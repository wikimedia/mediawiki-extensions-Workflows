<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity;

use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\ActivityDescriptor\GroupVoteDescriptor;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\NonRecoverableWorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\Util\ThresholdChecker;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Notifier;

class GroupVoteActivity extends GenericVoteActivity {

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'group-vote';

	/** @var array */
	private $initialAssignedUsers = [];

	/**
	 * Array with users votes data, has such structure:
	 * [
	 *   [
	 *     'vote' => <vote1>,
	 *     'userName' => <userName1>,
	 *     'comment' => <comment1>
	 *   ],
	 *   [
	 *     ...
	 *   ],
	 * ]
	 *
	 * Initialized with data from previous votes in same group (if presented).
	 *
	 * @var array
	 */
	private $usersVoted = [];

	/**
	 * Group data provider for group vote activity
	 *
	 * @var GroupDataProvider
	 */
	private $groupDataProvider;

	/** @var UserFactory */
	private $userFactory;

	/** @var ThresholdChecker|null */
	private $thresholdChecker = null;

	/**
	 * @inheritDoc
	 */
	public function __construct(
		Notifier $notifier, GroupDataProvider $groupDataProvider, UserFactory $userFactory, ITask $task
	) {
		parent::__construct( $notifier, $task );

		$this->groupDataProvider = $groupDataProvider;
		$this->userFactory = $userFactory;
	}

	/**
	 * Gets all users voted
	 *
	 * @return array List of user votes, has such structure as
	 *  {@link \MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity::$usersVoted}
	 */
	private function getUserVotes(): array {
		return $this->usersVoted;
	}

	/**
	 * Save some user's vote data
	 *
	 * @param string $userName Name of user, who voted
	 * @param string $vote <tt>"yes"</tt> if user accepted or <tt>"no"</tt> if user voted declined
	 * @param string $comment User comment
	 */
	private function saveUserVote( string $userName, string $vote, string $comment ) {
		$this->usersVoted[] = [
			'vote' => $vote,
			'userName' => $userName,
			'comment' => $comment
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getSpecialLogAction( string $vote ): string {
		return 'groupvote-' . $vote;
	}

	/**
	 * @inheritDoc
	 */
	protected function setSecondaryData( array $data, WorkflowContext $context ): void {
		parent::setSecondaryData( $data, $context );

		$errorMessages = [];
		$this->usersVoted = $this->parseUsersVoted( $data['users_voted'] );
		$this->handleErrors( $errorMessages );
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function setInitialAssignedUsers( array $data ): void {
		if ( isset( $data['assigned_group'] ) && !empty( $data['assigned_group'] ) ) {
			$this->initialAssignedUsers = array_values(
				$this->groupDataProvider->getUsersInGroup( $data['assigned_group'] )
			);
		} elseif ( isset( $data['assigned_users'] ) && !empty( $data['assigned_users'] ) ) {
			$this->initialAssignedUsers = explode( ',', $data['assigned_users'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function setPrimaryData( array $data, WorkflowContext $context ): void {
		parent::setPrimaryData( $data, $context );

		$errorMessages = [];

		$this->setInitialAssignedUsers( $data );
		if ( count( $this->initialAssignedUsers ) === 0 ) {
			// workflows-group-vote-group-no-users
			$errorMessages[] = 'workflows-' . $this->activityKey . '-group-no-users';
		}
		try {
			$this->thresholdChecker = new ThresholdChecker( $this->getThresholdData( $data ) );
		} catch ( Exception $e ) {
			$errorMessages[] = $e->getMessage();
		}

		$this->handleErrors( $errorMessages );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->setPrimaryData( $data, $context );
		$this->setSecondaryData( $data, $context );
		if ( !$this->actor instanceof User ) {
			throw new WorkflowExecutionException( 'workflows-user-vote-actor-invalid' );
		}

		$vote = $data['vote'];
		$comment = $data['comment'] ?? '';

		$this->doVote( $vote, $comment );
		$this->saveUserVote( $this->actor->getName(), $vote, $comment );

		// Update data to be returned
		$data['users_voted'] = $this->getUserVotes();

		// We need to reset comment field for next users
		$data['comment'] = '';

		try {
			$reached = $this->thresholdChecker->hasReachedThresholds(
				$this->usersVoted, count( $this->initialAssignedUsers ), 'vote'
			);
			if ( !$reached ) {
				// No thresholds reached yet
				return new ExecutionStatus( IActivity::STATUS_LOOP_COMPLETE, $data );
			}
		} catch ( Exception $e ) {
			// Unreachable threshold
			throw new NonRecoverableWorkflowExecutionException( $e->getMessage(), $this->task );
		}

		return new ExecutionStatus( IActivity::STATUS_COMPLETE, $data );
	}

	/**
	 * @param string|array $raw
	 *
	 * @return array
	 */
	private function parseUsersVoted( $raw ): array {
		if ( empty( $raw ) ) {
			return [];
		}

		if ( is_array( $raw ) ) {
			return $raw;
		}

		$decodedValue = json_decode( $raw, true );
		if ( !is_array( $decodedValue ) ) {
			return [];
		}

		return $decodedValue;
	}

	/**
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		$this->setInitialAssignedUsers( $properties );
		$usernames = $this->initialAssignedUsers;
		$parsedVoted = $this->parseUsersVoted( $properties['users_voted'] );
		$voted = array_column( $parsedVoted, 'userName' );

		$userFactory = $this->userFactory;
		return array_values( array_filter( $usernames, static function ( $username ) use ( $voted, $userFactory ) {
			$user = $userFactory->newFromName( $username );
			return !in_array( $user->getName(), $voted );
		} ) );
	}

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		return new UserInteractionModule(
			[ 'ext.workflows.activity.vote', 'ext.oOJSPlus.formelements' ],
			'workflows.object.form.GroupVote'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new GroupVoteDescriptor( $this, $this->logger );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	private function getThresholdData( array $data ): array {
		// Expect $data['threshold_yes_unit'] and $data['threshold_yes_value'] to be set, also for "no"
		$thresholdData = [];
		foreach ( [ 'yes', 'no' ] as $vote ) {
			$unit = $data['threshold_' . $vote . '_unit'] ?? null;
			$value = $data['threshold_' . $vote . '_value'] ?? null;
			if ( $unit === null || $value === null ) {
				throw new Exception( 'workflows-group-vote-thresholds-invalid' );
			}
			$thresholdData[] = [
				'type' => $vote,
				'unit' => $unit,
				'value' => $value
			];
		}

		return $thresholdData;
	}
}

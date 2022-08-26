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
use MWStake\MediaWiki\Component\Notifications\INotifier;
use User;

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

	/**
	 * @inheritDoc
	 */
	public function __construct(
		INotifier $notifier, GroupDataProvider $groupDataProvider, ITask $task
	) {
		parent::__construct( $notifier, $task );

		$this->groupDataProvider = $groupDataProvider;
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
		$data['users_voted'] = json_encode( $this->getUserVotes() );

		// We need to reset comment field for next users
		$data['comment'] = '';

		try {
			$checker = new ThresholdChecker(
				$this->getExtensionElementData( 'threshold' )
			);

			$reached = $checker->hasReachedThresholds(
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
	 * @return array|mixed
	 */
	private function parseUsersVoted( $raw ): array {
		if ( empty( $raw ) ) {
			return [];
		}
		if ( is_array( $raw ) ) {
			return $raw;
		}
		return json_decode( $raw, 1 );
	}

	/**
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		$this->setInitialAssignedUsers( $properties );
		$usernames = $this->initialAssignedUsers;
		$voted = $this->parseUsersVoted( $properties['users_voted'] );
		if ( !is_array( $voted ) ) {
			$voted = [];
		}
		$voted = array_column( $voted, 'userName' );

		return array_values( array_filter( $usernames, static function ( $username ) use ( $voted ) {
			return !in_array( $username, $voted );
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
}

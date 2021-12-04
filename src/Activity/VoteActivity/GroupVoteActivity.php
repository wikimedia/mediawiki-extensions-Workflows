<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity;

use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\ActivityDescriptor\GroupVoteDescriptor;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\NonRecoverableWorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\Util\ThresholdChecker;
use MediaWiki\Extension\Workflows\Util\ThresholdCheckerFactory;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MWStake\MediaWiki\Component\Notifications\INotifier;

class GroupVoteActivity extends GenericVoteActivity {

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'group-vote';

	/**
	 * Group name to vote
	 *
	 * @var string
	 */
	private $groupName;

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

	/** @var ThresholdChecker */
	private $thresholdChecker;

	/**
	 * @inheritDoc
	 */
	public function __construct(
		INotifier $notifier, GroupDataProvider $groupDataProvider,
		ThresholdCheckerFactory $factory, ITask $task
	) {
		parent::__construct( $notifier, $task );

		$this->groupDataProvider = $groupDataProvider;
		try {
			$this->thresholdChecker = $factory->makeThresholdChecker(
				$this->getExtensionElementData( 'threshold' )
			);
		} catch ( Exception $ex ) {
			throw new NonRecoverableWorkflowExecutionException( $ex->getMessage(), $this->task );
		}
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
		$this->usersVoted = !empty( $data['users_voted'] ) ? $data['users_voted'] : [];
		$this->handleErrors( $errorMessages );
	}

	/**
	 * @inheritDoc
	 */
	protected function setPrimaryData( array $data, WorkflowContext $context ): void {
		parent::setPrimaryData( $data, $context );

		$errorMessages = [];

		if ( !empty( $data['assigned_group'] ) ) {
			$this->groupName = $data['assigned_group'];

			if ( $this->groupDataProvider->getNumberOfUsersInGroup( $this->groupName ) === 0 ) {
				// workflows-group-vote-group-no-users
				$errorMessages[] = 'workflows-' . $this->activityKey . '-group-no-users';
			}
		} else {
			// workflows-group-vote-group-empty
			$errorMessages[] = 'workflows-' . $this->activityKey . '-group-empty';
		}

		$this->handleErrors( $errorMessages );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->setPrimaryData( $data, $context );
		$this->setSecondaryData( $data, $context );

		$vote = $data['vote'];
		$comment = $data['comment'] ?? '';

		$this->doVote( $vote, $comment );
		$this->saveUserVote( $this->actor->getName(), $vote, $comment );

		// Update data to be returned
		$data['users_voted'] = $this->getUserVotes();

		// We need to reset comment field for next users
		$data['comment'] = '';

		try {
			if (
				!$this->thresholdChecker->hasReachedThresholds( $this->usersVoted, $this->groupName, 'vote' )
			) {
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
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		$groupName = $properties['assigned_group'];
		$usernames = array_values( $this->groupDataProvider->getUsersInGroup( $groupName ) );
		$voted = $properties['users_voted'];
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
		return new GroupVoteDescriptor( $this );
	}
}

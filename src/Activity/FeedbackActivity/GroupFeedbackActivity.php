<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity;

use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification\FeedbackNotification;
use MediaWiki\Extension\Workflows\ActivityDescriptor\GroupFeedbackDescriptor;
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

class GroupFeedbackActivity extends GenericFeedbackActivity {

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'group-feedback';

	/**
	 * Array with users feedbacks, has such structure:
	 * [
	 *   [
	 *     'userName' => <userName1>,
	 *     'feedback' => <feedback1>
	 *   ],
	 *   [
	 *     ...
	 *   ],
	 * ]
	 *
	 * Initialized with data from previous feedbacks in same group (if presented).
	 *
	 * @var array
	 */
	private $usersFeedbacks = [];

	/**
	 * Group data provider for group vote activity
	 *
	 * @var GroupDataProvider
	 */
	private $groupDataProvider;
	/** @var array */
	private $initialAssignedUsers = [];

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
	 * Gets all users feedbacks
	 *
	 * @return array List of users feedbacks, has such structure:
	 *  {@link \MediaWiki\Extension\Workflows\Activity\FeedbackActivity\GroupFeedbackActivity::$usersFeedbacks}
	 */
	private function getUsersFeedbacks(): array {
		return $this->usersFeedbacks;
	}

	/**
	 * Saves user feedback
	 *
	 * @param string $userName Name of user to mark as voted
	 * @param string $feedback User feedback
	 */
	private function saveFeedback( string $userName, string $feedback ) {
		$this->usersFeedbacks[] = [
			'userName' => $userName,
			'feedback' => $feedback
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function setSecondaryData( array $data, WorkflowContext $context ): void {
		$errorMessages = [];

		$this->usersFeedbacks = $this->parseUserFeedbacks( $data['users_feedbacks'] );
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

		$feedback = $data['comment'] ?? '';

		$this->saveFeedback( $this->actor->getName(), $feedback );

		$this->logToSpecialLog( 'groupfeedback', $feedback );

		$notification = new FeedbackNotification(
			$this->actor,
			$this->targetPage,
			$this->owner,
			$this->getActivityDescriptor()->getActivityName()->parse(),
			$feedback
		);

		$this->getNotifier()->notify( $notification );

		$data['users_feedbacks'] = json_encode( $this->getUsersFeedbacks() );

		// We need to reset comment field for next users
		$data['comment'] = '';

		try {
			$checker = new ThresholdChecker(
				$this->getExtensionElementData( 'threshold' )
			);
			$reached = $checker->hasReachedThresholds(
				$this->getUsersFeedbacks(), count( $this->initialAssignedUsers )
			);
			if ( !$reached ) {
				// No thresholds reached yet
				return new ExecutionStatus( IActivity::STATUS_LOOP_COMPLETE, $data );
			}
		}
		catch ( Exception $e ) {
			throw new NonRecoverableWorkflowExecutionException( $e->getMessage(), $this->task );
		}

		return new ExecutionStatus( IActivity::STATUS_COMPLETE, $data );
	}

	/**
	 * @param string|array $raw
	 *
	 * @return array|mixed
	 */
	private function parseUserFeedbacks( $raw ): array {
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
		$processed = $this->parseUserFeedbacks( $properties['users_feedbacks'] );
		$processed = array_column( $processed, 'userName' );

		$as = array_values( array_filter( $usernames, static function ( $username ) use ( $processed ) {
			return !in_array( $username, $processed );
		} ) );

		return $as;
	}

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		return new UserInteractionModule(
			[ 'ext.workflows.activity.activity.feedback' ],
			'workflows.object.form.Feedback'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new GroupFeedbackDescriptor( $this, $this->logger );
	}
}

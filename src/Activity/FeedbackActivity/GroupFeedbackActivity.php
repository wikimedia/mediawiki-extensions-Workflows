<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity;

use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification\FeedbackNotification;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\NonRecoverableWorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\Util\ThresholdChecker;
use MediaWiki\Extension\Workflows\Util\ThresholdCheckerFactory;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MWStake\MediaWiki\Component\Notifications\INotifier;

class GroupFeedbackActivity extends GenericFeedbackActivity {

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'group-feedback';

	/**
	 * Group name to vote
	 *
	 * @var string
	 */
	private $groupName;

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
		$this->usersFeedbacks = !empty( $data['users_feedbacks'] ) ? $data['users_feedbacks'] : [];
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
				$errorMessages[] = 'workflows-' . $this->activityKey . '-group-no-users';
			}
		} else {
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

		$data['users_feedbacks'] = $this->getUsersFeedbacks();

		// We need to reset comment field for next users
		$data['comment'] = '';

		try {
			if (
				!$this->thresholdChecker->hasReachedThresholds( $this->getUsersFeedbacks(), $this->groupName )
			) {
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
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		$groupName = $properties['assigned_group'];
		$usernames = array_values( $this->groupDataProvider->getUsersInGroup( $groupName ) );
		$processed = $properties['users_feedbacks'];
		if ( !is_array( $processed ) ) {
			$processed = [];
		}
		$processed = array_column( $processed, 'userName' );

		return array_values( array_filter( $usernames, static function ( $username ) use ( $processed ) {
			return !in_array( $username, $processed );
		} ) );
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
}

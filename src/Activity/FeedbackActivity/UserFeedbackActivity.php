<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification\FeedbackNotification;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\WorkflowContext;

class UserFeedbackActivity extends GenericFeedbackActivity {

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'user-feedback';

	/**
	 * @inheritDoc
	 */
	public function start( $data, WorkflowContext $context ) {
		$this->setPrimaryData( $data, $context );

		$this->logger->info( "User {$this->actor->getName()} is assigned to give feedback on {$this->targetPage->getText()}" );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->setPrimaryData( $data, $context );

		$feedback = $data['comment'] ?? '';

		$this->logToSpecialLog( 'userfeedback', $feedback );

		$notification = new FeedbackNotification(
			$this->actor,
			$this->targetPage,
			$this->owner,
			$this->getActivityDescriptor()->getActivityName()->parse(),
			$feedback
		);

		$this->getNotifier()->notify( $notification );

		return new ExecutionStatus( IActivity::STATUS_COMPLETE, $data );
	}

	/**
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		$users = $properties['assigned_user'];
		return explode( ',', $users );
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

	protected function setSecondaryData( array $data, WorkflowContext $context ): void {
		// TODO: Implement setSecondaryData() method.
	}
}

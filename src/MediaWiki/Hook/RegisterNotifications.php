<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Hook\SetupAfterCacheHook;
use MWStake\MediaWiki\Component\Notifications\INotifier;

class RegisterNotifications implements SetupAfterCacheHook {
	/** @var INotifier */
	private $notifier;

	/**
	 * @param INotifier $notifier
	 */
	public function __construct( INotifier $notifier ) {
		$this->notifier = $notifier;
	}

	public function onSetupAfterCache() {
		// echo-category-title-workflow-cat
		$this->notifier->registerNotificationCategory( 'workflow-cat', [
			'priority' => 3,
			'tooltip' => "workflows-pref-tooltip-notification-workflow-cat",
			'no-dismiss' => [ 'web' ],
		] );

		$this->notifier->registerNotification(
			'workflows-task-assign',
			[
				'category' => 'workflow-cat',
				'web-body-message' => 'workflows-notification-task-assign-web',
				'web-body-params' => [ 'activity-type', 'title' ],
				'email-subject-message' => 'workflows-notification-task-assign-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-task-assign-email-body',
				'email-body-params' => [ 'activity-type' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-feedback-task-assign',
			[
				'category' => 'workflow-cat',
				'summary-message' => 'workflows-notification-task-assign-web',
				'summary-params' => [ 'activity-type', 'title' ],
				'web-body-message' => 'workflows-notification-feedback-task-assign-web-body',
				'web-body-params' => [ 'activity-type', 'title', 'initiator', 'instructions' ],
				'email-subject-message' => 'workflows-notification-task-assign-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-task-assign-email-body',
				'email-body-params' => [ 'activity-type' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-ended',
			[
				'category' => 'workflow-cat',
				'summary-message' => 'workflows-notification-ended-web',
				'summary-params' => [ 'title', 'workflow-name' ],
				'web-body-message' => 'workflows-notification-ended-web',
				'web-body-params' => [ 'title', 'workflow-name' ],
				'email-subject-message' => 'workflows-notification-ended-email-sub',
				'email-subject-params' => [ 'title', 'workflow-name' ],
				'email-body-message' => 'workflows-notification-ended-email-body',
				'email-body-params' => [ 'title', 'workflow-name' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-aborted',
			[
				'category' => 'workflow-cat',
				'summary-message' => 'workflows-notification-aborted-web-summary',
				'summary-params' => [ 'title', 'workflow-name' ],
				'web-body-message' => 'workflows-notification-aborted-web',
				'web-body-params' => [ 'reason' ],
				'email-subject-message' => 'workflows-notification-aborted-email-sub',
				'email-subject-params' => [ 'title', 'workflow-name' ],
				'email-body-message' => 'workflows-notification-aborted-email-body',
				'email-body-params' => [ 'title', 'reason', 'workflow-name' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-due-date-proximity',
			[
				'category' => 'workflow-cat',
				'web-body-message' => 'workflows-notification-due-date-proximity-web',
				'web-body-params' => [ 'title', 'activity-type' ],
				'email-subject-message' => 'workflows-notification-due-date-proximity-email-sub',
				'email-subject-params' => [ 'title', 'activity-type' ],
				'email-body-message' => 'workflows-notification-due-date-proximity-email-body',
				'email-body-params' => [ 'title', 'activity-type' ],
			]
		);

		// Non workflow-native notifications (from Activities)
		$this->notifier->registerNotification(
			'workflows-vote-accept',
			[
				'category' => 'workflow-cat',
				'web-body-message' => 'workflows-notification-user-vote-accept-web',
				'web-body-params' => [ 'agent', 'title' ],
				'email-subject-message' => 'workflows-notification-user-vote-accept-email-sub',
				'email-subject-params' => [ 'agent', 'title' ],
				'email-body-message' => 'workflows-notification-user-vote-accept-email-body',
				'email-body-params' => [ 'agent', 'title', 'activity-type', 'comment' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-vote-deny',
			[
				'category' => 'workflow-cat',
				'web-body-message' => 'workflows-notification-user-vote-deny-web',
				'web-body-params' => [ 'agent', 'title' ],
				'email-subject-message' => 'workflows-notification-user-vote-deny-email-sub',
				'email-subject-params' => [ 'agent', 'title' ],
				'email-body-message' => 'workflows-notification-user-vote-deny-email-body',
				'email-body-params' => [ 'agent', 'title', 'activity-type', 'comment' ],
			]
		);

		$this->notifier->registerNotification(
			'workflows-vote-delegate',
			[
				'category' => 'workflow-cat',
				'web-body-message' => 'workflows-notification-user-vote-delegate-web',
				'web-body-params' => [ 'agent', 'title', 'activity-type' ],
				'email-subject-message' => 'workflows-notification-user-vote-delegate-email-sub',
				'email-subject-params' => [ 'agent', 'title', 'activity-type' ],
				'email-body-message' => 'workflows-notification-user-vote-delegate-email-body',
				'email-body-params' => [ 'agent', 'title', 'activity-type', 'comment' ],
			]
		);
	}
}

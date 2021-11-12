<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Notifications\INotifier;

class Extension {
	public static function register() {
		/** @var INotifier $notifier */
		$notifier = MediaWikiServices::getInstance()->getService( 'MWStakeNotificationsNotifier' );
		$notifier->registerNotificationCategory( 'workflow-cat', [
			'priority' => 3,
			'tooltip' => "workflows-pref-tooltip-notification-workflow-cat"
		] );

		$notifier->registerNotification(
			'workflows-task-assign',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-task-assign-web',
				'web-body-params' => [ 'activity-type', 'title' ],
				'email-subject-message' => 'workflows-notification-task-assign-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-task-assign-email-body',
				'email-body-params' => [ 'activity-type' ],
			]
		);

		$notifier->registerNotification(
			'workflows-ended',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-ended-web',
				'web-body-params' => [ 'title' ],
				'email-subject-message' => 'workflows-notification-ended-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-ended-email-body',
				'email-body-params' => [ 'title' ],
			]
		);

		$notifier->registerNotification(
			'workflows-aborted',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-aborted-web',
				'web-body-params' => [ 'title' ],
				'email-subject-message' => 'workflows-notification-aborted-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-aborted-email-body',
				'email-body-params' => [ 'title' ],
			]
		);

		$notifier->registerNotification(
			'workflows-due-date-proximity',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-due-date-proximity-web',
				'web-body-params' => [ 'title', 'activity-type' ],
				'email-subject-message' => 'workflows-notification-due-date-proximity-email-sub',
				'email-subject-params' => [ 'title' ],
				'email-body-message' => 'workflows-notification-due-date-proximity-email-body',
				'email-body-params' => [ 'title', 'activity-type' ],
			]
		);

		// Non workflow-native notifications (from Activities)
		$notifier->registerNotification(
			'workflows-vote-accept',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-user-vote-accept-web',
				'web-body-params' => [ 'agent', 'title' ],
				'email-subject-message' => 'workflows-notification-user-vote-accept-email-sub',
				'email-subject-params' => [ 'agent', 'title' ],
				'email-body-message' => 'workflows-notification-user-vote-accept-email-body',
				'email-body-params' => [ 'agent', 'title', 'activity-type', 'comment' ],
			]
		);

		$notifier->registerNotification(
			'workflows-vote-deny',
			[
				'category' => 'workflows-cat',
				'web-body-message' => 'workflows-notification-user-vote-deny-web',
				'web-body-params' => [ 'agent', 'title' ],
				'email-subject-message' => 'workflows-notification-user-vote-deny-email-sub',
				'email-subject-params' => [ 'agent', 'title' ],
				'email-body-message' => 'workflows-notification-user-vote-deny-email-body',
				'email-body-params' => [ 'agent', 'title', 'activity-type', 'comment' ],
			]
		);

		$notifier->registerNotification(
			'workflows-vote-delegate',
			[
				'category' => 'workflows-cat',
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

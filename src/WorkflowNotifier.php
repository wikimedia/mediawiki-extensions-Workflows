<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\TaskAssigned;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowAborted;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MWStake\MediaWiki\Component\Notifications\INotification;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use User;

/**
 * Class responsible for sending out notifications
 * based on events that occur during workflow execution
 *
 * Individual activities may emit additional notifications,
 * this class deals only with generic workflows notifications
 */
class WorkflowNotifier implements Consumer {
	/** @var INotifier */
	private $notifier;
	/** @var ActivityManager */
	private $activityManger;
	/** @var Workflow */
	private $workflow;

	public function __construct(
		INotifier $notifier, ActivityManager $activityManager, Workflow $workflow
	) {
		$this->notifier = $notifier;
		$this->activityManger = $activityManager;
		$this->workflow = $workflow;
	}

	public function handle( Message $message ) {
		if ( $message->aggregateRootId() !== $this->workflow->getStorage()->aggregateRootId() ) {
			// Not a message for us
			return;
		}
		$event = $message->event();

		if ( $event instanceof ActivityEvent ) {
			$task = $this->workflow->getTaskFromId( $event->getElementId() );
			$activity = $this->activityManger->getActivityForTask( $task );

			if ( $activity instanceof UserInteractiveActivity ) {
				$this->notifyAboutEvent( $activity, $event );
			}
		}

		if ( $event instanceof Storage\Event\WorkflowAborted ) {
			$reason = $event->getReason();
			if ( is_array( $reason ) ) {
				if ( isset( $reason['message'] ) ) {
					$reason = $reason['message'];
				} else {
					$reason = '';
				}
			}
			$notification = new WorkflowAborted(
				$this->workflow->getContext()->getInitiator(),
				$this->workflow->getContext()->getContextPage(),
				$reason
			);
			$this->notifier->notify( $notification );
		}

		if ( $event instanceof Storage\Event\WorkflowEnded ) {
			$notification = new WorkflowEnded(
				$this->workflow->getContext()->getInitiator(),
				$this->workflow->getContext()->getContextPage()
			);
			$this->notifier->notify( $notification );
		}
	}

	private function notifyAboutEvent( UserInteractiveActivity $activity, ActivityEvent $event ) {
		if ( $event instanceof TaskStarted ) {
			$notification = $this->getNotificationFor( $activity, $event );
			if ( $notification instanceof INotification ) {
				$this->notifier->notify( $notification );
			}
		}
	}

	private function getNotificationFor(
		UserInteractiveActivity $activity, ActivityEvent $event
	) {
		$notification = $activity->getActivityDescriptor()->getNotificationFor(
			$event, $this->workflow
		);

		if ( $notification instanceof INotification ) {
			return $notification;
		}
		if ( $event instanceof TaskStarted ) {
			return new TaskAssigned(
				$this->getTargetUsers( $activity ),
				$this->workflow->getContext()->getContextPage(),
				$activity->getActivityDescriptor()->getActivityName()->parse()
			);
		}

		return null;
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @return array
	 * @throws Exception\WorkflowExecutionException
	 */
	public function getTargetUsers( UserInteractiveActivity $activity ) {
		$targetUsers = $this->activityManger->getTargetUsersForActivity( $activity ) ?? [];
		$validUsers = [];
		foreach ( $targetUsers as $username ) {
			$user = User::newFromName( $username );
			if ( !$user instanceof User || !$user->isRegistered() ) {
				continue;
			}
			$validUsers[] = $user;
		}

		return $validUsers;
	}
}

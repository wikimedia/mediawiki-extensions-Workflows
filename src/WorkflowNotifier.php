<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\TaskAssigned;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowAborted;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
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

		if ( $event instanceof TaskStarted ) {
			$task = $this->workflow->getTaskFromId( $event->getElementId() );
			$activity = $this->activityManger->getActivityForTask( $task );

			if ( $activity instanceof UserInteractiveActivity ) {
				$targetUsers = $this->activityManger->getTargetUsersForActivity( $activity ) ?? [];
				foreach ( $targetUsers as $username ) {
					$user = User::newFromName( $username );
					if ( !$user instanceof User || !$user->isRegistered() ) {
						continue;
					}
					$notification = new TaskAssigned(
						$user,
						$this->workflow->getContext()->getContextPage(),
						$activity->getActivityDescriptor()->getActivityName()->parse()
					);
					$this->notifier->notify( $notification );
				}
			}

		}
		if ( $event instanceof Storage\Event\WorkflowAborted ) {
			$notification = new WorkflowAborted(
				$this->workflow->getContext()->getInitiator(),
				$this->workflow->getContext()->getContextPage()
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
}

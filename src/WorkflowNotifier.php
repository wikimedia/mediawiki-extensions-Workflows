<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\TaskAssigned;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowAborted;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use Message as MWMessage;
use MWStake\MediaWiki\Component\Notifications\INotification;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use RawMessage;

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
	private $activityManager;
	/** @var Workflow */
	private $workflow;

	/**
	 * @param INotifier $notifier
	 * @param ActivityManager $activityManager
	 * @param Workflow $workflow
	 */
	public function __construct(
		INotifier $notifier, ActivityManager $activityManager, Workflow $workflow
	) {
		$this->notifier = $notifier;
		$this->activityManager = $activityManager;
		$this->workflow = $workflow;
	}

	/**
	 * @param Message $message
	 * @return void
	 */
	public function handle( Message $message ) {
		if ( $message->aggregateRootId() !== $this->workflow->getStorage()->aggregateRootId() ) {
			// Not a message for us
			return;
		}
		$event = $message->event();

		$workflowName = $this->workflow->getDefinition()->getSource()->getName();
		$repo = $this->workflow->getDefinition()->getSource()->getRepositoryKey();
		$workflowNameMsg = MWMessage::newFromKey( "workflows-$repo-definition-$workflowName-title" );
		if ( !$workflowNameMsg->exists() ) {
			$workflowNameMsg = RawMessage::newFromKey( $workflowName );
		}

		if ( $event instanceof ActivityEvent ) {
			$task = $this->workflow->getTaskFromId( $event->getElementId() );
			$activity = $this->activityManager->getActivityForTask( $task );

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
			$initiator = $this->workflow->getContext()->getInitiator();
			if ( $initiator ) {
				// Notify initiator of workflow
				$notification = new WorkflowAborted(
					$initiator,
					$workflowNameMsg,
					$this->workflow->getContext()->getContextPage(),
					$reason
				);
				$this->notifier->notify( $notification );
			}

			// Get all current workflow elements
			$currentElements = $this->workflow->current();
			if ( !is_array( $currentElements ) ) {
				$currentElements = [ $currentElements ];
			}

			// Go through all current tasks and notify target users about workflow abortion
			foreach ( $currentElements as $currentElement ) {
				if ( $currentElement instanceof ITask ) {
					$activity = $this->activityManager->getActivityForTask( $currentElement );

					if ( $activity instanceof UserInteractiveActivity ) {
						$targetUsers = $this->activityManager->getTargetUsersForActivity( $activity, true );
						if ( !empty( $targetUsers ) ) {
							// Notify participants
							$notification = new WorkflowAborted(
								$targetUsers,
								$workflowNameMsg,
								$this->workflow->getContext()->getContextPage(),
								$reason
							);

							$this->notifier->notify( $notification );
						}
					}
				}
			}
		}

		if ( $event instanceof Storage\Event\WorkflowEnded ) {
			$initiator = $this->workflow->getContext()->getInitiator();
			if ( $initiator ) {
				$notification = new WorkflowEnded(
					$initiator,
					$workflowNameMsg,
					$this->workflow->getContext()->getContextPage()
				);
				$this->notifier->notify( $notification );
			}
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
			$targetUsers = $this->activityManager->getTargetUsersForActivity( $activity, true );
			if ( empty( $targetUsers ) ) {
				return null;
			}
			return new TaskAssigned(
				$targetUsers,
				$this->workflow->getContext()->getContextPage(),
				$activity->getActivityDescriptor()->getActivityName()->parse()
			);
		}

		return null;
	}
}

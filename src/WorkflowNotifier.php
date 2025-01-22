<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Event\TaskAssignedEvent;
use MediaWiki\Extension\Workflows\Event\WorkflowAbortedEvent;
use MediaWiki\Extension\Workflows\Event\WorkflowEndedEvent;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Message\Message as MWMessage;
use MWStake\MediaWiki\Component\Events\BotAgent;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notifier;
use RawMessage;
use Throwable;

/**
 * Class responsible for sending out notifications
 * based on events that occur during workflow execution
 *
 * Individual activities may emit additional notifications,
 * this class deals only with generic workflows notifications
 */
class WorkflowNotifier implements Consumer {
	/** @var Notifier */
	private $notifier;
	/** @var ActivityManager */
	private $activityManager;
	/** @var Workflow */
	private $workflow;

	/**
	 * @param Notifier $notifier
	 * @param ActivityManager $activityManager
	 * @param Workflow $workflow
	 */
	public function __construct(
		Notifier $notifier, ActivityManager $activityManager, Workflow $workflow
	) {
		$this->notifier = $notifier;
		$this->activityManager = $activityManager;
		$this->workflow = $workflow;
	}

	/**
	 * @param Message $message
	 * @return void
	 * @throws WorkflowExecutionException
	 */
	public function handle( Message $message ) {
		try {
			$storage = $this->workflow->getStorage();
		} catch ( Throwable $e ) {
			// Workflow not found
			return;
		}

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
				$reason = $reason['message'] ?? '';
			}
			$initiator = $this->workflow->getContext()->getInitiator();
			if ( $initiator ) {
				// Notify initiator of workflow
				$notification = new WorkflowAbortedEvent(
					new BotAgent(),
					$this->workflow->getContext()->getContextPage(),
					[ $initiator ],
					$workflowNameMsg,
					$reason
				);
				$this->notifier->emit( $notification );
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
							$notification = new WorkflowAbortedEvent(
								new BotAgent(),
								$this->workflow->getContext()->getContextPage(),
								$targetUsers,
								$workflowNameMsg,
								$reason
							);

							$this->notifier->emit( $notification );
						}
					}
				}
			}
		}

		if ( $event instanceof Storage\Event\WorkflowEnded ) {
			$initiator = $this->workflow->getContext()->getInitiator();
			if ( $initiator ) {
				$notification = new WorkflowEndedEvent(
					$this->workflow->getContext()->getContextPage(),
					[ $initiator ],
					$workflowNameMsg
				);
				$this->notifier->emit( $notification );
			}
		}
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @param ActivityEvent $event
	 * @return void
	 * @throws WorkflowExecutionException
	 */
	private function notifyAboutEvent( UserInteractiveActivity $activity, ActivityEvent $event ) {
		if ( $event instanceof TaskStarted ) {
			$notification = $this->getNotificationFor( $activity, $event );
			if ( $notification instanceof INotificationEvent ) {
				$this->notifier->emit( $notification );
			}
		}
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @param ActivityEvent $event
	 * @return INotificationEvent|null
	 * @throws WorkflowExecutionException
	 */
	private function getNotificationFor(
		UserInteractiveActivity $activity, ActivityEvent $event
	): ?INotificationEvent {
		$notification = $activity->getActivityDescriptor()->getNotificationFor(
			$event, $this->workflow
		);

		if ( $notification instanceof INotificationEvent ) {
			return $notification;
		}
		if ( $event instanceof TaskStarted ) {
			$targetUsers = $this->activityManager->getTargetUsersForActivity( $activity, true );
			if ( empty( $targetUsers ) ) {
				return null;
			}
			return new TaskAssignedEvent(
				$this->workflow->getContext()->getContextPage(),
				$targetUsers,
				$activity->getActivityDescriptor()->getActivityName()->parse()
			);
		}

		return null;
	}
}

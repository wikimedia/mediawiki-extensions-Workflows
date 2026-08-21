<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use MediaWiki\Extension\UnifiedTaskOverview\TaskStore;
use MediaWiki\Extension\Workflows\ActivityManager;
use MediaWiki\Extension\Workflows\ActivityManagerFactory;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IUserInteractiveActivityDescriptor;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowAborted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowEnded;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\MediaWikiServices;
use Throwable;

class Listener implements MessageConsumer {

	private ActivityManager $activityManager;

	/**
	 * @param ActivityManagerFactory $activityManagerFactory
	 */
	public function __construct(
		ActivityManagerFactory $activityManagerFactory
	) {
		$this->activityManager = $activityManagerFactory->newActivityManager();
	}

	/**
	 * @param Message $message
	 * @return void
	 * @throws WorkflowExecutionException
	 * @throws Throwable
	 */
	public function handle( Message $message ): void {
		$taskStore = $this->getTaskStore();
		if ( !$taskStore ) {
			return;
		}
		$event = $message->payload();

		// TODO: Cannot be injected - circular dependency
		$workflowFactory = MediaWikiServices::getInstance()->getService( 'WorkflowFactory' );

		if ( $event instanceof WorkflowAborted || $event instanceof WorkflowEnded || $event instanceof TaskCompleted ) {
			$workflowId = WorkflowId::fromString( $message->aggregateRootId()->toString() );
			$workflow = $workflowFactory->getWorkflowForBot( $workflowId );
			if ( !$workflow->getStorage() ) {
				return;
			}
			$contextPage = $workflow->getContext()->getContextPage();
			if ( $contextPage ) {
				$type = 'workflows-activity-' . $workflow->getStorage()->aggregateRootId()->toString();
				$taskStore->clearForPage( $contextPage, $type );
			}
		}

		if ( $event instanceof ActivityEvent ) {
			$workflowId = WorkflowId::fromString( $message->aggregateRootId()->toString() );
			$workflow = $workflowFactory->getWorkflowForBot( $workflowId );
			$task = $workflow->getTaskFromId( $event->getElementId() );
			if ( !$task ) {
				return;
			}
			$this->activityManager->setWorkflow( $workflow );
			$activity = $this->activityManager->getActivityForTask( $task );
			if ( $activity instanceof UserInteractiveActivity ) {
				$targetUsers = $this->activityManager->getTargetUsersForActivity( $activity, true );
				if ( empty( $targetUsers ) ) {
					return;
				}
				$contextPage = $workflow->getContext()->getContextPage();
				if ( !$contextPage ) {
					// No context page
					return;
				}
				$descriptor = $activity->getActivityDescriptor();
				if ( $descriptor instanceof IUserInteractiveActivityDescriptor ) {
					$taskDescriptor = $descriptor->getTaskDescriptor( $workflow );
					foreach ( $targetUsers as $user ) {
						$taskStore->storeTask( $taskDescriptor, $user );
					}
				}

			}
		}
	}

	/**
	 * @return TaskStore|null
	 */
	private function getTaskStore(): ?TaskStore {
		$services = MediaWikiServices::getInstance();
		return $services->hasService( 'UnifiedTaskOverview.TaskStore' ) ?
			$services->getService( 'UnifiedTaskOverview.TaskStore' ) : null;
	}
}

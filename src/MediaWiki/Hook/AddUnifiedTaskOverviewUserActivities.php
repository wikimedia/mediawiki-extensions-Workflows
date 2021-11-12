<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use EventSauce\EventSourcing\AggregateRootId;
use MediaWiki\Extension\UnifiedTaskOverview\Hook\GetTaskDescriptors;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\WorkflowAborted;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\AutoAbortedWorkflow;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowAutoAborted;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPageFactory;
use User;

class AddUnifiedTaskOverviewUserActivities implements GetTaskDescriptors {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var PermissionManager */
	private $permissionManager;
	/** @var SpecialPageFactory */
	private $specialPageFactory;

	public function __construct(
		WorkflowStateStore $stateStore, WorkflowFactory $factory,
		PermissionManager $pm, SpecialPageFactory $spf
	) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $factory;
		$this->permissionManager = $pm;
		$this->specialPageFactory = $spf;
	}

	/**
     * @inheritDoc
     */
    public function onUnifiedTaskOverviewGetTaskDescriptors( &$descriptors, $user ) {
		$tasks = $this->getUserActivities( $user );
		$aborted = $this->getAutoAborted( $user );
		$descriptors = array_merge( $descriptors, $tasks );
		$descriptors = array_merge( $descriptors, $aborted );
    }

	/**
	 * @param User $user
	 * @return array
	 */
    private function getUserActivities( User $user ) {
		$ids = array_merge(
			$this->stateStore->active()->onEvent( TaskStarted::class )->query(),
			$this->stateStore->active()->onEvent( TaskLoopCompleted::class )->query(),
			$this->stateStore->active()->onEvent( TaskIntermediateStateChanged::class )->query()
		);

		$activities = [];
		/** @var AggregateRootId $id */
		foreach ( $ids as $id ) {
			try {
				$workflow = $this->workflowFactory->getWorkflow( $id );
				$current = $workflow->current();

				foreach ( $current as $item ) {
					if ( !$item instanceof ITask || $item->getElementName() !== 'userTask' ) {
						continue;
					}
					$activity = $workflow->getActivityForTask( $item );
					if ( !$activity instanceof UserInteractiveActivity ) {
						continue;
					}
					$target = $workflow->getActivityManager()->getTargetUsersForActivity( $activity );
					if ( $target === null || in_array( $user->getName(), $target ) ) {
						$activities[] = $activity->getActivityDescriptor()->getTaskDescriptor( $workflow );
					}

				}
			} catch ( WorkflowExecutionException $ex ) {
				// TODO: Log
				continue;
			}
		}

		return $activities;
	}

	/**
	 * @param User $user
	 * @return array
	 */
	private function getAutoAborted( User $user ) {
		$ids = $this->stateStore->onEvent( WorkflowAutoAborted::class )->query();

		$autoAbortedWorkflows = [];
		/** @var AggregateRootId $id */
		foreach ( $ids as $id ) {
			try {
				$workflow = $this->workflowFactory->getWorkflow( $id );
				$initiator = $workflow->getContext()->getInitiator();
				if ( !$this->isUserAdmin( $user ) && ( !$initiator || !$user->equals( $initiator ) ) ) {
					continue;
				}
				$stateMessage = $workflow->getStateMessage();
				if ( !is_array( $stateMessage ) ) {
					continue;
				}
				if ( isset( $stateMessage['noReport'] ) && $stateMessage['noReport'] ) {
					continue;
				}
				$autoAbortedWorkflows[] = $this->getAutoAbortedWorkflowDescriptor( $workflow );
			} catch ( WorkflowExecutionException $ex ) {
				// TODO: Log
				continue;
			}
		}

		return $autoAbortedWorkflows;
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	private function isUserAdmin( User $user ): bool {
		return $this->permissionManager->userHasRight( $user, 'workflows-admin' );
	}

	private function getAutoAbortedWorkflowDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new AutoAbortedWorkflow( $workflow, $this->specialPageFactory->getPage( 'WorkflowsOverview' ) );
	}
}

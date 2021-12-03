<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use EventSauce\EventSourcing\AggregateRootId;
use MediaWiki\Extension\UnifiedTaskOverview\Hook\GetTaskDescriptors;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
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
		$descriptors = array_merge( $descriptors, $tasks );
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
}

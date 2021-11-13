<?php

namespace MediaWiki\Extension\Workflows\StateTracker;

use MediaWiki\Extension\Workflows\ActivityManager;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Util\MultiInstanceHelper;
use MediaWiki\Extension\Workflows\WorkflowContext;

class SequentialStateTracker extends MultiInstanceStateTracker {
	/** @var ITask */
	private $task;
	/** @var WorkflowContext */
	private $context;
	/** @var ActivityManager */
	private $activityManager;
	/** @var MultiInstanceHelper */
	private $helper;
	/** @var array */
	private $sets = [];
	/** @var int */
	private $counter = 0;
	/** @var ITask|null */
	private $currentTask = null;

	public function __construct(
		ITask $task, WorkflowContext $context, ActivityManager $activityManager
	) {
		$this->task = $task;
		$this->context = $context;
		$this->activityManager = $activityManager;
		$this->helper = new MultiInstanceHelper();
		$this->populateSets();
	}

	private function populateSets() {
		$this->sets = $this->helper->getMultiInstancePropertyData( $this->task, $this->context );
	}

	public function isCompleted(): bool {
		return empty( $this->sets );
	}

	public function getNext(): ?ITask {
		if ( !$this->currentTask ) {
			if ( $this->isCompleted() ) {
				return null;
			}
			$set = array_shift( $this->sets );
			$task = $this->helper->cloneTaskWithCounter( $this->task, 'seq_' . $this->counter );
			$activity = $this->activityManager->getActivityForTask( $task );
			$this->activityManager->setActivityProperties( $activity, $set );
			$this->counter++;
			$this->currentTask = $task;
		}

		return $this->currentTask;
	}

	/**
	 * Get next ref for the workflow after all tasks complete
	 * We can just take first, as they all have to point to the same exit element
	 *
	 * @return string
	 */
	public function getAfterRef(): string {
		return $this->task->getOutgoing()[0];
	}

	/**
	 * @param ITask $task
	 */
	public function markCompleted( ITask $task ) {
		if ( $this->currentTask && $this->currentTask->getId() === $task->getId() ) {
			$this->currentTask = null;
		} else {
			throw new WorkflowExecutionException(
				'Execution order broken: Trying to complete task that is not started'
			);
		}
	}

	public function getVirtualTask( $taskId ): ?ITask {
		if ( $this->currentTask instanceof ITask && $this->currentTask->getId() === $taskId ) {
			return $this->currentTask;
		}

		return null;
	}
}

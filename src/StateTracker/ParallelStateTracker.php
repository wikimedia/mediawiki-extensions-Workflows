<?php

namespace MediaWiki\Extension\Workflows\StateTracker;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;

class ParallelStateTracker extends MultiInstanceStateTracker {
	/** @var ITask[] */
	private $tasks;

	public function __construct( $tasks ) {
		$this->tasks = $tasks;
	}

	/**
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function isCompleted(): bool {
		return empty( $this->getPending() );
	}

	/**
	 * @return ITask[]
	 * @throws WorkflowExecutionException
	 */
	public function getPending(): array {
		return array_filter( $this->tasks, function ( ITask $task ) {
			return !in_array( $task->getId(), $this->completed );
		} );
	}

	/**
	 * Get next ref for the workflow after all tasks complete
	 * We can just take first, as they all have to point to the same exit element
	 *
	 * @return string
	 */
	public function getAfterRef(): string {
		return $this->tasks[0]->getOutgoing()[0];
	}

	public function getVirtualTask( $taskId ): ?ITask {
		foreach ( $this->tasks as $task ) {
			if ( $task->getId() === $taskId ) {
				return $task;
			}
		}
		return null;
	}
}

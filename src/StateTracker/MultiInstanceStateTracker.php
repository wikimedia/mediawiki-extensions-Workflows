<?php

namespace MediaWiki\Extension\Workflows\StateTracker;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;

abstract class MultiInstanceStateTracker {
	/** @var string[] */
	protected $completed = [];

	/**
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	abstract public function isCompleted(): bool;

	/**
	 * Get next ref for the workflow after all tasks complete
	 * We can just take first, as they all have to point to the same exit element
	 *
	 * @return string
	 */
	abstract public function getAfterRef(): string;

	/**
	 * @param ITask $task
	 */
	public function markCompleted( ITask $task ) {
		$this->completed[] = $task->getId();
	}

	abstract public function getVirtualTask( $taskId ): ?ITask;
}

<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;

interface IActivity {
	public const STATUS_NOT_STARTED = 0;
	public const STATUS_STARTED = 1;
	public const STATUS_EXECUTING = 2;
	public const STATUS_LOOP_COMPLETE = 4;
	public const STATUS_COMPLETE = 3;
	public const STATUS_EXPIRED = 5;

	/**
	 * Called when process reaches the activity
	 * Should not be confused with execute method
	 * This is not meant to have any business logic,
	 * its only here to let activity know it is it's turn
	 *
	 * @param array $data Activity properties, if any#
	 * @param WorkflowContext $context
	 * @return void
	 */
	public function start( $data, WorkflowContext $context );

	/**
	 * Called when "completeTask" is called from the Workflow
	 * It should contain all of the execution logic
	 *
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return ExecutionStatus
	 * @throws WorkflowExecutionException
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus;

	/**
	 * This function will be called to check activity status
	 * It will be called any time the workflow is loaded,
	 * AFTER activity's execute method is called.
	 * It should be used only in cases when execute does not
	 * return STATUS_COMPLETE, to give activity a chance to
	 * complete itself. It will be called until STATUS_COMPLETE is returned
	 * It is allowed for this function to change its internal state (props and status)
	 * but should not contain any logic that could have otherwise went to execute()
	 *
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return ExecutionStatus|null if no change is to be reported
	 */
	public function probe( $data, WorkflowContext $context ): ?ExecutionStatus;

	/**
	 * Get task object this activity bases on
	 *
	 * @return ITask
	 */
	public function getTask(): ITask;
}

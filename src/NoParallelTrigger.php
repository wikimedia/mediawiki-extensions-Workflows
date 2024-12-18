<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;

interface NoParallelTrigger {

	/**
	 * @param WorkflowStateStore $stateStore
	 * @return mixed
	 */
	public function setWorkflowStore( WorkflowStateStore $stateStore );

	/**
	 * @return bool
	 */
	public function isAlreadyRunning(): bool;
}

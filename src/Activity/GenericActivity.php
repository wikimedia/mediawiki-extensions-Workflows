<?php

namespace MediaWiki\Extension\Workflows\Activity;

use MediaWiki\Extension\Workflows\WorkflowContext;

class GenericActivity extends Activity {
	/**
	 * @inheritDoc
	 */
	public function start( $data, WorkflowContext $context ) {
		// NOOP
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		return new ExecutionStatus( static::STATUS_COMPLETE, $data );
	}

	/**
	 * @inheritDoc
	 */
	public function probe( $data, WorkflowContext $context ): ?ExecutionStatus {
		return null;
	}
}

<?php

namespace MediaWiki\Extension\Workflows\Activity\ExecutionStatus;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\IActivity;

class IntermediateExecutionStatus extends ExecutionStatus {
	/**
	 * @param array $payload
	 */
	public function __construct( array $payload = [] ) {
		parent::__construct( IActivity::STATUS_EXECUTING, $payload );
	}
}

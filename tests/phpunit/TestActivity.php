<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\WorkflowContext;

class TestActivity extends GenericActivity {

	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$loop = $data['loop'];
		if ( $loop === '' ) {
			$loop = 0;
		}
		$loop++;

		if ( $loop === 5 ) {
			return new ExecutionStatus( IActivity::STATUS_COMPLETE, [ 'loop' => $loop ] );
		}
		return new ExecutionStatus( IActivity::STATUS_LOOP_COMPLETE, [ 'loop' => $loop, 'dummy' => true ] );
	}
}

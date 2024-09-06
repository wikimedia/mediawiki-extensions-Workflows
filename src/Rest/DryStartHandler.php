<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\ActivitySerializer;

class DryStartHandler extends StartHandler {
	/**
	 * Start the workflow to see if it has the initializer
	 *
	 * @return \MediaWiki\Rest\Response
	 * @throws \MediaWiki\Rest\HttpException
	 * @throws \MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException
	 */
	public function doExecute() {
		$engine = $this->getWorkflowEngine();

		$engine->start( $this->getBodyData( 'startData' ), true );
		$initializer = $this->getInitializer( $engine );
		if ( $initializer ) {
			$initData = $this->getBodyData( 'initData', null );
			if ( $initData ) {
				$engine->getActivityManager()->startActivityWithAdditionalData( $initializer, $initData );
			} else {
				$engine->getActivityManager()->startActivity( $initializer );
			}
			$serializer = new ActivitySerializer( $engine );
			$initializer = $serializer->serialize( $initializer );
		}

		return $this->getResponseFactory()->createJson( [
			'initializer' => $initializer
		] );
	}
}

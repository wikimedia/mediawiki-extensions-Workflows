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
			$serializer = new ActivitySerializer( $engine );
			$initializer = $serializer->serialize( $initializer );
		}

		return $this->getResponseFactory()->createJson( [
			'initializer' => $initializer
		] );
	}
}

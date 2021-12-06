<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Rest\HttpException;

class AbortHandler extends JSONBodyActionHandler {

	public function doExecute() {
		$workflow = $this->loadWorkflow( $this->getWorkflowId() );
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			throw new HttpException( 'Cannot abort non-running workflow' );
		}
		try {
			$workflow->abort( $this->getBodyData( 'reason', '' ) );
			$this->getWorkflowFactory()->persist( $workflow );
		} catch ( \Exception $ex ) {
			throw new HttpException( $ex->getMessage() );
		}

		return $this->getResponseFactory()->createJson( [
			'ack' => true
		] );
	}
}

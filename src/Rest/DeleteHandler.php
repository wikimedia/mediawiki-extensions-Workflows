<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Rest\HttpException;
use MediaWiki\Extension\Workflows\Workflow;

class DeleteHandler extends JSONBodyActionHandler {

	public function doExecute() {
		$workflow = $this->loadWorkflow( $this->getWorkflowId() );
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			throw new HttpException( 'Cannot abort non-running workflow' );
		}
		try {
			// Does not work! No body data on DELETE REQUEST
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

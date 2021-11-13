<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Rest\HttpException;

class RestoreHandler extends JSONBodyActionHandler {

	public function doExecute() {
		$workflow = $this->loadWorkflow( $this->getWorkflowId() );
		if ( $workflow->getCurrentState() !== Workflow::STATE_ABORTED ) {
			throw new HttpException( 'Cannot restore non-aborted workflow' );
		}
		try {
			$workflow->unAbort( $this->getBodyData( 'reason', '' ) );
			$this->getWorkflowFactory()->persist( $workflow );
		} catch ( \Exception $ex ) {
			throw new HttpException( $ex->getMessage() );
		}

		return $this->getResponseFactory()->createJson( [
			'ack' => true
		] );
	}
}

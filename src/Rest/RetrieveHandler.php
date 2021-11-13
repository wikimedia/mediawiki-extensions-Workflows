<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\MediaWikiServices;

class RetrieveHandler extends ActionHandler {
	/**
	 * @param WorkflowFactory $factory
	 */
	public function __construct( WorkflowFactory $factory ) {
		parent::__construct( $factory );
	}

	public function doExecute() {
		$engine = $this->loadWorkflow( $this->getWorkflowId() );
		$workflowSerializer = MediaWikiServices::getInstance()->getService( 'WorkflowSerializer' );

		return $this->getResponseFactory()->createJson( [
			$this->getWorkflowId()->toString() => $workflowSerializer->serialize( $engine )
		] );
	}

}

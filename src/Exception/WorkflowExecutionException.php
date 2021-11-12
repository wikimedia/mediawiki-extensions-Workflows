<?php

namespace MediaWiki\Extension\Workflows\Exception;

use Exception;
use MediaWiki\Extension\Workflows\Definition\IElement;

class WorkflowExecutionException extends Exception {
	/**
	 * @param string $message
	 * @param IElement|null $element
	 */
	public function __construct( $message, IElement $element = null ) {
		$exceptionMessage = 'Workflow execution exception: %s';
		if ( $element instanceof IElement ) {
			$exceptionMessage = 'Workflow execution exception: %s, on element %s';
		}

		$message = sprintf(
			$exceptionMessage,
			$message, $element instanceof IElement ? $element->getId() : ''
		);

		parent::__construct( $message, 500 );
	}
}

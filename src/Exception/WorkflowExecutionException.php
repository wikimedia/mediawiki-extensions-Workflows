<?php

namespace MediaWiki\Extension\Workflows\Exception;

use Exception;
use MediaWiki\Extension\Workflows\Definition\IElement;
use MediaWiki\Extension\Workflows\Definition\ITask;
use Message;

class WorkflowExecutionException extends Exception {
	/** @var string */
	protected $message;
	/** @var ITask|null */
	protected $element;

	/**
	 * @param string $message
	 * @param IElement|null $element
	 */
	public function __construct( $message, IElement $element = null ) {
		$this->message = $message;
		$this->element = $element;

		parent::__construct( $this->getExceptionMessage(), 500 );
	}

	/**
	 * @return string
	 */
	protected function getExceptionMessage() {
		$exceptionMessage = 'Workflow execution exception: %s';
		if ( $this->element instanceof IElement ) {
			$exceptionMessage = 'Workflow execution exception: %s, on element %s';
		}

		// A lot of the code already assumes this is happening...
		$messageObject = Message::newFromKey( $this->message );
		if ( $messageObject->exists() ) {
			$this->message = $messageObject->text();
		}

		return sprintf(
			$exceptionMessage,
			$this->message, $this->element instanceof IElement ? $this->element->getId() : ''
		);
	}
}

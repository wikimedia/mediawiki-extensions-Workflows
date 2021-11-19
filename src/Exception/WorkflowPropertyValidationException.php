<?php

namespace MediaWiki\Extension\Workflows\Exception;

class WorkflowPropertyValidationException extends WorkflowExecutionException {
	/** @var string */
	private $property;

	/**
	 * @param string $message
	 * @param string $property
	 */
	public function __construct( $message, $property ) {
		$this->property = $property;

		parent::__construct( $message );
	}

	/**
	 * @return string
	 */
	protected function getExceptionMessage() {
		return \Message::newFromKey( 'workflows-exception-property-validation' )->params(
			$this->property,
			$this->message
		)->text();
	}
}

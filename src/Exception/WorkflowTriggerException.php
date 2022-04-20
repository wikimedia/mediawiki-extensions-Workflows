<?php

namespace MediaWiki\Extension\Workflows\Exception;

use Exception;
use MediaWiki\Extension\Workflows\ITrigger;

class WorkflowTriggerException extends Exception {
	/** @var string */
	protected $message;
	/** @var ITrigger */
	protected $trigger;

	/**
	 * @param string $message
	 * @param ITrigger $trigger
	 */
	public function __construct( $message, ITrigger $trigger ) {
		$this->message = $message;
		$this->trigger = $trigger;

		parent::__construct( $this->getExceptionMessage(), 500 );
	}

	/**
	 * @return string
	 */
	protected function getExceptionMessage() {
		$exceptionMessage = 'Workflow execution exception: %s, for trigger %s';

		return sprintf(
			$exceptionMessage,
			$this->message, $this->trigger->getId()
		);
	}
}

<?php

namespace MediaWiki\Extension\Workflows\Decision;

use Exception;
use MediaWiki\Extension\Workflows\Definition\Element\Gateway;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use MediaWiki\Extension\Workflows\IDecision;

class DataBasedDecision implements IDecision {
	/** @var Gateway */
	private $gateway;

	public static function factory( Gateway $gateway ) {
		return new static( $gateway );
	}

	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * @inheritDoc
	 */
	public function decideFlow( $data, WorkflowDefinition $process ): string {
		if ( !$this->gateway->getName() || !isset( $data[$this->gateway->getName()] ) ) {
			throw new Exception( 'Invalid gateway definition: Data is missing' );
		}

		$value = $data[$this->gateway->getName()];
		foreach ( $this->gateway->getOutgoing() as $flowRef ) {
			$flow = $process->getElementById( $flowRef );
			if ( $flow->getElementName() === 'sequenceFlow' && $flow->getName() === $value ) {
				return $flow->getId();
			}
		}

		throw new Exception(
			'Decision object failed to provide the decision: Data does not match any of the branches'
		);
	}
}

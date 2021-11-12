<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

class SequenceFlow extends Element {
	/** @var string */
	private $sourceRef;
	/** @var string */
	private $targetRef;

	/**
	 * @param string $id
	 * @param string $sourceRef
	 * @param string $targetRef
	 * @param string|null $name
	 */
	public function __construct( $id, $sourceRef, $targetRef, $name = null ) {
		parent::__construct( $id, $name );

		$this->sourceRef = $sourceRef;
		$this->targetRef = $targetRef;
	}

	/**
	 * @return string
	 */
	public function getSourceRef(): string {
		return $this->sourceRef;
	}

	/**
	 * @return string
	 */
	public function getTargetRef(): string {
		return $this->targetRef;
	}

	public function getElementName(): string {
		return 'sequenceFlow';
	}
}

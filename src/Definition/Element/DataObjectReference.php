<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

class DataObjectReference extends Element {
	/** @var string */
	private $dataObjectRef;

	public function __construct( $id, $dataObjectRef, $name = null ) {
		parent::__construct( $id, $name );

		$this->dataObjectRef = $dataObjectRef;
	}

	public function getDataObjectRef(): string {
		return $this->dataObjectRef;
	}

	public function getElementName(): string {
		return 'dataObjectReference';
	}
}

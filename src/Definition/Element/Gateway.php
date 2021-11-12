<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

class Gateway extends Element {
	/** @var array */
	protected $data = [];
	/** @var bool */
	protected $isCollection;
	/** @var string */
	protected $gatewayType;
	/** @var array */
	protected $extensionElements;

	public function __construct(
		$id, $incoming, $outgoing, $type, $name = null, $extensionElements = []
	) {
		parent::__construct( $id, $name, $incoming, $outgoing );

		$this->gatewayType = $type;
		$this->extensionElements = $extensionElements;
	}

	public function getElementName(): string {
		return $this->gatewayType;
	}

	public function getExtensionElements(): array {
		return $this->extensionElements;
	}
}

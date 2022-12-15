<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

use MediaWiki\Extension\Workflows\Definition\IElement;

abstract class Element implements IElement {
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string|null */
	protected $incoming;
	/** @var array|null */
	protected $outgoing;

	public function __construct( $id, $name, $incoming = [], $outgoing = [] ) {
		$this->id = $id;
		$this->name = $name;
		$this->incoming = $incoming;
		$this->outgoing = $outgoing;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getIncoming(): ?array {
		return $this->incoming;
	}

	public function getOutgoing(): array {
		return $this->outgoing;
	}

	public function getExtensionElements(): array {
		// Not supported ATM for most elements
		return [];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'incoming' => $this->incoming,
			'outgoing' => $this->outgoing,
			'elementName' => $this->getElementName(),
		];
	}
}

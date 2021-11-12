<?php

namespace MediaWiki\Extension\Workflows\Definition;

use JsonSerializable;

interface IElement extends JsonSerializable {

	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @return string|null
	 */
	public function getName(): ?string;

	/**
	 * Get incoming flow references
	 *
	 * @return array|null if StartEvent
	 */
	public function getIncoming(): ?array;

	/**
	 * Get outgoing flow references
	 *
	 * @return array|null if EndEvent
	 */
	public function getOutgoing(): ?array;

	/**
	 * Get the element name as its specified in definition
	 *
	 * @return string
	 */
	public function getElementName(): string;

	/**
	 * @return array
	 */
	public function getExtensionElements(): array;
}

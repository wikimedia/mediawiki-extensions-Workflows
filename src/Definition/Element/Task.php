<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

use MediaWiki\Extension\Workflows\Definition\ITask;

class Task extends Element implements ITask {
	/** @var array */
	protected $dataProperties;
	/** @var array|null */
	protected $dataInputAssociations;
	/** @var array|null */
	protected $dataOutputAssociations;
	/** @var string */
	protected $taskType;
	/** @var array */
	protected $extensionElements;
	/** @var bool */
	protected $isLooping;
	/** @var array|null */
	protected $multiInstanceCharacteristics;

	public function __construct(
		$id, $name, $incoming, $outgoing, $type, $properties = [],
		$dataInput = null, $dataOutput = null, $extensionElements = [], $isLooping = false,
		$multiInstanceCharacteristics = null
	) {
		parent::__construct( $id, $name, $incoming, $outgoing );

		$this->taskType = $type;
		$this->dataProperties = $properties;
		$this->dataInputAssociations = $dataInput;
		$this->dataOutputAssociations = $dataOutput;
		$this->extensionElements = $extensionElements;
		$this->isLooping = (bool)$isLooping;
		$this->multiInstanceCharacteristics = $multiInstanceCharacteristics;
	}

	public function getDataProperties(): ?array {
		return $this->dataProperties;
	}

	public function getInputDataAssociations(): ?array {
		return $this->dataInputAssociations;
	}

	public function getOutputDataAssociations(): ?array {
		return $this->dataOutputAssociations;
	}

	public function getElementName(): string {
		return $this->taskType;
	}

	public function getExtensionElements(): array {
		return $this->extensionElements;
	}

	public function isLooping(): bool {
		return $this->isLooping;
	}

	/**
	 * @return array|null
	 */
	public function getMultiInstanceCharacteristics(): ?array {
		return $this->multiInstanceCharacteristics;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_merge( parent::jsonSerialize(), [
			'properties' => $this->dataProperties,
			'dataInputAssociation' => $this->dataInputAssociations,
			'dataOutputAssociation' => $this->dataOutputAssociations,
			'extensionElements' => $this->getExtensionElements(),
			'isLooping' => $this->isLooping,
			'multiInstanceCharacteristics' => $this->multiInstanceCharacteristics,
		] );
	}
}

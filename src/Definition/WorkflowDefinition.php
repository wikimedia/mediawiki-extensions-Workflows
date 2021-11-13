<?php

namespace MediaWiki\Extension\Workflows\Definition;

use MediaWiki\Extension\Workflows\Definition\Element\DataObjectReference;

class WorkflowDefinition {

	/** @var null */
	private $id;
	/** @var DefinitionSource */
	private $source;
	/** @var DefinitionContext */
	private $context;
	/** @var array */
	private $index = [];
	/** @var IElement[] */
	private $elements = [];

	/**
	 * @param string $id
	 * @param DefinitionSource $definitionSource
	 * @param DefinitionContext|null $context
	 * @param array|null $elements
	 * @return static
	 */
	public static function factory(
		$id, DefinitionSource $definitionSource, DefinitionContext $context = null, $elements = []
	) {
		if ( !$context ) {
			$context = new DefinitionContext();
		}
		$process = new static( $id, $definitionSource, $context );

		foreach ( $elements as $element ) {
			$process->addElement( $element );
		}

		return $process;
	}

	/**
	 * @param string $id
	 * @param DefinitionSource $definitionSource
	 * @param DefinitionContext|null $context
	 */
	public function __construct(
		$id, DefinitionSource $definitionSource, DefinitionContext $context
	) {
		$this->id = $id;
		$this->source = $definitionSource;
		$this->context = $context;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return DefinitionContext
	 */
	public function getContext(): DefinitionContext {
		return $this->context;
	}

	/**
	 * @param IElement $element
	 */
	public function addElement( IElement $element ) {
		$this->elements[$element->getId()] = $element;
		$elName = $element->getElementName();
		if ( !isset( $this->index[$elName] ) ) {
			$this->index[$elName] = [];
		}
		$this->index[$elName][] = $element->getId();
	}

	/**
	 * @return IElement[]
	 */
	public function getElements(): array {
		return $this->elements;
	}

	/**
	 * @return DefinitionSource
	 */
	public function getSource(): DefinitionSource {
		return $this->source;
	}

	/**
	 * @param string $id
	 * @return IElement|null if no such ID exists
	 */
	public function getElementById( $id ): ?IElement {
		if ( isset( $this->elements[$id] ) ) {
			return $this->elements[$id];
		}

		return null;
	}

	/**
	 * Get all elements of particular type
	 *
	 * @param string $type
	 * @return IElement[]|null if such type is not found
	 */
	public function getElementsOfType( $type ): ?array {
		if ( !isset( $this->index[$type] ) ) {
			return null;
		}

		$index = $this->index[$type];
		return array_filter( $this->elements, static function ( $key ) use ( $index ) {
			return in_array( $key, $index );
		}, ARRAY_FILTER_USE_KEY );
	}

	/**
	 * @param string $referenceId
	 * @return IDataObject|string|null
	 */
	public function getDataObjectByReference( $referenceId ): ?IElement {
		$refObject = $this->getDataObjectReferenceObject( $referenceId );
		if ( !$refObject ) {
			return null;
		}

		return $this->getElementById( $refObject->getDataObjectRef() );
	}

	/**
	 * @param string $id
	 * @return DataObjectReference|null
	 */
	public function getDataObjectReferenceObject( $id ): ?DataObjectReference {
		if ( !isset( $this->elements[$id] ) ) {
			return null;
		}
		$refObject = $this->elements[$id];
		if ( !$refObject instanceof DataObjectReference ) {
			return null;
		}

		return $refObject;
	}

	/**
	 * @param string $id
	 * @param array $data
	 */
	public function updateDataForRef( $id, array $data ) {
		$this->elements[$id]->setData( $data );
	}

	public function setContextData( $data = [] ) {
		foreach ( $this->context->getItemKeys() as $key ) {
			if ( isset( $data[$key] ) ) {
				$this->context->setItem( $key, $data[$key] );
			}
		}
	}
}

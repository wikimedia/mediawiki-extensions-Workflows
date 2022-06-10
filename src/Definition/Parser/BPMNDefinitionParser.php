<?php

namespace MediaWiki\Extension\Workflows\Definition\Parser;

use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Element\DataObject;
use MediaWiki\Extension\Workflows\Definition\Element\DataObjectReference;
use MediaWiki\Extension\Workflows\Definition\Element\EndEvent;
use MediaWiki\Extension\Workflows\Definition\Element\Gateway;
use MediaWiki\Extension\Workflows\Definition\Element\SequenceFlow;
use MediaWiki\Extension\Workflows\Definition\Element\StartEvent;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\Definition\IDefinitionParser;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use SimpleXMLElement;

class BPMNDefinitionParser implements IDefinitionParser {
	/** @var DefinitionSource */
	private $source;
	/** @var array */
	private $namespaces = [
		'bpmn2' => null,
		'bpmn' => null,
		'wf' => null
	];

	/** @var null */
	private $process = null;

	public function __construct( DefinitionSource $source ) {
		$this->source = $source;
	}

	/**
	 * @param string $input
	 * @return WorkflowDefinition
	 * @throws \Exception
	 */
	public function parse( $input ): WorkflowDefinition {
		$xml = $this->getXML( $input );
		$this->getNamespacesFromXML( $xml );
		$this->parseChildren( $xml );
		$this->parseChildren( $xml, 'bpmn' );

		return $this->process;
	}

	private function getXML( $content ) {
		return simplexml_load_string( $content );
	}

	/**
	 * Look into the namespaces declared in XML itself
	 * and override predefined values
	 *
	 * @param SimpleXMLElement $xml
	 * @return void
	 */
	private function getNamespacesFromXML( SimpleXMLElement $xml ) {
		$namespaces = $xml->getNamespaces( true );
		foreach ( $this->namespaces as $key => $value ) {
			if ( isset( $namespaces[$key] ) ) {
				$this->namespaces[$key] = $namespaces[$key];
			}
		}
	}

	private function parseChildren( SimpleXMLElement $xml, $ns = 'bpmn2' ) {
		foreach ( $xml->children( $this->getNamespace( $ns ) ) as $child ) {
			$name = $child->getName();
			if ( $name === 'process' ) {
				$extensionElements = $this->getExtensionElementsData( $child );
				$data = [];
				if ( isset( $extensionElements['context'] ) ) {
					$data = $extensionElements['context'];
				}
				$processContext = new DefinitionContext( $data );
				$this->process = WorkflowDefinition::factory(
					$this->getAttribute( $child, 'id' ), $this->source, $processContext
				);
				$this->parseChildren( $child, $ns );
			} else {
				if ( !$this->process ) {
					throw new \Exception( "Definition parse failed: No process found as first element" );
				}
				switch ( $child->getName() ) {
					case 'startEvent':
						$this->process->addElement(
							$this->parseStartEvent( $child )
						);
						break;
					case 'userTask':
					case 'serviceTask':
					case 'task':
						$this->process->addElement(
							$this->parseTask( $child, $child->getName() )
						);
						break;
					case 'dataObjectReference':
						$this->process->addElement(
							$this->parseDataObjectReference( $child )
						);
						break;
					case 'dataObject':
						$this->process->addElement(
							$this->parseDataObject( $child )
						);
						break;
					case 'sequenceFlow':
						$this->process->addElement(
							$this->parseSequenceFlow( $child )
						);
						break;
					case 'exclusiveGateway':
					case 'parallelGateway':
						$this->process->addElement(
							$this->parseGateway( $child, $child->getName() )
						);
						break;
					case 'endEvent':
						$this->process->addElement(
							$this->parseEndEvent( $child )
						);
				}
			}
		}
	}

	private function getNamespace( $ns ): string {
		if ( isset( $this->namespaces[$ns] ) ) {
			return $this->namespaces[$ns];
		}

		return '';
	}

	private function getAttribute( SimpleXMLElement $child, $attribute, $ns = null ): string {
		if ( $ns && isset( $this->namespaces[$ns] ) ) {
			$attributes = $child->attributes( $this->namespaces[$ns] );
		} else {
			$xpath = $ns !== null ? "@$ns:$attribute" : "@$attribute";
			$attributes = $child->xpath( $xpath );
			if ( count( $attributes ) > 0 ) {
				$attributes = $attributes[0];
			} else {
				return '';
			}
		}

		foreach ( $attributes as $key => $value ) {
			if ( $key === $attribute ) {
				return $value;
			}
		}

		return '';
	}

	private function parseStartEvent( SimpleXMLElement $child ) {
		return new StartEvent(
			$this->getAttribute( $child, 'id' ),
			$this->getAttribute( $child, 'name' ),
			(array)$child->{"outgoing"}
		);
	}

	private function parseTask( SimpleXMLElement $child, $type ) {
		$properties = [];
		$internalProperties = [];
		$propertyValidators = [];
		foreach ( $child->property as $key => $property ) {
			$propertyName = $this->getAttribute( $property, 'name' );
			if ( empty( trim( $propertyName ) ) ) {
				continue;
			}
			$properties[$propertyName] = $this->convertValue(
				$this->getAttribute( $property, 'default' ) ?: (string)$property
			);
			if ( !empty( $this->getAttribute( $property, 'validation' ) ) ) {
				$validators = $this->getAttribute( $property, 'validation' );
				$validators = explode( ',', $validators );
				$propertyValidators[$propertyName] = $validators;
			}
			$internal = (bool)$this->getAttribute( $property, 'internal' );
			if ( $internal ) {
				$internalProperties[] = $propertyName;
			}
		}

		$extensionElements = $this->getExtensionElementsData( $child ) ?? [];
		$extensionElements['_internal_properties'] = $internalProperties;
		$extensionElements['_property_validators'] = $propertyValidators;

		return new Task(
			$this->getAttribute( $child, 'id' ),
			$this->getAttribute( $child, 'name' ),
			(array)$child->{"incoming"},
			(array)$child->{"outgoing"},
			$type,
			$properties,
			$this->parseDataAssociation( $child, 'dataInputAssociation' ),
			$this->parseDataAssociation( $child, 'dataOutputAssociation' ),
			$extensionElements,
			(bool)$child->{"standardLoopCharacteristics"},
			$this->parseTaskMultiInstanceCharacteristics( $child )
		);
	}

	private function parseTaskMultiInstanceCharacteristics( SimpleXMLElement $child ): ?array {
		/** @var SimpleXMLElement $el */
		$el = $child->{"multiInstanceLoopCharacteristics"};
		if ( $el->getName() === 'multiInstanceLoopCharacteristics' ) {
			$sequential = false;
			if ( $this->getAttribute( $el, 'isSequential' ) === 'true' ) {
				$sequential = true;
			}
			$extensionElements = $el->extensionElements ?? null;
			$props = [];
			if ( $extensionElements !== null ) {
				foreach ( $extensionElements->children( $this->getNamespace( 'wf' ) ) as $prop ) {
					if ( $prop->getName() !== 'multiInstanceProperty' ) {
						continue;
					}
					$props[] = [
						'source' => $this->getAttribute( $prop, 'source' ),
						'target' => $this->getAttribute( $prop, 'target' ),
					];
				}
			}

			if ( empty( $props ) ) {
				throw new \Exception(
					"Tasks with multi-instance characteristics must have at least one wf:multiInstanceProperty defined"
				);
			}
			return [
				'props' => $props,
				'isSequential' => $sequential,
			];
		}

		return null;
	}

	/**
	 * @param SimpleXMLElement $child
	 * @return array|null if no extensionElements exist
	 */
	private function getExtensionElementsData( SimpleXMLElement $child ): ?array {
		if ( isset( $child->extensionElements ) ) {
			return $this->parseWfData( $child->extensionElements );
		}

		return null;
	}

	private function parseWfData( $element, $usedKeys = [] ) {
		$data = [];
		foreach ( $element->children( $this->getNamespace( 'wf' ) ) as $wf ) {
			$name = $this->getAttribute( $wf, 'name' ) ?: $wf->getName();
			if ( isset( $data[$name] ) && !in_array( $name, $usedKeys ) ) {
				$data[$name] = [ $data[$name] ];
				$usedKeys[] = $name;
			}
			if ( isset( $data[$name] ) && is_array( $data[$name] ) && in_array( $name, $usedKeys ) ) {
				$data[$name][] = $this->getWfValue( $wf, $usedKeys );
			} else {
				$data[$name] = $this->getWfValue( $wf, $usedKeys );
			}
		}

		return $data;
	}

	/**
	 * @param SimpleXMLElement $wf
	 * @param array $usedKeys
	 * @return array|string
	 */
	private function getWfValue( $wf, $usedKeys ) {
		if ( count( $wf->children( $this->getNamespace( 'wf' ) ) ) === 0 ) {
			return (string)$wf;
		} else {
			return $this->parseWfData( $wf, $usedKeys );
		}
	}

	private function parseDataAssociation( $child, $type ) {
		$data = [];
		foreach ( $child->$type as $item ) {
			$data[$this->getAttribute( $item, 'id' )] =
				$this->processDataAssociation( $item, 'bpmn' ) +
				$this->processDataAssociation( $item, 'bpmn2' );
		}

		return $data;
	}

	private function processDataAssociation( $child, $ns ) {
		$associationItems = [];

		$extensionElements = $this->getExtensionElementsData( $child );
		if ( isset( $extensionElements['inputDataKey'] ) ) {
			$associationItems['inputDataKey'] = $extensionElements['inputDataKey'];
		}
		foreach ( $child->children( $this->getNamespace( $ns ) ) as $subItem ) {
			$associationItems[$subItem->getName()] = (string)$subItem;
		}

		return $associationItems;
	}

	private function parseDataObjectReference( SimpleXMLElement $child ) {
		return new DataObjectReference(
			$this->getAttribute( $child, 'id' ),
			$this->getAttribute( $child, 'dataObjectRef' ),
			$this->getAttribute( $child, 'name' )
		);
	}

	private function parseDataObject( SimpleXMLElement $child ) {
		$extensionData = $this->getExtensionElementsData( $child ) ?? [];
		$data = [];
		if ( isset( $extensionData['data'] ) && is_array( $extensionData['data'] ) ) {
			$data = $extensionData['data'];
		}

		return new DataObject(
			$this->getAttribute( $child, 'id' ),
			$data,
			$this->getAttribute( $child, 'isCollection' ),
			$this->getAttribute( $child, 'name' )
		);
	}

	private function parseSequenceFlow( SimpleXMLElement $child ) {
		return new SequenceFlow(
			$this->getAttribute( $child, 'id' ),
			$this->getAttribute( $child, 'sourceRef' ),
			$this->getAttribute( $child, 'targetRef' ),
			$this->getAttribute( $child, 'name' )
		);
	}

	private function parseGateway( SimpleXMLElement $child, $type ) {
		return new Gateway(
			$this->getAttribute( $child, 'id' ),
			(array)$child->incoming,
			(array)$child->outgoing,
			$type,
			$this->getAttribute( $child, 'name' ),
			$this->getExtensionElementsData( $child ) ?? []
		);
	}

	private function parseEndEvent( SimpleXMLElement $child ) {
		return new EndEvent(
			$this->getAttribute( $child, 'id' ),
			$this->getAttribute( $child, 'name' ),
			(array)$child->{"incoming"}
		);
	}

	/**
	 * Basic conversion to correct types
	 *
	 * @param string $value
	 * @return string
	 */
	private function convertValue( $value ) {
		if ( $value === '' ) {
			return '';
		}
		if ( in_array( strtolower( $value ), [ 'true', 'false' ] ) ) {
			return $value === 'true';
		}
		if ( is_numeric( $value ) ) {
			return $value + 0;
		}

		return $value;
	}

}

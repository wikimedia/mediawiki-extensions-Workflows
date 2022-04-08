<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\PropertyValidator\IPropertyValidator;
use Wikimedia\ObjectFactory\ObjectFactory;

class PropertyValidatorFactory {
	/** @var array */
	private $registry;
	/** @var ObjectFactory */
	private $objectFactory;
	/** @var array */
	private $validators = [];

	/**
	 * @param array $registry
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct( $registry, $objectFactory ) {
		$this->registry = $registry;
		$this->objectFactory = $objectFactory;
	}

	public function getValidator( $name ) {
		if ( !isset( $this->validators[$name] ) ) {
			$this->instantiate( $name );
		}

		return $this->validators[$name] ?? null;
	}

	private function instantiate( $name ) {
		if ( !isset( $this->registry[$name] ) ) {
			return;
		}
		$spec = $this->registry[$name];
		if ( !is_array( $spec ) ) {
			return;
		}
		$instance = $this->objectFactory->createObject( $spec );
		if ( !$instance instanceof IPropertyValidator ) {
			return;
		}
		$this->validators[$name] = $instance;
	}
}

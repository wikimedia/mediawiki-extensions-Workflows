<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use MediaWiki\MediaWikiServices;
use Wikimedia\ObjectFactory\ObjectFactory;

class DefinitionRepositoryFactory {
	/** @var array */
	private $registry;
	/** @var ObjectFactory */
	private $objectFactory;

	public function __construct( array $registry, ObjectFactory $objectFactory ) {
		$this->registry = $registry;
		$this->objectFactory = $objectFactory;
	}

	public function getRepository( $name, $params = [] ) {
		// TEMP: If we use OF instance that was injected, it will report "Container disabled!"
		// when it tries to inject services into objects it creates, dont really know why
		$this->objectFactory = MediaWikiServices::getInstance()->getObjectFactory();

		if ( isset( $this->registry[$name] ) ) {
			$spec = $this->registry[$name];
			if ( is_callable( $spec ) ) {
				$instance = $this->objectFactory->createObject( [
					'factory' => $spec,
					'args' => $params
				] );
			} else {
				$spec['args'] = $params;
				$instance = $this->objectFactory->createObject( $spec );
			}

			if ( $instance instanceof IDefinitionRepository ) {
				return $instance;
			}
		}

		return null;
	}

	public function getRepositoryKeys() {
		return array_keys( $this->registry );
	}
}

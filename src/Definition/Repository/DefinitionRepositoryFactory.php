<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use MediaWiki\MediaWikiServices;
use Wikimedia\ObjectFactory;

class DefinitionRepositoryFactory {
	/** @var array */
	private $registry;
	/** @var MediaWikiServices */
	private $services;

	public function __construct( array $registry, MediaWikiServices $services ) {
		$this->registry = $registry;
		$this->services = $services;
	}

	public function getRepository( $name, $params = [] ) {
		if ( isset( $this->registry[$name] ) ) {
			$callback = $this->registry[$name];
			if ( is_callable( $callback ) ) {
				$instance = ObjectFactory::getObjectFromSpec( [
					'factory' => $callback,
					'args' => [ $this->services, ...$params ]
				] );
				if ( $instance instanceof IDefinitionRepository ) {
					return $instance;
				}
			}
		}

		return null;
	}

	public function getRepositoryKeys() {
		return array_keys( $this->registry );
	}
}

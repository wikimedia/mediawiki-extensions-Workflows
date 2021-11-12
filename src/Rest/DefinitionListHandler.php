<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Rest\Handler;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;

class DefinitionListHandler extends Handler {
	/** @var DefinitionRepositoryFactory */
	private $repositoryFactory;

	public function __construct( DefinitionRepositoryFactory $definitionRepositoryFactory ) {
		$this->repositoryFactory = $definitionRepositoryFactory;
	}

	public function execute() {
		$res = [];
		foreach ( $this->repositoryFactory->getRepositoryKeys() as $key ) {
			$repository = $this->repositoryFactory->getRepository( $key );
			$definitions = $repository->getAllKeys();
			$res[$key] = [
				'definitions' => array_map( function( $item ) use ( $repository ) {
					return [
						'key' => $item,
						'title' => $repository->getDefinitionDisplayTitle( $item ),
						'desc' => $repository->getDefinitionDescription( $item ),
					];
				}, $definitions )
			];
		}

		return $this->getResponseFactory()->createJson( $res );
	}
}

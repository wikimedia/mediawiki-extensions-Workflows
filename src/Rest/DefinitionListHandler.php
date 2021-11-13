<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Rest\Handler;

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
				'definitions' => array_map( static function ( $item ) use ( $repository ) {
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

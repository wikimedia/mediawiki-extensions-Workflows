<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use Wikimedia\ParamValidator\ParamValidator;

class DefinitionDetailsHandler extends Handler {
	/** @var DefinitionRepositoryFactory */
	private $repositoryFactory;

	public function __construct( DefinitionRepositoryFactory $definitionRepositoryFactory ) {
		$this->repositoryFactory = $definitionRepositoryFactory;
	}

	public function execute() {
		$params = $this->getValidatedParams();

		$repo = $this->repositoryFactory->getRepository( $params['repo'] );
		if ( !$repo instanceof IDefinitionRepository ) {
			throw new HttpException( 'Invalid repository name: ' . $params['repo'] );
		}

		return $this->getResponseFactory()->createJson( [
			'key' => $params['definition'],
			'title' => $repo->getDefinitionDisplayTitle( $params['definition'] ),
			'desc' => $repo->getDefinitionDescription( $params['definition'] ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'repo' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'definition' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}
}

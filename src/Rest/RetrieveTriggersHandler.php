<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Rest\HttpException;
use Wikimedia\ParamValidator\ParamValidator;

class RetrieveTriggersHandler extends TriggerHandler {
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$triggers = $this->getTriggerRepo()->getAll( TriggerRepo::GROUP_BY_KEY );

		$params = $this->getValidatedParams();
		if ( $params['key'] === '*' ) {
			return $this->getResponseFactory()->createJson( $triggers );
		}
		if ( !isset( $triggers[$params['key'] ] ) ) {
			throw new HttpException( 'Trigger ' . $params['key'] . ' not found', 404 );
		}
		return $this->getResponseFactory()->createJson( $triggers[$params['key']]->jsonSerialize() );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'key' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}
}

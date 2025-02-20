<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use Wikimedia\ParamValidator\ParamValidator;

class DeleteTriggerHandler extends TriggerHandler {
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$user = RequestContext::getMain()->getUser();
		$isAdmin = $this->assertUserIsAdmin( $user );
		if ( !$isAdmin ) {
			throw new HttpException( 'permissiondenied', 401 );
		}
		$key = $this->getValidatedParams()['key'];
		return $this->getResponseFactory()->createJson(
			[ 'success' => $this->getTriggerRepo()->deleteTrigger( $key, $user ) ]
		);
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

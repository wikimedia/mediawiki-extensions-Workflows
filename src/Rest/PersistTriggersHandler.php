<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

class PersistTriggersHandler extends TriggerHandler {

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$body = $this->getValidatedBody()['data'];
		$this->assertUserIsAdmin();
		if ( !is_array( $body ) ) {
			throw new HttpException( 'Body data must be an array', 400 );
		}

		return $this->getResponseFactory()->createJson( [
			'success' => $this->getTriggerRepo()->setContent( $body ),
		] );
	}

	/**
	 * @param string $contentType
	 * @return JsonBodyValidator
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType === 'application/json' ) {
			return new JsonBodyValidator( [
				'data' => [
					static::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
				]
			] );
		}
		throw new HttpException( 'Content must be of type application/json', 400 );
	}
}

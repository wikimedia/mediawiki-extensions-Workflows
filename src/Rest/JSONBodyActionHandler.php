<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

abstract class JSONBodyActionHandler extends ActionHandler {

	/**
	 * @param string|null $key Piece of data to retrieve
	 * @param array $default
	 * @return array|mixed
	 */
	protected function getBodyData( $key = null, $default = [] ) {
		$body = $this->getValidatedBody();
		if ( isset( $body['data'] ) ) {
			if ( $key ) {
				if ( isset( $body['data'][$key] ) ) {
					return $body['data'][$key];
				} else {
					return $default;
				}
			}
			return $body['data'];
		}

		return $default;
	}

	/**
	 * @param string $contentType
	 * @return JsonBodyValidator
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType === 'application/json' ) {
			return new JsonBodyValidator( [
				'data' => [
					ParamValidator::PARAM_REQUIRED => false,
				]
			] );
		}
	}
}

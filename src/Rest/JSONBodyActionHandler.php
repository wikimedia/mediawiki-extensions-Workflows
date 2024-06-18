<?php

namespace MediaWiki\Extension\Workflows\Rest;

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

	public function getBodyParamSettings(): array {
		return [
			'data' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_REQUIRED => false,
			]
		];
	}
}

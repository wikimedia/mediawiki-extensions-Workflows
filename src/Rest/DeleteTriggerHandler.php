<?php

namespace MediaWiki\Extension\Workflows\Rest;

use Wikimedia\ParamValidator\ParamValidator;

class DeleteTriggerHandler extends TriggerHandler {
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertUserIsAdmin();
		$key = $this->getValidatedParams()['key'];
		return $this->getResponseFactory()->createJson(
			[ 'success' => $this->getTriggerRepo()->deleteTrigger( $key ) ]
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

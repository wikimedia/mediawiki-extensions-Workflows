<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Rest\HttpException;
use Title;
use TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class RetrieveTriggersHandler extends TriggerHandler {
	/** @var TitleFactory */
	private $titleFactory;
	/** @var bool */
	private $listForType;

	public function __construct(
		TriggerRepo $triggerRepo, ?TitleFactory $titleFactory = null, bool $listForType = false
	) {
		parent::__construct( $triggerRepo );
		$this->titleFactory = $titleFactory;
		$this->listForType = $listForType;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->getValidatedParams();
		$key = $params['key'];
		if ( $this->listForType ) {
			return $this->getResponseFactory()->createJson(
				$this->getTriggersOfType( $params['page'], $key )
			);
		}

		$triggers = $this->getTriggerRepo()->getAll();
		if ( $key === '*' ) {
			return $this->getResponseFactory()->createJson( $triggers );
		}

		if ( !isset( $triggers[$key] ) ) {
			throw new HttpException( "Trigger $key not found", 404 );
		}
		return $this->getResponseFactory()->createJson( $triggers[$key]->jsonSerialize() );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		$params = [
			'key' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
		if ( $this->listForType ) {
			$params['page'] = [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
			];
		}

		return $params;
	}

	/**
	 * @param string|null $page
	 * @param string $key
	 *
	 * @return array
	 */
	private function getTriggersOfType( ?string $page, string $key ): array {
		$triggers = $this->getTriggerRepo()->getAllOfType( $key );

		if ( $page ) {
			$title = $this->titleFactory->newFromDBkey( $page );
			if ( !( $title instanceof Title ) || !$title->exists() ) {
				return [];
			}

			$allowed = [];
			foreach ( $triggers as $trigger ) {
				if ( !( $trigger instanceof PageRelatedTrigger ) ) {
					continue;
				}
				if ( !$trigger->isActive() ) {
					continue;
				}
				$trigger->setTitle( $title );
				if ( $trigger->shouldTrigger() ) {
					$allowed[$trigger->getId()] = $trigger;
				}
			}
			return $allowed;
		}

		return $triggers;
	}
}

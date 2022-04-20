<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Extension\Workflows\TriggerRepo;
use Title;
use TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class RetrieveManualTriggersForPageHandler extends TriggerHandler {
	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param TriggerRepo $triggerRepo
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TriggerRepo $triggerRepo, TitleFactory $titleFactory ) {
		parent::__construct( $triggerRepo );
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->getValidatedParams();
		$title = $this->titleFactory->newFromDBkey( $params['page'] );
		if ( !( $title instanceof Title ) || !$title->exists() ) {
			return $this->getResponseFactory()->createJson( [] );
		}
		$triggers = $this->getTriggerRepo()->getAllOfType( 'manual' );

		$allowed = [];
		/** @var PageRelatedTrigger $trigger */
		foreach ( $triggers as $trigger ) {
			if ( !$trigger->isActive() ) {
				continue;
			}
			$trigger->setTitle( $title );
			if ( $trigger->shouldTrigger() ) {
				$allowed[$trigger->getId()] = $trigger;
			}
		}
		return $this->getResponseFactory()->createJson( $allowed );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'page' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}

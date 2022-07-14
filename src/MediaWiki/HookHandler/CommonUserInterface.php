<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\HookHandler;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\SpecialPage\SpecialPageFactory;
use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;
use Title;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {
	/** @var SpecialPageFactory */
	private $spf;
	/** @var TriggerRepo */
	private $triggerRepo;

	/**
	 * @param SpecialPageFactory $spf
	 * @param TriggerRepo $triggerRepo
	 */
	public function __construct( SpecialPageFactory $spf, TriggerRepo $triggerRepo ) {
		$this->spf = $spf;
		$this->triggerRepo = $triggerRepo;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$specialOverview = $this->spf->getTitleForAlias( 'WorkflowsOverview' );
		$registry->register(
			'GlobalActionsTools',
			[
				'bs-special-workflows' => [
					'factory' => static function () use ( $specialOverview ) {
						return new RestrictedTextLink( [
							'id' => 'ga-bs-workflows',
							'href' => $specialOverview->getLocalURL(),
							'text' => Message::newFromKey( 'workflowsoverview' ),
							'title' => Message::newFromKey( 'workflows-desc' ),
							'aria-label' => Message::newFromKey( 'workflowsoverview' ),
							'permissions' => [ 'workflows-view' ]
						] );
					}
				]
			]
		);

		$triggerPage = $this->triggerRepo->getTitle();
		if ( !( $triggerPage instanceof Title ) ) {
			return;
		}
		$registry->register(
			'GlobalActionsTools',
			[
				'bs-workflow-triggers' => [
					'factory' => static function () use ( $triggerPage ) {
						return new RestrictedTextLink( [
							'id' => 'bs-workflow-triggers',
							'href' => $triggerPage->getLocalURL(),
							'text' => Message::newFromKey( 'workflows-ui-trigger-page-title' ),
							'title' => Message::newFromKey( 'workflows-ui-trigger-page-desc' ),
							'aria-label' => Message::newFromKey( 'workflows-ui-trigger-page-title' ),
							'permissions' => [ 'workflows-admin' ]
						] );
					}
				]
			]
		);
	}
}

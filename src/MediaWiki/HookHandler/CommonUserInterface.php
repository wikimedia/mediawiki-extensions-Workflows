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
		$overviewEntry = [
			'bs-special-workflows' => [
				'factory' => static function () use ( $specialOverview ) {
					return new RestrictedTextLink( [
						'id' => 'ga-bs-workflows',
						'href' => $specialOverview->getLocalURL(),
						'text' => Message::newFromKey( 'workflows-global-action-overview' ),
						'title' => Message::newFromKey( 'workflows-global-action-overview-desc' ),
						'aria-label' => Message::newFromKey( 'workflows-global-action-overview' ),
						'permissions' => [ 'workflows-view' ]
					] );
				}
			]
		];
		// BlueSpiceDiscovery 4.4
		$registry->register( 'GlobalActionsOverview', $overviewEntry );

		// BlueSpiceDiscovery 4.3 b/c
		$registry->register( 'GlobalActionsTools', $overviewEntry );

		$triggerPage = $this->triggerRepo->getTitle();
		if ( !( $triggerPage instanceof Title ) ) {
			return;
		}
		$triggersEntry = [
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
		];

		// BlueSpiceDiscovery 4.4
		$registry->register( 'GlobalActionsEditing', $triggersEntry );

		// BlueSpiceDiscovery 4.3 b/c
		$registry->register( 'GlobalActionsTools', $triggersEntry );
	}
}

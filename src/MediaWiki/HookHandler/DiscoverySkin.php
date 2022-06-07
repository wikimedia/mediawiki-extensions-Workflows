<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\HookHandler;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;

class DiscoverySkin implements BlueSpiceDiscoveryTemplateDataProviderAfterInit {

	/**
	 *
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'actions_secondary', 'ca-wf_start' );
		$registry->unregister( 'toolbox', 'ca-wf_start' );

		$registry->unregister( 'actioncollection/actions', 'ca-wf_view_for_page' );
	}
}

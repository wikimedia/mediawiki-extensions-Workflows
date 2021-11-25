<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\HookHandler;

use MediaWiki\Extension\Workflows\MediaWiki\DiscoverySkin\GlobalActionsTool;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsTools',
			[
				'bs-special-workflows' => [
					'factory' => static function () {
						return new GlobalActionsTool();
					}
				]
			]
		);
	}
}

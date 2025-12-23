<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;

class AddMagic implements GetDoubleUnderscoreIDsHook {
	/**
	 * @inheritDoc
	 */
	public function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ): void {
		$doubleUnderscoreIDs[] = 'NOWORKFLOWEXECUTION';
	}
}

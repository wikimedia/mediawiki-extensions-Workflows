<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Hook\BeforePageDisplayHook;

class AddBootstrap implements BeforePageDisplayHook {

	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModules( "ext.workflows.bootstrap" );
	}
}

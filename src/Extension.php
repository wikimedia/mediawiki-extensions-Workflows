<?php

namespace MediaWiki\Extension\Workflows;

class Extension {
	public static function register() {
		\mwsInitComponents();

		// Shouldn't be zero
		$GLOBALS['wgReauthenticateTime']['SignWorkflowActivity'] = 2;
	}
}

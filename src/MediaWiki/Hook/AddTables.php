<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class AddTables implements LoadExtensionSchemaUpdatesHook {

	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'workflow_event',
			dirname( dirname( dirname( __DIR__ ) ) ) . '/db/workflows_event.sql'
		);

		$updater->addExtensionTable(
			'workflows_state',
			dirname( dirname( dirname( __DIR__ ) ) ) . '/db/workflows_state.sql'
		);

		return true;
	}
}

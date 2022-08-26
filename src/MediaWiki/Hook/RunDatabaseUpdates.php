<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\MediaWiki\Maintenance\CreateDefaultTriggersPage;
use MediaWiki\Extension\Workflows\MediaWiki\Maintenance\UpdateWorkflowState;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'workflow_event',
			dirname( dirname( dirname( __DIR__ ) ) ) . '/db/workflows_event.sql'
		);

		$updater->addExtensionTable(
			'workflows_state',
			dirname( dirname( dirname( __DIR__ ) ) ) . '/db/workflows_state.sql'
		);

		$updater->addExtensionField(
			'workflows_state',
			'wfs_started',
			dirname( dirname( dirname( __DIR__ ) ) ) . '/db/workflows_state_start_patch.sql'
		);

		$updater->addPostDatabaseUpdateMaintenance(
			CreateDefaultTriggersPage::class
		);

		$updater->addPostDatabaseUpdateMaintenance(
			UpdateWorkflowState::class
		);

		return true;
	}
}

<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\MediaWiki\Maintenance\CreateDefaultTriggersPage;
use MediaWiki\Extension\Workflows\MediaWiki\Maintenance\UpdateLegacyAssignees;
use MediaWiki\Extension\Workflows\MediaWiki\Maintenance\UpdateWorkflowState;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();
		$dir = dirname( __DIR__, 3 );

		$updater->addExtensionTable(
			'workflows_event',
			"$dir/db/$dbType/workflows_event.sql"
		);

		$updater->addExtensionTable(
			'workflows_state',
			"$dir/db/$dbType/workflows_state.sql"
		);

		$updater->addExtensionField(
			'workflows_state',
			'wfs_started',
			"$dir/db/$dbType/workflows_state_start_patch.sql"
		);

		$updater->addExtensionField(
			'workflows_state',
			'wfs_assignees',
			"$dir/db/$dbType/workflows_state_assignees_patch.sql"
		);

		$updater->addPostDatabaseUpdateMaintenance(
			CreateDefaultTriggersPage::class
		);

		$updater->addPostDatabaseUpdateMaintenance(
			UpdateWorkflowState::class
		);

		$updater->addPostDatabaseUpdateMaintenance(
			UpdateLegacyAssignees::class
		);
	}
}

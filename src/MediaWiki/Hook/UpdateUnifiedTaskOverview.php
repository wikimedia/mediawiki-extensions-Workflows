<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\User\User;

class UpdateUnifiedTaskOverview {

	public function onWorkflowsUpdateTask(
		ITaskDescriptor $descriptor,
		User $user,
		bool $isCompleted
	): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'UnifiedTaskOverview' ) ) {
			return;
		}
		MediaWikiServices::getInstance()->getService( 'UnifiedTaskOverview.TaskStore' )
			->updateTask( $descriptor, $user, $isCompleted );
	}

}

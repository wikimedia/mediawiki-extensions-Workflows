<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\User\User;

interface WorkflowsUpdateTaskHook {

	/**
	 * @param ITaskDescriptor $descriptor
	 * @param User $user
	 * @param bool $isCompleted
	 * @return void
	 */
	public function onWorkflowsUpdateTask(
		ITaskDescriptor $descriptor,
		User $user,
		bool $isCompleted
	): void;

}

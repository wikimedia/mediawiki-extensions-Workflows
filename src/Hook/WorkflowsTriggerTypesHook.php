<?php

namespace MediaWiki\Extension\Workflows\Hook;

interface WorkflowsTriggerTypesHook {

	/**
	 * @param array &$types
	 * @param array &$editors
	 * @return void
	 */
	public function onWorkflowsTriggerTypes( &$types, &$editors ): void;
}

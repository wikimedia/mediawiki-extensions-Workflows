<?php

namespace MediaWiki\Extension\Workflows\MediaWiki;

use MediaWiki\Extension\Workflows\Definition\Repository\WorkflowFileDefinitionRepository;

/**
 * @stable to implement
 * @ingroup Hooks
 */
interface WorkflowFileDefinitionRepositoryLoadHook {
	/**
	 * This hook is called during loading of the WorkflowFileDefinitionRepository
	 *
	 * @param WorkflowFileDefinitionRepository $repository
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public static function onWorkflowFileDefinitionRepositoryLoad(
		WorkflowFileDefinitionRepository $repository
	);
}

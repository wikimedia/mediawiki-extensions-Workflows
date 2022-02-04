<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook\MWStakeRunJobsTriggerRegisterHandlers;

use MediaWiki\Extension\Workflows\RunJobsTriggerHandler\ProcessWorkflows;

class AddBackgroundProcess {

	/**
	 *
	 * @param array &$handlers
	 * @return bool
	 */
	public static function callback( &$handlers ) {
		$handlers[ProcessWorkflows::HANDLER_KEY] = [
			'class' => ProcessWorkflows::class,
			'services' => [
				'WorkflowEventRepository', 'DefinitionRepositoryFactory',
				'MWStakeNotificationsNotifier', 'WorkflowTriggerRunner'
			]
		];

		return true;
	}
}

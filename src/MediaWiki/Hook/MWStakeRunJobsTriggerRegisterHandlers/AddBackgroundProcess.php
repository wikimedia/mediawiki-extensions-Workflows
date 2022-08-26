<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook\MWStakeRunJobsTriggerRegisterHandlers;

use MediaWiki\Extension\Workflows\RunJobsTriggerHandler\ProcessTimeBasedTriggers;
use MediaWiki\Extension\Workflows\RunJobsTriggerHandler\ProcessWorkflows;
use MediaWiki\Extension\Workflows\RunJobsTriggerHandler\SendDueDateProximityNotifications;

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
				'MWStakeNotificationsNotifier'
			]
		];
		$handlers[ProcessTimeBasedTriggers::HANDLER_KEY] = [
			'class' => ProcessTimeBasedTriggers::class,
			'services' => [ 'WorkflowTriggerRunner' ]
		];
		$handlers[SendDueDateProximityNotifications::HANDLER_KEY] = [
			'class' => SendDueDateProximityNotifications::class,
			'services' => [
				'WorkflowEventRepository', 'DefinitionRepositoryFactory',
				'MWStakeNotificationsNotifier'
			]
		];

		return true;
	}
}

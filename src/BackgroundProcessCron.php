<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Process\AbortExpired;
use MediaWiki\Extension\Workflows\Process\ProcessTimeBasedTriggers;
use MediaWiki\Extension\Workflows\Process\ProcessWorkflows;
use MediaWiki\Extension\Workflows\Process\SendDueDateProximityNotifications;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\ProcessManager\ManagedProcess;
use MWStake\MediaWiki\Component\WikiCron\WikiCronManager;

class BackgroundProcessCron {

	/**
	 * @return void
	 */
	public static function register(): void {
		if ( defined( 'MW_PHPUNIT_TEST' ) || defined( 'MW_QUIBBLE_CI' ) ) {
			return;
		}

		/** @var WikiCronManager $cronManager */
		$cronManager = MediaWikiServices::getInstance()->getService( 'MWStake.WikiCronManager' );

		// Interval: Once every hour
		$cronManager->registerCron( 'ext-workflows-process-workflows', '0 * * * *', new ManagedProcess( [
			'process-workflows' => [
				'class' => ProcessWorkflows::class,
				'services' => [
					'WorkflowEventRepository',
					'DefinitionRepositoryFactory',
					'MWStake.Notifier'
				],
			]
		] ) );

		// Interval: Daily at 01:00
		$cronManager->registerCron(
			'ext-workflows-send-due-date-proximity-notifications',
			'0 1 * * *',
			new ManagedProcess( [
				'send-due-date-proximity-notifications' => [
					'class' => SendDueDateProximityNotifications::class,
					'services' => [
						'WorkflowEventRepository',
						'DefinitionRepositoryFactory',
						'MWStake.Notifier'
					],
				]
			] )
		);

		// Interval: Daily at 01:00
		$cronManager->registerCron(
			'ext-workflows-abort-expired',
			'0 1 * * *',
			new ManagedProcess( [
				'abort-expired' => [
					'class' => AbortExpired::class,
					'services' => [
						'WorkflowEventRepository',
						'DefinitionRepositoryFactory',
						'MWStake.Notifier'
					],
				]
			] )
		);

		// Interval: Daily at 01:00
		$cronManager->registerCron(
			'ext-workflows-process-time-based-triggers',
			'0 1 * * *',
			new ManagedProcess( [
				'process-time-based-triggers' => [
					'class' => ProcessTimeBasedTriggers::class,
					'services' => [
						'WorkflowTriggerRunner'
					],
				]
			] )
		);
	}
}

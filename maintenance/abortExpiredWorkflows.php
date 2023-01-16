<?php

use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Util\AutoAborter;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class AbortExpiredWorkflows extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		/** @var WorkflowEventRepository $store */
		$store = MediaWikiServices::getInstance()->getService( 'WorkflowEventRepository' );
		/** @var DefinitionRepositoryFactory $definitionRepoFactory */
		$definitionRepoFactory = MediaWikiServices::getInstance()->getService( 'DefinitionRepositoryFactory' );
		$autoAborter = new AutoAborter( $store );

		$aborted = 0;
		$ids = $store->retrieveAllIds();
		$this->output( "Found " . count( $ids ) . " workflows\n" );
		foreach ( $ids as $id ) {
			$workflow = Workflow::newFromInstanceID( $id, $store, $definitionRepoFactory );
			if ( $autoAborter->abortIfExpired( $workflow ) ) {
				$aborted++;
			}
		}

		$this->output( "Aborted $aborted workflows\n" );
	}
}

$maintClass = AbortExpiredWorkflows::class;
require_once RUN_MAINTENANCE_IF_MAIN;

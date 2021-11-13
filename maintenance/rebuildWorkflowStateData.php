<?php

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class RebuildWorkflowStateData extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Rebuild workflow state store' );
	}

	public function execute() {
		/** @var WorkflowEventRepository $eventStore */
		$eventStore = MediaWikiServices::getInstance()->getService( 'WorkflowEventRepository' );
		/** @var WorkflowStateStore $stateStore */
		$stateStore = MediaWikiServices::getInstance()->getService( 'WorkflowsStateStore' );

		$eventStore->addReplayConsumer( $stateStore );

		$ids = $eventStore->retrieveAllIds();
		$this->output( 'Found ' . count( $ids ) . " workflows...\n" );
		$counter = 0;
		foreach ( $ids as $id ) {
			$counter++;
			if ( $counter % 100 === 0 ) {
				$this->output( "Processed $counter...\n" );
			}
			// Retrieving will recreate WorkflowStorage from events...
			// ... and fire each event for out state store to react to
			$eventStore->retrieve( $id );
		}

		$this->output( "Done\n" );
	}
}

$maintClass = RebuildWorkflowStateData::class;
require_once RUN_MAINTENANCE_IF_MAIN;

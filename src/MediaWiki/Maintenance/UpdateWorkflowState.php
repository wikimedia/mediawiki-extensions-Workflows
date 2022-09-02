<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Maintenance;

use LoggedUpdateMaintenance;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\MediaWikiServices;

class UpdateWorkflowState extends LoggedUpdateMaintenance {
	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		/** @var WorkflowEventRepository $eventStore */
		$eventStore = MediaWikiServices::getInstance()->getService( 'WorkflowEventRepository' );
		/** @var WorkflowStateStore $stateStore */
		$stateStore = MediaWikiServices::getInstance()->getService( 'WorkflowsStateStore' );

		$eventStore->addReplayConsumer( $stateStore );

		$ids = $eventStore->retrieveAllIds();
		$this->output(
			'...Update workflows state store: Found ' . count( $ids ) . ' workflow(s)...'
		);
		foreach ( $ids as $id ) {
			// Retrieving will recreate WorkflowStorage from events...
			// ... and fire each event for out state store to react to
			$eventStore->retrieve( $id );
		}
		$this->output( "done\n" );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'workflow-state-store-update-add-start';
	}
}

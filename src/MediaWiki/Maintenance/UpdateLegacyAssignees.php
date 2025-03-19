<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Maintenance;

use Exception;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;

require_once __DIR__ . '/../../../../../maintenance/Maintenance.php';

class UpdateLegacyAssignees extends LoggedUpdateMaintenance {

	protected function doDBUpdates() {
		/** @var WorkflowEventRepository $eventStore */
		$eventStore = MediaWikiServices::getInstance()->getService( 'WorkflowEventRepository' );
		/** @var WorkflowFactory $workflowFactory */
		$workflowFactory = MediaWikiServices::getInstance()->getService( 'WorkflowFactory' );

		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

		$success = $fail = 0;
		$ids = $eventStore->retrieveAllIds();
		foreach ( $ids as $id ) {
			try {
				$workflow = $workflowFactory->getWorkflow( $id );
				if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
					continue;
				}
				$current = $workflow->current();
				foreach ( $current as $task ) {
					$activity = $workflow->getActivityForTask( $task );
					if ( !( $activity instanceof UserInteractiveActivity ) ) {
						continue;
					}
					$users = $workflow->getActivityManager()->getTargetUsersForActivity( $activity );
					if ( $users === null ) {
						continue;
					}
					$stateRow = $db->selectRow(
						'workflows_state',
						[ 'wfs_assignees' ],
						[ 'wfs_workflow_id' => $id->toString() ],
						__METHOD__
					);
					if ( $stateRow === false ) {
						continue;
					}
					if ( $stateRow->wfs_assignees ) {
						// Already assigned
						continue;
					}
					$res = $db->update(
						'workflows_state',
						[ 'wfs_assignees' => implode( '|', $users ) ],
						[ 'wfs_workflow_id' => $id->toString() ],
						__METHOD__
					);
					$res ? $success++ : $fail++;

				}
			} catch ( Exception $ex ) {
				$this->output( "Failed to process workflow {$id->toString()}: " . $ex->getMessage() );
				continue;
			}
		}
		$this->output( "Updated $success workflow(s), failed $fail\n" );
		return true;
	}

	protected function getUpdateKey() {
		return 'workflow-state-store-update-legacy-assignees';
	}
}

$maintClass = UpdateLegacyAssignees::class;
require_once RUN_MAINTENANCE_IF_MAIN;

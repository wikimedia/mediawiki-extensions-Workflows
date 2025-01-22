<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use ManualLogEntry;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Message\Message;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;

class AbortWorkflowsOnDelete implements PageDeleteCompleteHook {

	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var WorkflowStateStore */
	private $stateStore;

	public function __construct(
		WorkflowFactory $workflowFactory, WorkflowStateStore $stateStore
	) {
		$this->workflowFactory = $workflowFactory;
		$this->stateStore = $stateStore;
	}

	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		$active = $this->stateStore->active()->complexQuery( [
			'context' => [ 'pageId' => $pageID ]
		] );
		/** @var WorkflowId $workflow */
		foreach ( $active as $workflowId ) {
			$workflow = $this->workflowFactory->getWorkflow( $workflowId );
			$workflow->autoAbort(
				'page-deleted',
				Message::newFromKey( 'workflows-auto-aborted-workflow-page-deleted' )->text(),
				true, false
			);
			$this->workflowFactory->persist( $workflow );
		}
	}
}

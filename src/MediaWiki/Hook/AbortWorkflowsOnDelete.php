<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Page\Hook\PageDeleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use StatusValue;

class AbortWorkflowsOnDelete implements PageDeleteHook {

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

	/**
	 * @inheritDoc
	 */
	public function onPageDelete(
		ProperPageIdentity $page, Authority $deleter, string $reason, StatusValue $status, bool $suppress
	) {
		// Abort workflows running on page before page is actually deleted,
		// This will make sure page can still be retrieved by pageID, which is how
		// pages are stored in workflow context. Needed to be able to emit abort events
		$active = $this->stateStore->active()->complexQuery( [
			'context' => [ 'pageId' => $page->getId() ]
		] );
		/** @var WorkflowId $workflow */
		foreach ( $active as $workflowId ) {
			$workflow = $this->workflowFactory->getWorkflow( $workflowId );
			$workflow->autoAbort(
				'page-deleted',
				\Message::newFromKey( 'workflows-auto-aborted-workflow-page-deleted' )->text(),
				true, false
			);
			$this->workflowFactory->persist( $workflow );
		}
	}
}

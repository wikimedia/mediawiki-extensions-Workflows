<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;

class AbortWorkflowsOnDelete implements ArticleDeleteCompleteHook {
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

	public function onArticleDeleteComplete(
		$wikiPage, $user, $reason, $id, $content, $logEntry, $archivedRevisionCount
	) {
		$active = $this->stateStore->active()->complexQuery( [
			'context' => [ 'pageId' => $wikiPage->getTitle()->getArticleID() ]
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

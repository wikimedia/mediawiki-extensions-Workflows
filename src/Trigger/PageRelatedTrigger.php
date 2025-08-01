<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\IPageTrigger;
use MediaWiki\Extension\Workflows\NoParallelTrigger;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Title\Title;

class PageRelatedTrigger extends GenericTrigger implements IPageTrigger, NoParallelTrigger {
	/** @var Title|null */
	protected $title = null;

	/** @var WorkflowStateStore|null */
	protected $workflowStore = null;

	/**
	 * @param Title $title
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
	}

	/**
	 * @return array
	 */
	protected function getContextData() {
		if ( !$this->title === null ) {
			return parent::getContextData();
		}
		return parent::getContextData() + [
			'pageId' => $this->title->getArticleID(),
			'revision' => $this->title->getLatestRevID()
		];
	}

	/**
	 * TODO: This part here needs rework (and the parts it calls)
	 * We need a better unified testing for rules
	 *
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		if ( !$this->title || $this->isAlreadyRunning() ) {
			return false;
		}
		return $this->appliesToPage( $this->title, $qualifyingData );
	}

	/**
	 * @return UserInteractionModule|null
	 */
	public function getEditorModule(): ?UserInteractionModule {
		return new UserInteractionModule(
			'ext.workflows.trigger.editors',
			'workflows.object.form.trigger.PageRelated'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function isAutomatic(): bool {
		return false;
	}

	/**
	 * @param WorkflowStateStore $stateStore
	 * @return void
	 */
	public function setWorkflowStore( WorkflowStateStore $stateStore ) {
		$this->workflowStore = $stateStore;
	}

	/**
	 * @return bool
	 */
	public function isAlreadyRunning(): bool {
		if ( !$this->workflowStore ) {
			return false;
		}
		return $this->checkIsAlreadyRunning( $this->title, $this->workflowStore );
	}
}

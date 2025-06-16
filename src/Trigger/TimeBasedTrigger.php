<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\NoParallelTrigger;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

class TimeBasedTrigger extends GenericTrigger implements NoParallelTrigger {
	/** @var array */
	protected $matches = [];

	/** @var WorkflowStateStore|null */
	protected $workflowStore = null;

	/**
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function trigger(): bool {
		/** @var Title $title */
		foreach ( $this->matches as $title ) {
			try {
				$this->startWorkflow( $this->repo, $this->definition, [
					'pageId' => $title->getArticleID(),
					'revision' => $title->getLatestRevID(),
				], $this->initData );
			} catch ( WorkflowExecutionException $ex ) {
				$this->logger->error( $ex->getMessage(), [
					'repository' => $this->repo,
					'definition' => $this->definition,
					'contextData' => $this->getContextData(),
					'initData' => $this->initData,
				] );
			}

		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getNativeType(): string {
		return 'time';
	}

	/**
	 * @inheritDoc
	 */
	public function appliesToPage( Title $title, $qualifyingData = [] ): bool {
		if ( !$title->isContentPage() ) {
			return false;
		}
		if ( $this->checkIsAlreadyRunning( $title, $this->workflowStore ) ) {
			return false;
		}

		return parent::appliesToPage( $title, $qualifyingData );
	}

	/**
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		$this->loadMatchingPages( $qualifyingData );
		return !empty( $this->matches );
	}

	/**
	 * @param array $qualifyingData
	 */
	protected function loadMatchingPages( $qualifyingData = [] ) {
		if ( !isset( $this->rules['include']['pages'] ) ) {
			return;
		}
		$this->matches = [];
		$pages = $this->processPagesRule( $this->rules['include']['pages'] );
		// Do not re-evaluate
		unset( $this->rules['include']['pages'] );
		foreach ( $pages as $page ) {
			if ( $this->appliesToPage( $page, $qualifyingData ) ) {
				$this->matches[] = $page;
			}
		}
	}

	/**
	 * @return User|null
	 */
	protected function getActor(): ?User {
		return User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
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
		// Not called in this trigger type
		return false;
	}
}

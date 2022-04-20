<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use Title;

class TimeBasedTrigger extends GenericTrigger {
	/** @var array */
	protected $matches = [];

	/**
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function trigger(): bool {
		/** @var Title $title */
		foreach ( $this->matches as $title ) {
			try {
				return $this->startWorkflow( $this->repo, $this->definition, [
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
				return false;
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
}

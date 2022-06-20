<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Linker\LinkRenderer;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use TitleFactory;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var LinkRenderer */
	private $linkRenderer;

	/**
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowFactory $wfFactory
	 * @param TitleFactory $titleFactory
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		WorkflowStateStore $stateStore, WorkflowFactory $wfFactory,
		TitleFactory $titleFactory, LinkRenderer $linkRenderer
	) {
		parent::__construct();
		$this->stateStore = $stateStore;
		$this->workflowFactory = $wfFactory;
		$this->titleFactory = $titleFactory;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema();
	}

	/**
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->stateStore, $this->titleFactory );
	}

	/**
	 * @return SecondaryDataProvider
	 */
	protected function makeSecondaryDataProvider() {
		return new SecondaryDataProvider( $this->workflowFactory, $this->linkRenderer );
	}
}

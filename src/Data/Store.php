<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Linker\LinkRenderer;
use MWStake\MediaWiki\Component\DataStore\IStore;

class Store implements IStore {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var \TitleFactory */
	private $titleFactory;
	/** @var LinkRenderer */
	private $linkRenderer;

	/**
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowFactory $wfFactory
	 * @param \TitleFactory $titleFactory
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		WorkflowStateStore $stateStore, WorkflowFactory $wfFactory,
		\TitleFactory $titleFactory, LinkRenderer $linkRenderer
	) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $wfFactory;
		$this->titleFactory = $titleFactory;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @return null
	 */
	public function getWriter() {
		return null;
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader(
			$this->stateStore, $this->workflowFactory,
			$this->titleFactory, $this->linkRenderer
		);
	}
}

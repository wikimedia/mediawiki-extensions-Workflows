<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\User\UserFactory;
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
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowFactory $wfFactory
	 * @param \TitleFactory $titleFactory
	 * @param LinkRenderer $linkRenderer
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		WorkflowStateStore $stateStore, WorkflowFactory $wfFactory,
		\TitleFactory $titleFactory, LinkRenderer $linkRenderer, UserFactory $userFactory
	) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $wfFactory;
		$this->titleFactory = $titleFactory;
		$this->linkRenderer = $linkRenderer;
		$this->userFactory = $userFactory;
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
			$this->titleFactory, $this->linkRenderer, $this->userFactory
		);
	}
}

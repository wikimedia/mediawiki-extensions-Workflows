<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Data\Store;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Extension\Workflows\WorkflowSerializer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Rest\Handler;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ListHandler extends Handler {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var WorkflowSerializer */
	private $workflowSerializer;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var LinkRenderer */
	private $linkRenderer;
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param WorkflowFactory $factory
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowSerializer $workflowSerializer
	 * @param TitleFactory $titleFactory
	 * @param LinkRenderer $linkRenderer
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		WorkflowFactory $factory, WorkflowStateStore $stateStore,
		WorkflowSerializer $workflowSerializer, TitleFactory $titleFactory,
		LinkRenderer $linkRenderer, UserFactory $userFactory
	) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $factory;
		$this->workflowSerializer = $workflowSerializer;
		$this->titleFactory = $titleFactory;
		$this->linkRenderer = $linkRenderer;
		$this->userFactory = $userFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$start = microtime( true );

		$readerParams = new ReaderParams( [
			'start' => $this->getOffset(),
			'limit' => $this->getLimit(),
			'filter' => $this->getFilter(),
			'sort' => $this->getSort()
		] );

		$store = new Store(
			$this->stateStore, $this->workflowFactory,
			$this->titleFactory, $this->linkRenderer, $this->userFactory
		);
		$resultSet = $store->getReader()->read( $readerParams );
		$end = microtime( true );

		return $this->getResponseFactory()->createJson( [
			'workflows' => $resultSet->getRecords(),
			'offset' => $this->getOffset(),
			'limit' => $this->getLimit(),
			'total' => $resultSet->getTotal(),
			'took' => $end - $start
		] );
	}

	/**
	 * @return int
	 */
	private function getOffset(): int {
		return (int)$this->getValidatedParams()['offset'];
	}

	/**
	 * @return int
	 */
	private function getLimit(): int {
		return (int)$this->getValidatedParams()['limit'];
	}

	/**
	 * @return array
	 */
	private function getFilter(): array {
		$validated = $this->getValidatedParams();
		if ( is_array( $validated ) && isset( $validated['filter'] ) ) {
			return json_decode( $validated['filter'], 1 );
		}
		return [];
	}

	/**
	 * @return array
	 */
	private function getSort(): array {
		$validated = $this->getValidatedParams();
		if ( is_array( $validated ) && isset( $validated['sort'] ) ) {
			return json_decode( $validated['sort'], 1 );
		}
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'filter' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false
			],
			'limit' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 25
			],
			'sort' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false
			],
			'offset' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 0
			]
		];
	}
}

<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Extension\Workflows\WorkflowSerializer;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use Wikimedia\ParamValidator\ParamValidator;

class ListHandler extends Handler {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var WorkflowSerializer */
	private $workflowSerializer;

	/**
	 * @param WorkflowFactory $factory
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowSerializer $workflowSerializer
	 */
	public function __construct(
		WorkflowFactory $factory, WorkflowStateStore $stateStore,
		WorkflowSerializer $workflowSerializer
	) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $factory;
		$this->workflowSerializer = $workflowSerializer;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$start = microtime( true );
		try {
			$ids = $this->getFilteredIds();
			$trimmed = $this->trim( $ids );
			if ( $this->retrieveFullDetails() ) {
				$workflows = [];
				foreach ( $trimmed as $id ) {
					$workflows[$id->toString()] = $this->getSerializedWorkflow( $id );
				}
			} else {
				$workflows = array_map( static function ( WorkflowId $id ) {
					return $id->toString();
				}, $trimmed );
			}
			$end = microtime( true );
		} catch ( WorkflowExecutionException $ex ) {
			throw new HttpException( $ex->getMessage() );
		}
		$total = count( $ids );
		return $this->getResponseFactory()->createJson( [
			'workflows' => $workflows,
			'offset' => $this->getOffset(),
			'limit' => $this->getLimit(),
			'total' => $total,
			'took' => $end - $start
		] );
	}

	protected function getFilteredIds() {
		$activeFilter = $this->activeFilter();
		$constraints = $this->getFilterData();

		if ( $activeFilter ) {
			$this->stateStore->active();
		} else {
			$this->stateStore->all();
		}
		if ( $constraints ) {
			$ids = $this->stateStore->complexQuery( $constraints );
		} else {
			$ids = $this->stateStore->query();
		}

		return $ids;
	}

	private function trim( $ids ) {
		return array_slice( $ids, $this->getOffset(), $this->getLimit() );
	}

	private function getOffset() {
		return (int)$this->getValidatedParams()['offset'];
	}

	private function getLimit() {
		return (int)$this->getValidatedParams()['limit'];
	}

	private function retrieveFullDetails() {
		return (bool)$this->getValidatedParams()['fullDetails'];
	}

	/**
	 * Retrieve active only?
	 *
	 * @return bool|null if filter not present
	 */
	private function activeFilter() {
		$validated = $this->getValidatedParams();
		if ( is_array( $validated ) && isset( $validated['active'] ) ) {
			return (bool)$validated['active'];
		}

		return null;
	}

	/**
	 * Get filter constraints
	 *
	 * @return array
	 */
	private function getFilterData() {
		$validated = $this->getValidatedParams();
		if ( is_array( $validated ) && isset( $validated['filterData'] ) ) {
			return json_decode( $validated['filterData'], 1 );
		}

		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'active' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'filterData' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false
			],
			'limit' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 25
			],
			'offset' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 0
			],
			'fullDetails' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 0
			]
		];
	}

	/**
	 * @param WorkflowId $id
	 * @return mixed
	 */
	private function getSerializedWorkflow( $id ) {
		$workflow = $this->workflowFactory->getWorkflow( $id );
		return $this->workflowSerializer->serialize( $workflow );
	}
}

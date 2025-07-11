<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Query\WorkflowStateModel;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\DataStore\IPrimaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;

class PrimaryDataProvider implements IPrimaryDataProvider {
	/** @var WorkflowStateStore */
	private $stateStore;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var string[] */
	private $propertyMap = [
		'start_ts' => 'wfs_started',
		'last_ts' => 'wfs_touched',
		'state' => 'wfs_state',
	];

	/**
	 * @param WorkflowStateStore $stateStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( WorkflowStateStore $stateStore, TitleFactory $titleFactory ) {
		$this->stateStore = $stateStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param ReaderParams $params
	 * @return array
	 */
	public function makeData( $params ) {
		$onlyActive = false;
		$context = [];
		$filters = $params->getFilter();
		foreach ( $filters as $filter ) {
			if ( $filter->getField() === 'context' ) {
				$context = $filter->getValue();
				$filter->setApplied( true );
				continue;
			}
			if ( $filter->getField() !== 'active' ) {
				continue;
			}
			if ( $filter->getValue() ) {
				$onlyActive = true;
			}
			$filter->setApplied( true );
		}
		$sort = [];
		foreach ( $params->getSort() as $sortField ) {
			$property = $this->propertyMap[$sortField->getProperty()] ?? null;
			if ( !$property ) {
				continue;
			}
			$sort[$property] = $sortField->getDirection();
		}
		if ( !empty( $sort ) ) {
			$this->stateStore->setSort( $sort );
		}

		if ( $onlyActive ) {
			$this->stateStore->active();
		} else {
			$this->stateStore->all();
		}

		if ( $context ) {
			$models = $this->stateStore->complexQuery( [ 'context' => $context ], true );
		} else {
			$models = $this->stateStore->query( true );
		}

		$data = [];
		/** @var WorkflowStateModel $model */
		foreach ( $models as $model ) {
			$page = $this->getPageFromContext( $model );
			$data[] = new Record( (object)[
				Record::ID => $model->getWorkflowId(),
				Record::TITLE => $this->constructTitleFromDefinition( $model ),
				Record::PAGE_PREFIXED_TEXT => $page instanceof Title ? $page->getPrefixedText() : '',
				'page_title_object' => $page,
				Record::STATE => $model->getState(),
				Record::ASSIGNEE => $model->getAssignees(),
				Record::LAST_TS => $model->getTouched(),
				Record::LAST_FORMATTED => '',
				Record::START_TS => $model->getStarted(),
				Record::START_FORMATTED => '',
			] );
		}

		return $data;
	}

	/**
	 * @param WorkflowStateModel $model
	 * @return string
	 */
	private function constructTitleFromDefinition( WorkflowStateModel $model ): string {
		$def = $model->getPayload()['definition'] ?? null;
		if ( !$def ) {
			return '';
		}

		$definitionSource = DefinitionSource::newFromArray( $def );
		return $definitionSource->getTitle();
	}

	/**
	 * @param WorkflowStateModel $model
	 * @return Title|null
	 */
	private function getPageFromContext( WorkflowStateModel $model ): ?Title {
		$pageId = $model->getPayload()['context']['pageId'] ?? null;
		if ( !$pageId ) {
			return null;
		}
		return $this->titleFactory->newFromID( $pageId );
	}
}

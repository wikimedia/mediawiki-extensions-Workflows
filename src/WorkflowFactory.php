<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\AggregateRootId;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;

class WorkflowFactory {
	/** @var WorkflowEventRepository */
	private $eventRepo;
	/** @var DefinitionRepositoryFactory */
	private $defintionRepositoryFactory;

	/**
	 * @param WorkflowEventRepository $repository
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 */
	public function __construct(
		WorkflowEventRepository $repository,
		DefinitionRepositoryFactory $definitionRepositoryFactory
	) {
		$this->eventRepo = $repository;
		$this->defintionRepositoryFactory = $definitionRepositoryFactory;
	}

	/**
	 * Retrieve instance of Workflow based on ID
	 * Good for public use
	 *
	 * @param WorkflowId $aggregateRootId
	 * @return Workflow
	 */
	public function getWorkflow(
		AggregateRootId $aggregateRootId
	): Workflow {
		return Workflow::newFromInstanceID(
			$aggregateRootId, $this->eventRepo, $this->defintionRepositoryFactory
		);
	}

	/**
	 * @param string $definition
	 * @param string $definitionRepositoryKey
	 * @return Workflow
	 */
	public function newEmpty( $definition, $definitionRepositoryKey ): Workflow {
		$repo = $this->defintionRepositoryFactory->getRepository( $definitionRepositoryKey );
		return Workflow::newEmpty( $definition, $repo );
	}

	/**
	 * Persist Workflow to EventStore
	 * @param Workflow $workflow
	 * @throws \Exception
	 */
	public function persist( Workflow $workflow ) {
		$workflow->persist( $this->eventRepo );
	}
}

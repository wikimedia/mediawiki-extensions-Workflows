<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\AggregateRootId;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WorkflowFactory {
	/** @var WorkflowEventRepository */
	private $eventRepo;
	/** @var DefinitionRepositoryFactory */
	private $definitionRepositoryFactory;

	/**
	 * @param WorkflowEventRepository $repository
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 */
	public function __construct(
		WorkflowEventRepository $repository,
		DefinitionRepositoryFactory $definitionRepositoryFactory
	) {
		$this->eventRepo = $repository;
		$this->definitionRepositoryFactory = $definitionRepositoryFactory;
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
			$aggregateRootId, $this->eventRepo, $this->definitionRepositoryFactory
		);
	}

	/**
	 * No permission checks, for bot or internal use only
	 * @param WorkflowId $workflowId
	 * @return Workflow
	 * @throws WorkflowExecutionException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getWorkflowForBot( WorkflowId $workflowId ): Workflow {
		return Workflow::newFromInstanceIDForBot(
			$workflowId, $this->eventRepo, $this->definitionRepositoryFactory
		);
	}

	/**
	 * @param string $definition
	 * @param string $definitionRepositoryKey
	 * @return Workflow
	 */
	public function newEmpty( $definition, $definitionRepositoryKey ): Workflow {
		$repo = $this->definitionRepositoryFactory->getRepository( $definitionRepositoryKey );
		if ( !( $repo instanceof IDefinitionRepository ) ) {
			throw new \InvalidArgumentException(
				"Definition repository {$definitionRepositoryKey} not found"
			);
		}
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

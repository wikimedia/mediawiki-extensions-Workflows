<?php

namespace MediaWiki\Extension\Workflows\Process;

use Exception;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Events\Notifier;
use MWStake\MediaWiki\Component\ProcessManager\IProcessStep;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProcessWorkflows implements IProcessStep, LoggerAwareInterface {

	/** @var LoggerInterface|null */
	protected LoggerInterface|null $logger = null;

	/**
	 * @param WorkflowEventRepository $workflowRepo
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 * @param Notifier $notifier
	 */
	public function __construct(
		private readonly WorkflowEventRepository $workflowRepo,
		private readonly DefinitionRepositoryFactory $definitionRepositoryFactory,
		protected readonly Notifier $notifier
	) {
		$this->logger = new NullLogger();
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function execute( $data = [] ): array {
		$workflowIds = $this->workflowRepo->retrieveAllIds();
		foreach ( $workflowIds as $workflowId ) {
			$this->logger->debug( "Loading '{id}'", [ 'id' => $workflowId->toString() ] );

			try {
				// Just the act of loading the workflow will probe any activity it might be on
				// and automatically preserve changes in case of status update
				$workflow = Workflow::newFromInstanceIDForBot(
					$workflowId, $this->workflowRepo, $this->definitionRepositoryFactory
				);

				$this->processWorkflow( $workflow );
			} catch ( Exception $ex ) {
				$this->logger->error( $ex->getMessage() );
				continue;
			}
		}

		return [ 'success' => true ];
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}
}

<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use Exception;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Status\Status;
use MWStake\MediaWiki\Component\Events\Notifier;
use MWStake\MediaWiki\Component\RunJobsTrigger\IHandler;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval\OnceEveryHour;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProcessWorkflows implements IHandler, LoggerAwareInterface {

	public const HANDLER_KEY = 'ext-workflows-process-workflows';

	/** @var WorkflowEventRepository */
	protected $workflowRepo;

	/** @var DefinitionRepositoryFactory */
	protected $definitionRepositoryFactory;

	/** @var LoggerInterface|null */
	protected $logger = null;

	/** @var Notifier */
	protected $notifier;

	/**
	 *
	 * @param WorkflowEventRepository $workflowRepo
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 * @param Notifier $notifier
	 */
	public function __construct(
		WorkflowEventRepository $workflowRepo, DefinitionRepositoryFactory $definitionRepositoryFactory,
		Notifier $notifier
	) {
		$this->workflowRepo = $workflowRepo;
		$this->definitionRepositoryFactory = $definitionRepositoryFactory;
		$this->logger = new NullLogger();
		$this->notifier = $notifier;
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		$workflowIds = $this->workflowRepo->retrieveAllIds();
		foreach ( $workflowIds as $workflowId ) {
			$this->logger->debug( "Loading '{id}'", [ 'id' => $workflowId->toString() ] );

			try {
				// Just the act of loading the workflow will probe any activity it might be on
				// and automatically preserve changes in case of status update
				$workflow = Workflow::newFromInstanceIDForBot(
					$workflowId, $this->workflowRepo, $this->definitionRepositoryFactory
				);
				if ( !$workflow instanceof Workflow ) {
					continue;
				}

				$this->processWorkflow( $workflow );
			} catch ( Exception $ex ) {
				return Status::newFatal( $ex->getMessage() );
			}

		}

		return Status::newGood();
	}

	/**
	 *
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceEveryHour();
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getKey() {
		return static::HANDLER_KEY;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * Do any processing on the workflow
	 * Stub in this class, to be implemented in subclasses
	 *
	 * @param Workflow $workflow
	 *
	 * @return void
	 */
	protected function processWorkflow( Workflow $workflow ) {
		// STUB
	}
}

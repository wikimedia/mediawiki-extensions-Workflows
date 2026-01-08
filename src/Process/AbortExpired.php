<?php

namespace MediaWiki\Extension\Workflows\Process;

use Exception;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Util\AutoAborter;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Events\Notifier;

final class AbortExpired extends ProcessWorkflows {

	/** @var AutoAborter */
	protected AutoAborter $autoAborter;

	/**
	 * @inheritDoc
	 */
	public function __construct(
		WorkflowEventRepository $workflowRepo, DefinitionRepositoryFactory $definitionRepositoryFactory,
		Notifier $notifier
	) {
		parent::__construct( $workflowRepo, $definitionRepositoryFactory, $notifier );
		$this->autoAborter = new AutoAborter( $workflowRepo );
	}

	/**
	 * @param Workflow $workflow
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function processWorkflow( Workflow $workflow ): void {
		$this->autoAborter->abortIfExpired( $workflow );
	}
}

<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use BlueSpice\RunJobsTriggerHandler\Interval\OnceADay;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Util\AutoAborter;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Events\Notifier;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;

final class AbortExpired extends ProcessWorkflows {

	public const HANDLER_KEY = 'ext-workflows-abort-expired';

	/** @var AutoAborter */
	protected $autoAborter;

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
	 * @throws \Exception
	 */
	protected function processWorkflow( Workflow $workflow ) {
		$this->autoAborter->abortIfExpired( $workflow );
	}

	/**
	 *
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceADay();
	}
}

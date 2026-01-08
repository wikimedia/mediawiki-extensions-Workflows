<?php

namespace MediaWiki\Extension\Workflows\Process;

use MediaWiki\Extension\Workflows\TriggerRunner;
use MWStake\MediaWiki\Component\ProcessManager\IProcessStep;
use Psr\Log\NullLogger;

final class ProcessTimeBasedTriggers implements IProcessStep {

	/** @var NullLogger */
	protected NullLogger $logger;

	/**
	 * @param TriggerRunner $triggerRunner
	 */
	public function __construct( private readonly TriggerRunner $triggerRunner ) {
		$this->logger = new NullLogger();
	}

	public function execute( $data = [] ): array {
		$this->triggerRunner->triggerAllOfType( 'time' );

		return [];
	}
}

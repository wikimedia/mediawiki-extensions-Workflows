<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use BlueSpice\RunJobsTriggerHandler\Interval\OnceADay;
use MediaWiki\Extension\Workflows\TriggerRunner;
use MWStake\MediaWiki\Component\RunJobsTrigger\IHandler;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;
use Psr\Log\NullLogger;
use Status;

final class ProcessTimeBasedTriggers implements IHandler {

	public const HANDLER_KEY = 'ext-workflows-process-time-based-triggers';

	/** @var TriggerRunner */
	protected $triggerRunner;

	/**
	 *
	 * @param TriggerRunner $triggerRunner
	 */
	public function __construct( TriggerRunner $triggerRunner ) {
		$this->logger = new NullLogger();
		$this->triggerRunner = $triggerRunner;
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		$status = Status::newGood();

		$this->triggerRunner->triggerAllOfType( 'time' );

		return $status;
	}

	/**
	 *
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceADay();
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getKey() {
		return static::HANDLER_KEY;
	}
}

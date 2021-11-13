<?php

namespace MediaWiki\Extension\Workflows\Activity;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\IActivity;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Activity implements IActivity, LoggerAwareInterface {
	/** @var ITask */
	protected $task;

	/**
	 * PSR-4 logger object.
	 * Used log all user actions.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Sets PSR-4 logger for activity
	 *
	 * @param LoggerInterface $logger Logger object
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function __construct( ITask $task ) {
		$this->task = $task;

		$this->logger = new NullLogger();
	}

	public function getTask(): ITask {
		return $this->task;
	}
}

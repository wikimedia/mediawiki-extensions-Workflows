<?php

namespace MediaWiki\Extension\Workflows\StateTracker;

use MediaWiki\Extension\Workflows\ActivityManager;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Util\MultiInstanceHelper;
use MediaWiki\Extension\Workflows\WorkflowContext;

class ParallelMultiInstanceStateTracker extends ParallelStateTracker {
	/** @var WorkflowContext */
	private $context;
	/** @var ActivityManager */
	private $manager;

	public function __construct( ITask $task, WorkflowContext $context, ActivityManager $manager ) {
		$this->context = $context;
		$this->manager = $manager;

		$tasks = $this->expand( $task );
		parent::__construct( $tasks );
	}

	private function expand( ITask $task ) {
		$helper = new MultiInstanceHelper();

		$dataSets = $helper->getMultiInstancePropertyData( $task, $this->context );
		$expanded = [];
		foreach ( $dataSets as $index => $set ) {
			// Create a task and activity for each of the data sets
			$newTask = $helper->cloneTaskWithCounter( $task, $index );
			$expanded[] = $newTask;
			$activity = $this->manager->getActivityForTask( $newTask );
			$this->manager->setActivityProperties( $activity, $set );
		}

		return $expanded;
	}
}

<?php

namespace MediaWiki\Extension\Workflows\Util;

use DateTime;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;

class AutoAborter {

	/**
	 * @var WorkflowEventRepository
	 */
	private $repository;

	/**
	 * @param WorkflowEventRepository $repository
	 */
	public function __construct( WorkflowEventRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * @param Workflow $workflow
	 *
	 * @return bool True if the workflow was aborted
	 * @throws \Exception
	 */
	public function abortIfExpired( Workflow $workflow ): bool {
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			return false;
		}
		// Next part checks for due dates
		$current = $workflow->current();
		foreach ( $current as $element ) {
			if ( !$element instanceof ITask ) {
				continue;
			}
			$activity = $workflow->getActivityForTask( $element );
			if ( !$activity instanceof UserInteractiveActivity ) {
				continue;
			}
			if ( $this->dueDateReached( $activity ) ) {
				$workflow->expireActivity( $activity );
				$workflow->persist( $this->repository );
				return true;
			}
		}
		return false;
	}

	/**
	 * @param UserInteractiveActivity $activity
	 *
	 * @return bool
	 */
	private function dueDateReached( UserInteractiveActivity $activity ) {
		$dueDate = $activity->getDueDate();
		if ( !$dueDate instanceof DateTime ) {
			return false;
		}
		$now = new DateTime( 'today' );

		return $dueDate < $now;
	}
}

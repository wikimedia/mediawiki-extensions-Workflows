<?php

namespace MediaWiki\Extension\Workflows\Process;

use DateTime;
use Exception;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Event\DueDateProximityEvent;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use PermissionsError;

final class SendDueDateProximityNotifications extends ProcessWorkflows {

	/**
	 * @param Workflow $workflow
	 *
	 * @return void
	 * @throws WorkflowExecutionException
	 * @throws PermissionsError
	 * @throws Exception
	 */
	protected function processWorkflow( Workflow $workflow ): void {
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			return;
		}
		// Next part checks for due dates
		$current = $workflow->current();
		if ( !$current ) {
			return;
		}
		foreach ( $current as $element ) {
			if ( !$element instanceof ITask ) {
				continue;
			}
			$activity = $workflow->getActivityForTask( $element );
			if ( !$activity instanceof UserInteractiveActivity ) {
				continue;
			}
			if ( $this->dueDateClose( $activity ) ) {
				$this->notifyDueDateProximity( $activity, $workflow );
			}
		}
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @return bool
	 */
	private function dueDateClose( UserInteractiveActivity $activity ): bool {
		$dueDate = $activity->getDueDate();
		if ( !$dueDate instanceof DateTime ) {
			return false;
		}

		$now = new DateTime( "now" );
		if ( $dueDate->diff( $now )->days < 2 ) {
			return true;
		}

		return false;
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @param Workflow $workflow
	 * @return void
	 * @throws WorkflowExecutionException
	 */
	private function notifyDueDateProximity(
		UserInteractiveActivity $activity, Workflow $workflow
	): void {
		$this->notifier->emit(
			new DueDateProximityEvent(
				$workflow->getContext()->getContextPage(),
				$workflow->getActivityManager()->getTargetUsersForActivity( $activity, true ),
				$activity->getActivityDescriptor()->getActivityName(),
				$workflow->getContext()->getInitiator()
			)
		);
	}
}

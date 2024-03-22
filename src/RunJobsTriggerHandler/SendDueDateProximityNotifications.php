<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use BlueSpice\RunJobsTriggerHandler\Interval\OnceADay;
use DateTime;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Event\DueDateProximityEvent;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;

final class SendDueDateProximityNotifications extends ProcessWorkflows {

	public const HANDLER_KEY = 'ext-workflows-send-due-date-proximity-notifications';

	/**
	 * @param Workflow $workflow
	 * @return void
	 * @throws WorkflowExecutionException
	 */
	protected function processWorkflow( Workflow $workflow ) {
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			return;
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
			if ( $this->dueDateClose( $activity ) ) {
				$this->notifyDueDateProximity( $activity, $workflow );
			}
		}
	}

	/**
	 *
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceADay();
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @return bool
	 */
	private function dueDateClose( UserInteractiveActivity $activity ) {
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
	) {
		$this->notifier->emit(
			new DueDateProximityEvent(
				$workflow->getContext()->getContextPage(),
				$workflow->getActivityManager()->getTargetUsersForActivity( $activity ),
				$activity->getActivityDescriptor()->getActivityName(),
				$workflow->getContext()->getInitiator()
			)
		);
	}
}

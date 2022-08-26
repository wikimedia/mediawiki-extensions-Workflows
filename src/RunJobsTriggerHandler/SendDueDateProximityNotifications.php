<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use BlueSpice\RunJobsTriggerHandler\Interval\OnceADay;
use DateTime;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\DueDateProximity;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;

final class SendDueDateProximityNotifications extends ProcessWorkflows {

	public const HANDLER_KEY = 'ext-workflows-send-due-date-proximity-notifications';

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
			if ( $this->dueDateReached( $activity ) ) {
				$workflow->expireActivity( $activity );
				$workflow->persist( $this->workflowRepo );
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

	private function dueDateReached( UserInteractiveActivity $activity ) {
		$dueDate = $activity->getDueDate();
		if ( !$dueDate instanceof DateTime ) {
			return false;
		}

		$now = new DateTime( "now" );
		// Dont know how else to get DT with today's date only
		$now = new DateTime( $now->format( 'd-m-Y' ) );

		return $dueDate < $now;
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

	private function notifyDueDateProximity(
		UserInteractiveActivity $activity, Workflow $workflow
	) {
		$this->notifier->notify(
			new DueDateProximity(
				$workflow->getContext()->getInitiator(),
				$workflow->getActivityManager()->getTargetUsersForActivity( $activity ),
				$workflow->getContext()->getContextPage(),
				$activity->getActivityDescriptor()->getActivityName()
			)
		);
	}
}

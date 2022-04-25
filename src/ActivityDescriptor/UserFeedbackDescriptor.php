<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class UserFeedbackDescriptor extends FeedbackDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getActivityName(): Message {
		return Message::newFromKey( 'workflows-activity-user-feedback-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getHistoryReport( Workflow $workflow ): array {
		$status = $workflow->getActivityManager()->getActivityStatus( $this->activity );
		if (
			$status !== IActivity::STATUS_COMPLETE &&
			$status !== IActivity::STATUS_LOOP_COMPLETE
		) {
			return [];
		}
		$rd = $workflow->getContext()->getRunningData(
			$this->activity->getTask()->getId()
		);

		if ( $rd === null ) {
			return [];
		}

		return [
			$rd['assigned_user'] => $this->stripComment( $rd['comment'] )
		];
	}
}

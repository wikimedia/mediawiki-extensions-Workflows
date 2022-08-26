<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class GroupFeedbackDescriptor extends FeedbackDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getActivityName(): Message {
		return Message::newFromKey( 'workflows-activity-group-feedback-name' );
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

		$history = [];

		$rd = $workflow->getContext()->getRunningData(
			$this->activity->getTask()->getId()
		);

		if ( $rd === null ) {
			return [];
		}

		if ( empty( $rd['users_feedbacks'] ) ) {
			return $history;
		}
		$feedbacks = $rd['users_feedbacks'];
		$feedbacks = is_array( $feedbacks ) ?
			$feedbacks : json_decode( $rd['users_feedbacks'], 1 );
		foreach ( $feedbacks as $feedback ) {
			$history[$feedback['userName']] = $this->stripComment( $feedback['feedback'] );
		}

		return $history;
	}
}

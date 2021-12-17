<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;

class GroupFeedbackDescriptor extends FeedbackDescriptor {

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

		$feedbacks = $rd['users_feedbacks'];
		foreach ( $feedbacks as $feedback ) {
			$history[$feedback['userName']] = $this->stripComment( $feedback['feedback'] );
		}

		return $history;
	}
}

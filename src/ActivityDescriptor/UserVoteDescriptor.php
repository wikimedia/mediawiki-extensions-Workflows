<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class UserVoteDescriptor extends FeedbackDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getHistoryReport( Workflow $workflow ): array {
		$status = $workflow->getActivityManager()->getActivityStatus( $this->activity );

		if (
			$status === IActivity::STATUS_NOT_STARTED ||
			$status === IActivity::STATUS_EXECUTING ||
			$status === IActivity::STATUS_STARTED
		) {
			return [];
		}

		$history = [];

		$rd = $workflow->getContext()->getRunningData(
			$this->activity->getTask()->getId()
		);

		if ( $rd['vote'] === 'yes' ) {
			$voteResult = Message::newFromKey( 'workflows-activity-history-vote-result-yes' )->text();
		} else {
			$voteResult = Message::newFromKey( 'workflows-activity-history-vote-result-no' )->text();
		}

		$history[$rd['assigned_user']] = $voteResult . ': ' . $this->stripComment( $rd['comment'] );

		return $history;
	}
}

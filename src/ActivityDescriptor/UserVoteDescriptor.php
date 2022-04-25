<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class UserVoteDescriptor extends FeedbackDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getActivityName(): Message {
		return Message::newFromKey( 'workflows-activity-user-vote-name' );
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

		if ( $rd['vote'] === 'yes' ) {
			$voteResult = Message::newFromKey( 'workflows-activity-history-vote-result-yes' )->text();
		} else {
			$voteResult = Message::newFromKey( 'workflows-activity-history-vote-result-no' )->text();
		}

		return [
			$rd['assigned_user'] => $voteResult . ': ' . $this->stripComment( $rd['comment'] )
		];
	}
}

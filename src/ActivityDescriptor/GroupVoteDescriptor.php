<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class GroupVoteDescriptor extends FeedbackDescriptor {

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

		$voteResults = [
			'yes' => Message::newFromKey( 'workflows-activity-history-vote-result-yes' )->text(),
			'no' => Message::newFromKey( 'workflows-activity-history-vote-result-no' )->text()
		];

		$usersVoted = $rd['users_voted'];
		foreach ( $usersVoted as $userVoted ) {
			$voteResult = $voteResults[$userVoted['vote']];
			$history[$userVoted['userName']] = $voteResult . ': ' . $this->stripComment( $userVoted['comment'] );
		}

		return $history;
	}
}

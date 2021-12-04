<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity;

use MediaWiki\Extension\Workflows\Activity\FeedbackActivity\GenericFeedbackActivity;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Action\ActionList;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification\VoteAccept;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification\VoteDeny;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use Message;

abstract class GenericVoteActivity extends GenericFeedbackActivity {

	/**
	 * Sets data, necessary for vote processing.
	 * Also makes some checks on input and collects possible errors
	 *
	 * @param array $data Data which is used to process activity
	 * @param WorkflowContext $context Context object
	 * @throws WorkflowExecutionException In case of some invalid values
	 */
	protected function setSecondaryData( array $data, WorkflowContext $context ): void {
		$errorMessages = [];

		$this->action = ActionList::ACTION_VOTE;
		if ( isset( $data['action' ] ) ) {
			if ( in_array( $data['action'], ActionList::allActions() ) ) {
				$this->action = $data['action'];
			} else {
				// workflows-group-vote-action-invalid
				// workflows-user-vote-action-invalid
				$errorMessages[] = 'workflows-' . $this->activityKey . '-action-invalid';
				$this->handleErrors( $errorMessages );
			}
		}
	}

	/**
	 * @param string $vote <tt>'yes'</tt> or <tt>'no'</tt>, depending on what user selected
	 * @param string $comment Comment, which user provided with his vote
	 * @throws WorkflowExecutionException In case of some invalid values
	 */
	protected function doVote( string $vote, string $comment ): void {
		if ( $vote !== 'yes' && $vote !== 'no' ) {
			$errorMessage = Message::newFromKey( 'workflows-vote-value-invalid' )->text();
			$this->logger->error( $errorMessage );
			throw new WorkflowExecutionException( $errorMessage, $this->task );
		}

		if ( $vote === 'yes' ) {
			$this->voteYes( $comment );
		} else {
			$this->voteNo( $comment );
		}
	}

	/**
	 * @param string $comment Comment, which user provided with his vote
	 */
	protected function voteYes( string $comment ): void {
		$this->logger->info( "User {$this->actor->getName()} voted as 'yes'" );

		$action = $this->getSpecialLogAction( 'yes' );

		$this->logToSpecialLog( $action, $comment );

		$notification = new VoteAccept(
			$this->actor,
			$this->targetPage,
			$this->owner,
			$this->getActivityDescriptor()->getActivityName()->parse(),
			$comment
		);

		$this->getNotifier()->notify( $notification );
	}

	/**
	 * @param string $comment Comment, which user provided with his vote
	 */
	protected function voteNo( string $comment ): void {
		$this->logger->info( "User {$this->actor->getName()} voted as 'no'" );

		$action = $this->getSpecialLogAction( 'no' );

		$this->logToSpecialLog( $action, $comment );

		$notification = new VoteDeny(
			$this->actor,
			$this->targetPage,
			$this->owner,
			$this->getActivityDescriptor()->getActivityName()->parse(),
			$comment
		);

		$this->getNotifier()->notify( $notification );
	}

	/**
	 * Gets action string, which is used to log entry about vote in Special:Log.
	 * It is used to compose final translated message "logentry-ext-workflows-{action}".
	 * So it may look like that: "logentry-ext-workflows-uservote-yes".
	 *
	 * @param string $vote "yes" or "no", depending on user vote
	 * @return string Action as string
	 */
	abstract protected function getSpecialLogAction( string $vote ): string;
}

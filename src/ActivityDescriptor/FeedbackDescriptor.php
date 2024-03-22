<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\Event\FeedbackTaskAssignedEvent;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\InstructedActivity;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Events\INotificationEvent;

class FeedbackDescriptor extends GenericUIActivityDescriptor {

	/**
	 * Maximum user comment length to be displayed
	 */
	private const COMMENT_MAX_LENGTH = 100;

	/**
	 * Strips comment depending on its length
	 *
	 * @param string $comment Comment
	 * @return string Stripped comment
	 */
	protected function stripComment( string $comment ): string {
		$comment = strip_tags( $comment );
		if ( strlen( $comment ) > self::COMMENT_MAX_LENGTH ) {
			$comment = substr( $comment, 0, self::COMMENT_MAX_LENGTH - 1 ) . '…';
		}

		return $comment;
	}

	/**
	 * @inheritDoc
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new InstructedActivity( $this->activity, $workflow );
	}

	/**
	 * @inheritDoc
	 */
	public function getNotificationFor( ActivityEvent $event, Workflow $workflow ): ?INotificationEvent {
		if ( $event instanceof TaskStarted ) {
			$validUsers = $workflow->getActivityManager()->getTargetUsersForActivity( $this->activity, true ) ?? [];

			return new FeedbackTaskAssignedEvent(
				$workflow->getContext()->getInitiator(),
				$workflow->getContext()->getContextPage(),
				$validUsers,
				$this->getActivityName(),
				$workflow->getActivityManager()->getActivityProperties( $this->activity )['instructions']
			);
		}
		return null;
	}
}

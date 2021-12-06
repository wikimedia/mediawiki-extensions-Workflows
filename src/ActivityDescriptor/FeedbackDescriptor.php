<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification\FeedbackTaskAssigned;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\InstructedActivity;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Notifications\INotification;
use User;

class FeedbackDescriptor extends GenericDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new InstructedActivity( $this->activity, $workflow );
	}

	/**
	 * @inheritDoc
	 */
	public function getNotificationFor( ActivityEvent $event, Workflow $workflow ): ?INotification {
		if ( $event instanceof TaskStarted ) {
			$targetUsers = $workflow->getActivityManager()->getTargetUsersForActivity( $this->activity ) ?? [];
			$validUsers = [];
			foreach ( $targetUsers as $username ) {
				$user = User::newFromName( $username );
				if ( !$user instanceof User || !$user->isRegistered() ) {
					continue;
				}
				$validUsers[] = $user;
			}

			$notification = new FeedbackTaskAssigned(
				$validUsers,
				$workflow->getContext()->getContextPage(),
				$this->getActivityName()->parse(),
				$workflow->getContext()->getInitiator(),
				$workflow->getActivityManager()->getActivityProperties( $this->activity )['instructions']
			);

			return $notification;
		}
		return null;
	}
}

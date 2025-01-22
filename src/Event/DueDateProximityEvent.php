<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;

class DueDateProximityEvent extends TaskAssignedEvent {

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'workflows-event-task-due-date-proximity-key-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = 'workflows-event-task-due-data-proximity';
		return Message::newFromKey( $msgKey )->params(
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->getActivity()
		);
	}

	/**
	 * @param UserIdentity $agent
	 * @param MediaWikiServices $services
	 * @param array $extra
	 * @return array
	 */
	public static function getArgsForTesting(
		UserIdentity $agent, MediaWikiServices $services, array $extra = []
	): array {
		$target = $extra['targetUser'] ?? $services->getUserFactory()->newFromName( 'WikiSysop' );
		return [ $extra['title'], [ $target ], 'dummy task' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinksIntroMessage( IChannel $forChannel ): ?Message {
		return Message::newFromKey( 'workflows-event-task-due-date-proximity-links-message' );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'workflows-event-task-due-date-proximity';
	}
}

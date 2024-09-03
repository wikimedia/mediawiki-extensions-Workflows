<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\BotAgent;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;
use Title;

class TaskAssignedEvent extends TitleEvent implements PriorityEvent {
	/** @var string */
	private $activity;

	/** @var array */
	private $targetUsers;

	/**
	 * @param Title $title
	 * @param array $targetUsers
	 * @param string $activity
	 * @param UserIdentity|null $actor
	 */
	public function __construct( Title $title, array $targetUsers, string $activity, ?UserIdentity $actor = null ) {
		parent::__construct( $actor ?? new BotAgent(), $title );
		$this->activity = $activity;
		$this->targetUsers = $targetUsers;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'workflows-event-task-assigned-key-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = 'workflows-event-task-assigned-bot';

		return Message::newFromKey( $msgKey )->params(
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->getActivity()
		);
	}

	/**
	 * @return array|null
	 */
	public function getPresetSubscribers(): ?array {
		return $this->targetUsers;
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
		return [
			$extra['title'],
			[ $target ],
			'dummy task'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinksIntroMessage( IChannel $forChannel ): ?Message {
		return Message::newFromKey( 'workflows-event-task-assigned-links-message' );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'workflows-event-task-assigned';
	}

	/**
	 * @return string
	 */
	public function getActivity(): string {
		return $this->activity;
	}
}

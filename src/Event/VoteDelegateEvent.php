<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;
use User;

class VoteDelegateEvent extends TitleEvent implements PriorityEvent {
	/** @var string */
	private $activity;

	/** @var array */
	private $targetUsers;

	/** @var string */
	private $comment;

	/** @var UserIdentity */
	private $delegateTo;

	/**
	 * @param UserIdentity $voter
	 * @param Title $title
	 * @param User $delegateTo
	 * @param UserIdentity $owner
	 * @param string $activity
	 * @param string $comment
	 */
	public function __construct(
		UserIdentity $voter, Title $title, User $delegateTo,
		UserIdentity $owner, string $activity, string $comment
	) {
		parent::__construct( $voter, $title );
		$this->activity = $activity;
		$this->targetUsers = [ $owner ];
		$this->comment = $comment;
		$this->delegateTo = $delegateTo;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( "workflows-event-vote-delegate-desc" );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = "workflows-event-vote-delegate";
		if ( !$this->comment ) {
			$msgKey .= '-no-comment';
		}

		return Message::newFromKey( $msgKey )->params(
			$this->getAgent()->getName(),
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->activity,
			$this->comment,
			$this->getTitleAnchor(
				$this->delegateTo->getUserPage(),
				$forChannel,
				$this->delegateTo->getRealName() ?: $this->delegateTo->getName()
			)
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
			$agent,
			$extra['title'],
			$target,
			$target,
			'dummy activity',
			'dummy comment'
		];
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return "workflows-event-vote-delegate";
	}
}

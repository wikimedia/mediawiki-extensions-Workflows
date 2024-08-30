<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;
use Title;

class VoteEvent extends TitleEvent implements PriorityEvent {
	/** @var string */
	private $activity;

	/** @var array */
	private $targetUsers;

	/** @var string */
	private $comment;

	/** @var string */
	private $voteType;

	/**
	 * @param UserIdentity $voter
	 * @param Title $title
	 * @param UserIdentity $owner
	 * @param string $activity
	 * @param string $comment
	 * @param string $voteType
	 */
	public function __construct(
		UserIdentity $voter, Title $title, UserIdentity $owner, string $activity, string $comment, string $voteType
	) {
		parent::__construct( $voter, $title );
		$this->activity = $activity;
		$this->targetUsers = [ $owner ];
		$this->comment = $comment;
		$this->voteType = $voteType;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( "workflows-event-vote-key-desc" );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		// workflows-event-vote-yes
		// workflows-event-vote-no
		// workflows-event-vote-yes-no-comment
		// workflows-event-vote-no-no-comment
		$msgKey = "workflows-event-vote-$this->voteType";
		if ( !$this->comment ) {
			$msgKey .= '-no-comment';
		}

		return Message::newFromKey( $msgKey )->params(
			$this->getAgent()->getName(),
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->activity,
			$this->comment
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
			'dummy activity',
			'dummy comment',
			'yes'
		];
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return "workflows-event-vote";
	}
}

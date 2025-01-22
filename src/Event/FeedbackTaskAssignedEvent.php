<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;

class FeedbackTaskAssignedEvent extends TaskAssignedEvent {

	/** @var string */
	private $instructions;

	/**
	 * @param UserIdentity $actor
	 * @param Title $title
	 * @param array $targetUsers
	 * @param string $activity
	 * @param string $instructions
	 */
	public function __construct(
		UserIdentity $actor, Title $title, array $targetUsers, string $activity, string $instructions
	) {
		parent::__construct( $title, $targetUsers, $activity, $actor );
		$this->instructions = $instructions;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = 'workflows-event-feedback-task-assigned';
		if ( !$this->instructions ) {
			$msgKey .= '-no-instructions';
		}

		return Message::newFromKey( $msgKey )->params(
			$this->getAgent()->getName(),
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->getActivity(),
			$this->instructions
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
		return [
			$agent,
			$extra['title'],
			[ $target ],
			'dummy task',
			'foo bar'
		];
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'workflows-event-feedback-task-assigned';
	}
}

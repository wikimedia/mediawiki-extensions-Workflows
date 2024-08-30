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

class WorkflowEndedEvent extends TitleEvent implements PriorityEvent {

	/** @var array */
	private $targetUsers;

	/** @var string */
	protected $workflow;

	/**
	 * @param Title $title
	 * @param array $targetUsers
	 * @param string $workflowType
	 */
	public function __construct( Title $title, array $targetUsers, string $workflowType ) {
		parent::__construct( new BotAgent(), $title );
		$this->workflow = $workflowType;
		$this->targetUsers = $targetUsers;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'workflows-event-workflow-ended-key-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = 'workflows-event-workflow-ended';
		return Message::newFromKey( $msgKey )->params(
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->workflow
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
		return [ $extra['title'], [ $target ], 'dummy workflow' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'workflows-event-workflow-ended';
	}
}

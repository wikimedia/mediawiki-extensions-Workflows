<?php

namespace MediaWiki\Extension\Workflows\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;
use Title;

class WorkflowAbortedEvent extends TitleEvent implements PriorityEvent {

	/** @var string */
	private $reason;

	/** @var array */
	private $targetUsers;

	/** @var string */
	protected $workflow;

	/**
	 * @param UserIdentity $agent
	 * @param Title $title
	 * @param array $targetUsers
	 * @param string $workflowType
	 * @param string $reason
	 */
	public function __construct(
		UserIdentity $agent, Title $title, array $targetUsers, string $workflowType, string $reason
	) {
		parent::__construct( $agent, $title );
		$this->reason = $reason;
		$this->workflow = $workflowType;
		$this->targetUsers = $targetUsers;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'workflows-event-workflow-aborted-key-desc' );
	}

	/**
	 * @return array|null
	 */
	public function getPresetSubscribers(): ?array {
		return $this->targetUsers;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msgKey = 'workflows-event-workflow-aborted';
		if ( !$this->reason ) {
			$msgKey .= '-no-reason';
		}
		if ( $this->isBotAgent() ) {
			return Message::newFromKey( $msgKey . '-bot' )->params(
				$this->getTitleAnchor( $this->getTitle(), $forChannel ),
				$this->workflow,
				$this->reason
			);
		}
		return Message::newFromKey( $msgKey )->params(
			$this->getAgent()->getName(),
			$this->getTitleAnchor( $this->getTitle(), $forChannel ),
			$this->workflow,
			$this->reason
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
			'dummy workflow',
			'foo bar'
		];
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
		return 'workflows-event-workflow-aborted';
	}
}

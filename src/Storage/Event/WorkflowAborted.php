<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use DateTime;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use Ramsey\Uuid\UuidInterface;
use User;

class WorkflowAborted extends Event {
	use ActorTrait;

	/** @var string */
	private $reason;
	/** @var DateTime */
	private $date;

	/**
	 * @param UuidInterface $id
	 * @param User $actor
	 * @param DateTime $date
	 * @param string $reason
	 */
	public function __construct( UuidInterface $id, User $actor, DateTime $date, $reason = '' ) {
		parent::__construct( $id );

		$this->actor = $actor;
		$this->date = $date;
		$this->reason = $reason;
	}

	/**
	 * @return string
	 */
	public function getReason() {
		return $this->reason;
	}

	/**
	 * @return DateTime
	 */
	public function getDate(): DateTime {
		return $this->date;
	}

	/**
	 * @inheritDoc
	 */
	public function toPayload(): array {
		return array_merge( [
			'reason' => $this->reason,
			'actor' => $this->actorToPayload(),
			'date' => $this->date->format( 'YmdHis' ),
		], parent::toPayload() );
	}

	/**
	 * @inheritDoc
	 */
	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::actorFromPayload( $payload ),
			DateTime::createFromFormat( 'YmdHis', $payload['date'] ),
			$payload['reason'],
		];
	}
}

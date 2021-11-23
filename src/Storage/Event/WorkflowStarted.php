<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use DateTime;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use Ramsey\Uuid\UuidInterface;
use User;

final class WorkflowStarted extends Event {
	use ActorTrait;

	/** @var array */
	private $contextData;
	/** @var DateTime */
	private $startDate;

	/**
	 * @param UuidInterface $id
	 * @param User $actor
	 * @param DateTime $startDate
	 * @param array $contextData
	 */
	public function __construct( UuidInterface $id, User $actor, DateTime $startDate, $contextData = [] ) {
		parent::__construct( $id );
		$this->actor = $actor;
		$this->startDate = $startDate;
		$this->contextData = $contextData;
	}

	/**
	 * @return array
	 */
	public function getContextData(): array {
		return $this->contextData;
	}

	/**
	 * @return DateTime
	 */
	public function getStartDate(): DateTime {
		return $this->startDate;
	}

	public function toPayload(): array {
		return array_merge( [
			'contextData' => $this->contextData,
			'startDate' => $this->startDate->format( 'YmdHis' ),
			'actor' => $this->actorToPayload(),
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::actorFromPayload( $payload ),
			DateTime::createFromFormat( 'YmdHis', $payload['startDate'] ),
			$payload['contextData'],
		];
	}
}

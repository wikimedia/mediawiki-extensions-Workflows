<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use DateTime;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;
use Ramsey\Uuid\UuidInterface;

final class WorkflowEnded extends Event {
	use ElementTrait;

	/** @var DateTime */
	private $date;

	public function __construct( UuidInterface $id, DateTime $date ) {
		parent::__construct( $id );
		$this->date = $date;
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
			DateTime::createFromFormat( 'YmdHis', $payload['date'] ),
		];
	}
}

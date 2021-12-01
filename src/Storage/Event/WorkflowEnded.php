<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use DateTime;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;
use Ramsey\Uuid\UuidInterface;

final class WorkflowEnded extends Event {
	use ElementTrait;

	/** @var DateTime|null */
	private $date;

	/**
	 * @param UuidInterface $id
	 * @param string $elementID
	 * @param DateTime|null $date
	 */
	public function __construct( UuidInterface $id, $elementID, ?DateTime $date ) {
		parent::__construct( $id );
		$this->elementID = $elementID;
		$this->date = $date;
	}

	/**
	 * @return DateTime
	 */
	public function getDate(): ?DateTime {
		return $this->date;
	}

	/**
	 * @inheritDoc
	 */
	public function toPayload(): array {
		return array_merge( [
			'date' => $this->date->format( 'YmdHis' ),
			'elementID' => $this->getElementId(),
		], parent::toPayload() );
	}

	/**
	 * @inheritDoc
	 */
	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		$date = null;
		if ( isset( $payload['date'] ) ) {
			$date = DateTime::createFromFormat( 'YmdHis', $payload['date'] );
		}
		return [
			$data['id'],
			$payload['elementID'],
			$date,
		];
	}
}

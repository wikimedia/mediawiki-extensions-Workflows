<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;
use Ramsey\Uuid\UuidInterface;

final class ActivityProbeChange extends Event {
	use ElementTrait;

	/** @var int */
	private $status;
	/** @var array */
	private $properties;

	public function __construct(
		UuidInterface $id, $elementId, $status, $properties
	) {
		parent::__construct( $id );
		$this->elementID = $elementId;
		$this->status = $status;
		$this->properties = $properties;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @return array
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	public function toPayload(): array {
		return array_merge( [
			'status' => $this->status,
			'elementId' => $this->elementID,
			'properties' => $this->properties,
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::elementIdFromPayload( $payload ),
			$payload['status'],
			$payload['properties']
		];
	}
}

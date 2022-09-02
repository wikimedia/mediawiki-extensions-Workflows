<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use EventSauce\EventSourcing\PointInTime;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class Event implements SerializablePayload {
	/** @var UuidInterface */
	private $id;
	/** @var PointInTime|null */
	private $recordedAt = null;

	/**
	 * @param mixed ...$data
	 * @return static
	 */
	public static function newFromData( ...$data ) {
		return new static( Uuid::uuid4(), ...$data );
	}

	/**
	 * @return static
	 */
	public static function newEmpty() {
		return new static( Uuid::uuid4() );
	}

	/**
	 * @param UuidInterface $id
	 * @param mixed ...$data
	 */
	public function __construct( UuidInterface $id, ...$data ) {
		$this->id = $id;
	}

	/**
	 * @param PointInTime $recorded
	 * @return void
	 */
	public function setTimeOfRecording( PointInTime $recorded ) {
		$this->recordedAt = $recorded;
	}

	/**
	 * @return PointInTime|null
	 */
	public function getTimeOfRecording(): ?PointInTime {
		return $this->recordedAt;
	}

	/**
	 * @return UuidInterface
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function toPayload(): array {
		return [
			'id' => $this->id->toString(),
		];
	}

	/**
	 * @param array $payload
	 * @return SerializablePayload
	 */
	public static function fromPayload( array $payload ): SerializablePayload {
		$decodedPayload = static::decodePayloadData( $payload );
		return new static( ...array_values( $decodedPayload ) );
	}

	/**
	 * @param array $payload
	 * @return array
	 */
	protected static function decodePayloadData( array $payload ): array {
		return [
			'id' => Uuid::fromString( $payload['id'] ),
		];
	}
}

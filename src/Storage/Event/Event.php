<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use User;

abstract class Event implements SerializablePayload {
	/** @var UuidInterface */
	private $id;

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
	 * @param User $user
	 */
	public function __construct( UuidInterface $id, ...$data ) {
		$this->id = $id;
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

	public static function fromPayload( array $payload ): SerializablePayload {
		$decodedPayload = static::decodePayloadData( $payload );
		return new static( ...array_values( $decodedPayload ) );
	}

	protected static function decodePayloadData( array $payload ): array {
		return [
			'id' => Uuid::fromString( $payload['id'] ),
		];
	}
}

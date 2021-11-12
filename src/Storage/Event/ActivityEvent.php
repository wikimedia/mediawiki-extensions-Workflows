<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;
use Ramsey\Uuid\UuidInterface;
use User;

class ActivityEvent extends Event {
	use ElementTrait;
	use ActorTrait;

	/** @var array|null */
	private $data;

	public function __construct(
		UuidInterface $id, $elementId, ?User $actor = null, $data = null
	) {
		parent::__construct( $id );
		$this->elementID = $elementId;
		$this->actor = $actor;
		$this->data = $data;
	}

	/**
	 * @return array|null
	 */
	public function getData(): ?array {
		return $this->data;
	}

	public function toPayload(): array {
		return array_merge( [
			'data' => $this->data,
			'elementId' => $this->elementID,
			'actor' => $this->actorToPayload(),
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::elementIdFromPayload( $payload ),
			static::actorFromPayload( $payload ),
			$payload['data'],
		];
	}
}

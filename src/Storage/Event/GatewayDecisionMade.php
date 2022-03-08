<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;
use Ramsey\Uuid\UuidInterface;

final class GatewayDecisionMade extends Event {
	use ElementTrait;

	/** @var null */
	private $nextRef = null;

	public function __construct( UuidInterface $id, $taskId, $nextRef ) {
		parent::__construct( $id );
		$this->elementID = $taskId;
		$this->nextRef = $nextRef;
	}

	/**
	 * @return string|null
	 */
	public function getNextRef(): ?string {
		return $this->nextRef;
	}

	public function toPayload(): array {
		return array_merge( [
			'nextRef' => $this->nextRef,
			'elementId' => $this->elementID,
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::elementIdFromPayload( $payload ),
			$payload['nextRef']
		];
	}
}

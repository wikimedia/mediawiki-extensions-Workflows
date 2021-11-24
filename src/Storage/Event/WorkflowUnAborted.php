<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use Ramsey\Uuid\UuidInterface;
use User;

final class WorkflowUnAborted extends Event {
	use ActorTrait;

	/** @var array */
	private $reason;

	public function __construct( UuidInterface $id, User $actor, $reason ) {
		parent::__construct( $id );
		$this->actor = $actor;
		$this->reason = $reason;
	}

	public function getReason() {
		return $this->reason;
	}

	public function toPayload(): array {
		return array_merge( [
			'reason' => $this->reason,
			'actor' => $this->actorToPayload(),
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::actorFromPayload( $payload ),
			$payload['reason'],
		];
	}
}

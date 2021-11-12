<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use Ramsey\Uuid\UuidInterface;
use User;

final class WorkflowStarted extends Event {
	use ActorTrait;

	/** @var array */
	private $contextData;

	/**
	 * @param UuidInterface $id
	 * @param array $contextData
	 */
	public function __construct( UuidInterface $id, User $actor, $contextData = [] ) {
		parent::__construct( $id );
		$this->actor = $actor;
		$this->contextData = $contextData;
	}

	/**
	 * @return array
	 */
	public function getContextData(): array {
		return $this->contextData;
	}

	public function toPayload(): array {
		return array_merge( [
			'contextData' => $this->contextData,
			'actor' => $this->actorToPayload(),
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::actorFromPayload( $payload ),
			$payload['contextData'],
		];
	}
}

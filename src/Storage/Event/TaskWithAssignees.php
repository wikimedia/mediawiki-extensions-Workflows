<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\User\User;
use Ramsey\Uuid\UuidInterface;

abstract class TaskWithAssignees extends ActivityEvent {
	/** @var array */
	private $assignees;

	/**
	 * @param UuidInterface $id
	 * @param string $elementId
	 * @param array|null $assignees
	 * @param User|null $actor
	 * @param null $data
	 */
	public function __construct(
		UuidInterface $id, $elementId, $assignees = [], ?User $actor = null, $data = null
	) {
		parent::__construct( $id, $elementId, $actor, $data );
		$this->assignees = $assignees;
	}

	/**
	 * @return array
	 */
	public function getAssignees(): array {
		return $this->assignees;
	}

	/**
	 * @return array
	 */
	public function toPayload(): array {
		return array_merge( [
			'assignees' => $this->assignees,
		], parent::toPayload() );
	}

	/**
	 * @param array $payload
	 *
	 * @return array
	 */
	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			'id' => $data['id'],
			'elementId' => $data['elementId'],
			'assignees' => $data['assignees'] ?? [],
			'actor' => $data['actor'],
			'data' => $data['data'],
		];
	}
}

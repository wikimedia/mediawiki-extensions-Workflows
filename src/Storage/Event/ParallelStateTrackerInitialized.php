<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Definition\ITask;
use Ramsey\Uuid\UuidInterface;

final class ParallelStateTrackerInitialized extends Event {
	/** @var ITask[] */
	private $tasks;

	/**
	 * @param UuidInterface $id
	 * @param ITask[] $taskIds
	 */
	public function __construct( UuidInterface $id, $taskIds ) {
		parent::__construct( $id );
		$this->tasks = $taskIds;
	}

	/**
	 * @return array
	 */
	public function getTasks(): array {
		return $this->tasks;
	}

	public function toPayload(): array {
		return array_merge( [
			'tasks' => $this->tasks
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			$payload['tasks']
		];
	}
}

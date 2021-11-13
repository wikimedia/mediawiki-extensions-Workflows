<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Definition\ITask;
use Ramsey\Uuid\UuidInterface;

final class ParallelMultiInstanceStateTrackerInitialized extends Event {
	/** @var ITask */
	private $task;

	/**
	 * @param UuidInterface $id
	 * @param ITask $taskId
	 */
	public function __construct( UuidInterface $id, $taskId ) {
		parent::__construct( $id );
		$this->task = $taskId;
	}

	/**
	 * @return array
	 */
	public function getTask(): string {
		return $this->task;
	}

	public function toPayload(): array {
		return array_merge( [
			'task' => $this->task
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			$payload['task']
		];
	}
}

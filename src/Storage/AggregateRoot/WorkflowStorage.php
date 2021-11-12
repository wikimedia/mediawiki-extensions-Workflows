<?php

namespace MediaWiki\Extension\Workflows\Storage\AggregateRoot;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Generator;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\Event;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventDispatcher;

class WorkflowStorage implements AggregateRoot {
	use AggregateRootBehaviour;

	/** @var array */
	private $replayedEvents = [];

	public static function newInstance() {
		$id = WorkflowId::newWorkflowId();
		return new static( $id );
	}

	public function __construct( WorkflowId $id ) {
		$this->aggregateRootId = $id;
	}

	public function recordEvent( Event $event ) {
		$this->recordThat( $event );
	}

	/**
	 * @return Event[]
	 */
	public function getAllEvents(): array {
		return array_merge( $this->recordedEvents, $this->replayedEvents );
	}

	protected function apply( object $event ): void {
		++$this->aggregateRootVersion;

		$this->replayedEvents[] = $event;
	}

	public static function recreateFromEvents(
		AggregateRootId $aggregateRootId, Generator $events, WorkflowEventDispatcher $dispatcher = null
	): AggregateRoot {
		$aggregateRoot = new static( $aggregateRootId );

		/** @var Event $event */
		foreach ( $events as $event ) {
			$aggregateRoot->apply( $event );
			if ( $dispatcher ) {
				$dispatcher->dispatch( $event, $aggregateRootId );
			}
		}

		$aggregateRoot->aggregateRootVersion = $events->getReturn() ?: 0;

		/* @var AggregateRoot $aggregateRoot */
		return $aggregateRoot;
	}
}

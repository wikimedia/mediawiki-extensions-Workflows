<?php

namespace MediaWiki\Extension\Workflows\Storage;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\WorkflowStorage;
use MediaWiki\Extension\Workflows\Storage\MessageDispatcher\WorkflowMessageDispatcher;
use MediaWiki\Extension\Workflows\Storage\MessageRepository\WorkflowMessageRepository;
use MediaWiki\Extension\Workflows\Workflow;

class WorkflowEventRepository implements AggregateRootRepository {
	/** @var WorkflowMessageRepository */
	private $messages;
	/** @var MessageDecorator */
	private $decorator;
	/** @var WorkflowMessageDispatcher */
	private $dispatcher;
	/** @var WorkflowEventDispatcher */
	private $replayDispatcher;

	public function __construct(
		WorkflowMessageRepository $messageRepository,
		WorkflowMessageDispatcher $dispatcher = null,
		MessageDecorator $decorator = null
	) {
		$this->messages = $messageRepository;
		$this->dispatcher = $dispatcher ?: WorkflowMessageDispatcher::newFromRegisteredListeners();
		$this->decorator = $decorator ?: new DefaultHeadersDecorator( new WorkflowEventClassInflector() );
		// For internal use only!
		$this->replayDispatcher = new WorkflowEventDispatcher();
	}

	/**
	 * Get all workflow IDs, current and past
	 *
	 * @return WorkflowId[]
	 */
	public function retrieveAllIds(): array {
		return $this->messages->getAvailableWorkflows();
	}

	/**
	 * @param Consumer $consumer
	 */
	public function addConsumerToDispatcher( Consumer $consumer ) {
		$this->dispatcher->addConsumer( $consumer );
	}

	public function addReplayConsumer( ReplayConsumer $consumer ) {
		$this->replayDispatcher->addConsumer( $consumer );
	}

	/**
	 * Do not use directly, unless you really mean to!
	 * @protected
	 * @param AggregateRootId $aggregateRootId
	 * @return WorkflowStorage
	 */
	public function retrieve( AggregateRootId $aggregateRootId ): object {
		$this->assertHasId( $aggregateRootId );
		$events = $this->retrieveAllEvents( $aggregateRootId );

		return WorkflowStorage::recreateFromEvents(
			$aggregateRootId, $events, $this->replayDispatcher
		);
	}

	public function retrieveMessages( AggregateRootId $aggregateRootId ) {
		return $this->messages->retrieveAll( $aggregateRootId );
	}

	/**
	 * Internal use only!
	 * @protected
	 * @param Workflow $workflow
	 * @param mixed ...$dependencies
	 */
	public function setWorkflowForReplay( Workflow $workflow, ...$dependencies ) {
		$this->replayDispatcher->setWorkflow( $workflow, ...$dependencies );
	}

	private function retrieveAllEvents( AggregateRootId $aggregateRootId ) {
		/** @var Generator<Message> $messages */
		$messages = $this->messages->retrieveAll( $aggregateRootId );

		foreach ( $messages as $message ) {
			$event = $message->event();
			$event->setTimeOfRecording( $message->timeOfRecording() );
			yield $event;
		}

		return $messages->getReturn();
	}

	public function persist( object $aggregateRoot ): void {
		$this->persistEvents(
			$aggregateRoot->aggregateRootId(),
			$aggregateRoot->aggregateRootVersion(),
			...$aggregateRoot->releaseEvents()
		);
	}

	public function persistEvents(
		AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events
	): void {
		if ( count( $events ) === 0 ) {
			return;
		}

		// decrease the aggregate root version by the number of raised events
		// so the version of each message represents the version at the time
		// of recording.
		$aggregateRootVersion = $aggregateRootVersion - count( $events );
		$metadata = [ Header::AGGREGATE_ROOT_ID => $aggregateRootId ];
		$messages = array_map( function ( object $event ) use ( $metadata, &$aggregateRootVersion ) {
			return $this->decorator->decorate( new Message(
				$event,
				$metadata + [ Header::AGGREGATE_ROOT_VERSION => ++$aggregateRootVersion ]
			) );
		}, $events );

		$this->messages->persist( ...$messages );
		$this->dispatcher->dispatch( ...$messages );
	}

	private function assertHasId( AggregateRootId $aggregateRootId ) {
		foreach ( $this->retrieveAllIds() as $id ) {
			if ( $id->toString() === $aggregateRootId->toString() ) {
				return;
			}
		}

		throw new \Exception( "Workflow instance {$aggregateRootId->toString()} not found" );
	}
}

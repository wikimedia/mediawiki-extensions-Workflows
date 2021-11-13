<?php

namespace MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id;

use EventSauce\EventSourcing\AggregateRootId;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WorkflowId implements AggregateRootId {
	/** @var UuidInterface|null */
	private $workflowId = null;

	/**
	 * @return WorkflowId
	 */
	public static function newWorkflowId(): WorkflowId {
		return new static( Uuid::uuid4() );
	}

	/**
	 * @param string $aggregateRootId
	 * @return WorkflowId
	 */
	public static function fromString( string $aggregateRootId ): AggregateRootId {
		$uuid = Uuid::fromString( $aggregateRootId );
		return new static( $uuid );
	}

	/**
	 * @param UuidInterface $id
	 */
	public function __construct( UuidInterface $id ) {
		$this->workflowId = $id;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function toString(): string {
		if ( $this->workflowId === null ) {
			throw new Exception( 'Attempted to retrieve WorkflowID from null object' );
		}
		return $this->workflowId->toString();
	}
}

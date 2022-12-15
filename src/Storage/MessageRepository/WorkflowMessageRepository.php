<?php

namespace MediaWiki\Extension\Workflows\Storage\MessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use FormatJson;
use Generator;
use MediaWiki\Extension\Workflows\Exception\MessagePersistanceException;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventClassInflector;
use Ramsey\Uuid\Uuid;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

class WorkflowMessageRepository implements MessageRepository {
	/** @var ILoadBalancer */
	private $lb;
	/** @var MessageSerializer */
	private $serializer;
	/** @var string */
	private $tableName = 'workflows_event';
	/** @var null */
	private $validWorkflowIds = null;

	/**
	 * @param ILoadBalancer $lb
	 * @return static
	 */
	public static function newRepository( ILoadBalancer $lb ) {
		$serializer = new ConstructingMessageSerializer( new WorkflowEventClassInflector() );

		return new static( $lb, $serializer );
	}

	/**
	 * @param ILoadBalancer $lb
	 * @param MessageSerializer $serializer
	 */
	public function __construct( ILoadBalancer $lb, MessageSerializer $serializer ) {
		$this->lb = $lb;
		$this->serializer = $serializer;
	}

	public function persist( Message ...$messages ) {
		$values = [];
		foreach ( $messages as $message ) {
			$values[] = $this->getMessageData( $message );
		}

		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->insert(
			$this->tableName,
			$values,
			__METHOD__
		);

		if ( !$res ) {
			throw new MessagePersistanceException( $this );
		}
	}

	/**
	 * @return WorkflowId[]
	 */
	public function getAvailableWorkflows(): array {
		if ( $this->validWorkflowIds === null ) {
			$res = $this->lb->getConnection( DB_REPLICA )->select(
				$this->tableName,
				[ 'DISTINCT( wfe_aggregate_root_id ) as workflow_id' ],
				[],
				__METHOD__
			);

			$this->validWorkflowIds = [];
			foreach ( $res as $row ) {
				$this->validWorkflowIds[] = $row->workflow_id;
			}
		}

		return $this->toWorkflowIds( $this->validWorkflowIds );
	}

	private function toWorkflowIds( $array ) {
		return array_map( static function ( $id ) {
			return WorkflowId::fromString( $id );
		}, $array );
	}

	/**
	 * Internal use only. Do not call directly!
	 *
	 * @param AggregateRootId $id
	 * @return Generator
	 */
	public function retrieveAll( AggregateRootId $id ): Generator {
		return $this->fetchMessages( [
			'wfe_aggregate_root_id' => $id->toString(),
		] );
	}

	public function retrieveAllAfterVersion( AggregateRootId $id, int $aggregateRootVersion ): Generator {
		return $this->fetchMessages( [
			'wfe_aggregate_root_id' => $id->toString(),
			'wfe_aggregate_root_version > ' . $aggregateRootVersion,
		] );
	}

	private function fetchMessages( $conds ) {
		$rows = $this->lb->getConnection( DB_REPLICA )->select(
			$this->tableName,
			[ 'wfe_payload' ],
			$conds,
			__METHOD__,
			[
				'ORDER BY' => 'wfe_aggregate_root_id ASC'
			]
		);

		return $this->yieldMessage( $rows );
	}

	private function getMessageData( Message $message ): array {
		$payload = $this->serializer->serializeMessage( $message );
		// phpcs:ignore MediaWiki.Usage.AssignmentInReturn.AssignmentInReturn
		return [
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'wfe_event_id' => $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString(),
			'wfe_event_type' => $payload['headers'][Header::EVENT_TYPE] ?? null,
			'wfe_aggregate_root_id' => $payload['headers'][Header::AGGREGATE_ROOT_ID] ?? null,
			'wfe_aggregate_root_version' => $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0,
			'wfe_time_of_recording' => $payload['headers'][Header::TIME_OF_RECORDING],
			'wfe_payload' => FormatJson::encode( $payload )
		];
	}

	private function yieldMessage( IResultWrapper $rows ) {
		// This uses Generator syntax => https://www.php.net/manual/en/language.generators.syntax.php
		foreach ( $rows as $row ) {
			$messages = $this->serializer->unserializePayload( FormatJson::decode( $row->wfe_payload, true ) );
			foreach ( $messages as $message ) {
				if ( $message instanceof Message ) {
					yield $message;
				}
			}
		}

		return isset( $message )
			? ( $message->header( Header::AGGREGATE_ROOT_VERSION ) ?: 0
			) : 0;
	}
}

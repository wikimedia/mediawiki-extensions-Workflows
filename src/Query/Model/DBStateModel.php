<?php

namespace MediaWiki\Extension\Workflows\Query\Model;

use EventSauce\EventSourcing\PointInTime;
use MediaWiki\Extension\Workflows\Query\WorkflowStateModel;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\Event;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowAborted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowStarted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowUnAborted;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventClassInflector;
use MediaWiki\Extension\Workflows\Workflow;

final class DBStateModel implements WorkflowStateModel {
	/** @var WorkflowEventClassInflector */
	private $inflector;
	/** @var WorkflowId */
	private $workflowId;
	/** @var string */
	private $state;
	/** @var string */
	private $lastEvent;
	/** @var int|null */
	private $initiator;
	/** @var string */
	private $started;
	/** @var string */
	private $touched;
	/** @var array */
	private $payload;

	public static function newFromRow( $row ) {
		return new static(
			WorkflowId::fromString( $row->wfs_workflow_id ),
			$row->wfs_state,
			$row->wfs_last_event,
			$row->wfs_started,
			(int)$row->wfs_initiator,
			$row->wfs_touched,
			$row->wfs_payload
		);
	}

	/**
	 * @param WorkflowId $workflowId
	 * @param string $state
	 * @param string $lastEvent
	 * @param string $started
	 * @param null $initiator
	 * @param string|null $touched
	 * @param string|array|null $payload
	 */
	public function __construct(
		WorkflowId $workflowId, $state, $lastEvent, $started,
		$initiator = null, $touched = null, $payload = []
	) {
		$this->inflector = new WorkflowEventClassInflector();

		$this->workflowId = $workflowId;
		$this->state = $state;
		$this->lastEvent = $lastEvent !== null ? $this->inflector->typeToClassName( $lastEvent ) : null;
		$this->initiator = $initiator;
		$this->started = $started;
		$this->touched = $touched;
		if ( is_string( $payload ) ) {
			$payload = json_decode( $payload, 1 );
		}
		if ( is_array( $payload ) ) {
			$this->payload = $payload;
		}
	}

	/**
	 * @return WorkflowId
	 */
	public function getWorkflowId(): WorkflowId {
		return $this->workflowId;
	}

	/**
	 * @return string
	 */
	public function getState(): string {
		return $this->state;
	}

	/**
	 * @return array
	 */
	public function getPayload(): array {
		return is_array( $this->payload ) ? $this->payload : [];
	}

	/**
	 * @return string
	 */
	public function getTouched(): string {
		return $this->touched;
	}

	/**
	 * @return string
	 */
	public function getStarted(): string {
		return $this->started;
	}

	/**
	 * @inheritDoc
	 */
	public function serialize(): array {
		return [
			'wfs_workflow_id' => $this->workflowId->toString(),
			'wfs_state' => $this->state,
			'wfs_last_event' => $this->inflector->classNameToType( $this->lastEvent ),
			'wfs_initiator' => $this->initiator ? $this->initiator : null,
			'wfs_started' => $this->started,
			'wfs_touched' => $this->touched,
			'wfs_payload' => json_encode( $this->payload ),
		];
	}

	/**
	 * @param Event $event
	 */
	public function handleEvent( Event $event ) {
		$this->lastEvent = get_class( $event );
		if ( $event instanceof WorkflowInitialized ) {
			$this->payload['definition'] = $event->getDefinitionSource();
			if ( $event->getTimeOfRecording() instanceof PointInTime ) {
				$this->started = $event->getTimeOfRecording()->dateTime()->format( 'YmdHis' );
			}
		}
		if ( $event instanceof WorkflowStarted ) {
			$this->state = Workflow::STATE_RUNNING;
			$this->initiator = $event->getActor()->getId();
			$context = $event->getContextData();
			if ( isset( $context['pageId'] ) ) {
				$this->pageAffected = $context['pageId'];
			}
			$this->payload['context'] = $context;
		}
		if ( $event instanceof WorkflowAborted ) {
			$this->state = Workflow::STATE_ABORTED;
		}
		if ( $event instanceof WorkflowUnAborted ) {
			$this->state = Workflow::STATE_RUNNING;
		}
		if ( $event instanceof WorkflowEnded ) {
			$this->state = Workflow::STATE_FINISHED;
		}
		if ( $event instanceof TaskCompleted ) {
			$this->payload[$event->getElementId()] = $event->getData();
		}

		if ( $event->getTimeOfRecording() instanceof PointInTime ) {
			$this->touched = $event->getTimeOfRecording()->dateTime()->format( 'YmdHis' );
		}
	}
}

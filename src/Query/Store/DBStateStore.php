<?php

namespace MediaWiki\Extension\Workflows\Query\Store;

use EventSauce\EventSourcing\Message;
use Exception;
use MediaWiki\Extension\Workflows\Query\Model\DBStateModel;
use MediaWiki\Extension\Workflows\Query\WorkflowStateModel;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\Event;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventClassInflector;
use MediaWiki\Extension\Workflows\Workflow;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

final class DBStateStore implements WorkflowStateStore {
	private const TABLE = 'workflows_state';

	/** @var ILoadBalancer */
	private $lb;
	/** @var WorkflowEventClassInflector */
	private $inflector;
	/** @var WorkflowStateModel[] */
	private $models = [];
	/** @var WorkflowId[] */
	private $inserted = [];
	/** @var array */
	private $conditions = [];
	/** @var array */
	private $options = [];

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->lb = $loadBalancer;
		$this->inflector = new WorkflowEventClassInflector();
	}

	/**
	 * @inheritDoc
	 */
	public function all(): WorkflowStateStore {
		// No condition added
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function active(): WorkflowStateStore {
		$this->conditions['wfs_state'] = Workflow::STATE_RUNNING;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function onEvent( $event ): WorkflowStateStore {
		$this->conditions['wfs_last_event'] = $this->inflector->classNameToType( $event );
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function initiatedByUser( User $user ): WorkflowStateStore {
		$this->conditions['wfs_initiator'] = $user->getId();
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function complexQuery( $filter, $returnModel = false ): array {
		$ids = $this->query( $returnModel );
		$workSet = [];
		if ( $returnModel ) {
			$workSet = $ids;
		} else {
			foreach ( $ids as $id ) {
				$workSet[] = $this->getModel( $id );
			}
		}

		$filtered = [];
		foreach ( $workSet as $model ) {
			if ( $this->matchesFilter( $model, $filter ) ) {
				$filtered[] = $returnModel ? $model : $model->getWorkflowId();
			}
		}

		return $filtered;
	}

	/**
	 * @inheritDoc
	 */
	public function query( $returnModel = false ): array {
		$db = $this->lb->getConnection( DB_REPLICA );
		$res = $db->select(
			static::TABLE,
			[ 'wfs_workflow_id' ],
			$this->conditions,
			__METHOD__,
			$this->options
		);

		$return = [];
		foreach ( $res as $row ) {
			$id = WorkflowId::fromString( $row->wfs_workflow_id );
			if ( $returnModel ) {
				$return[] = $this->getModel( $id );
			} else {
				$return[] = $id;
			}
		}

		// reset conditions
		$this->conditions = [];
		return $return;
	}

	/**
	 * Handle message that has been fired
	 * @param Message $message
	 */
	public function handle( Message $message ) {
		$event = $message->event();
		$event->setTimeOfRecording( $message->timeOfRecording() );
		$this->processEvent( $event, $message->aggregateRootId() );
	}

	/**
	 * Only use to rebuild state data, not called in normal operation
	 *
	 * @param Event $event
	 * @param WorkflowId $id
	 * @return mixed|void
	 */
	public function handleReplayEvent( $event, WorkflowId $id ) {
		$this->processEvent( $event, $id );
	}

	/**
	 * @param Event $event
	 * @param WorkflowId $id
	 * @throws Exception
	 */
	private function processEvent( $event, WorkflowId $id ) {
		$model = $this->getModel( $id );
		$model->handleEvent( $event );
		$this->persistModel( $model );
	}

	/**
	 * @param WorkflowId $id
	 * @return DBStateModel|WorkflowStateModel
	 * @throws Exception
	 */
	private function getModel( WorkflowId $id ) {
		if ( !isset( $this->models[$id->toString()] ) ) {
			$this->models[$id->toString()] = $this->loadModel( $id );
		}
		return $this->models[$id->toString()];
	}

	/**
	 * @param WorkflowId $id
	 * @return DBStateModel
	 * @throws Exception
	 */
	private function loadModel( WorkflowId $id ): DBStateModel {
		$res = $this->lb->getConnection( DB_REPLICA )->selectRow(
			static::TABLE,
			'*',
			[ 'wfs_workflow_id' => $id->toString() ]
		);

		if ( !$res ) {
			return new DBStateModel( $id, Workflow::STATE_NOT_STARTED, null, '' );
		}

		$this->inserted[] = $id;
		return DBStateModel::newFromRow( $res );
	}

	/**
	 * @param DBStateModel $model
	 * @throws Exception
	 */
	private function persistModel( DBStateModel $model ) {
		if ( in_array( $model->getWorkflowId(), $this->inserted ) ) {
			$this->updateModel( $model );
		} else {
			$this->insertModel( $model );
		}
	}

	/**
	 * @param DBStateModel $model
	 * @throws Exception
	 */
	private function updateModel( DBStateModel $model ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->update(
			static::TABLE,
			$model->serialize(),
			[ 'wfs_workflow_id' => $model->getWorkflowId()->toString() ],
			__METHOD__
		);

		if ( !$res ) {
			throw new Exception(
				'Could not update state model for workflow id ' . $model->getWorkflowId()->toString()
			);
		}
	}

	/**
	 * @param DBStateModel $model
	 * @throws Exception
	 */
	private function insertModel( DBStateModel $model ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->insert(
			static::TABLE,
			$model->serialize(),
			__METHOD__
		);

		$this->inserted[] = $model->getWorkflowId();
		if ( !$res ) {
			throw new Exception(
				'Could not insert state model for workflow id ' . $model->getWorkflowId()->toString()
			);
		}
	}

	/**
	 * @param WorkflowStateModel $model
	 * @param array $filter
	 * @return bool
	 */
	private function matchesFilter( $model, $filter ) {
		foreach ( $filter as $field => $filterData ) {
			switch ( $field ) {
				case 'context':
					if ( !$this->matchContextFilter( $model->getPayload(), $filterData ) ) {
						return false;
					}
					break;
				case 'state':
					if ( !$this->matchState( $model->getState(), $filterData ) ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * @param array $payload
	 * @param array $filterData
	 * @return bool
	 */
	private function matchContextFilter( array $payload, array $filterData ) {
		foreach ( $filterData as $valueKey => $valueItem ) {
			if ( !isset( $payload['context'][$valueKey] ) ) {
				continue;
			}
			if ( $payload['context'][$valueKey] !== $valueItem ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $state
	 * @param array $filterData
	 * @return bool
	 */
	private function matchState( string $state, array $filterData ) {
		switch ( $filterData['type'] ) {
			case 'string':
				$value = mb_strtolower( $state );
				$test = mb_strtolower( $filterData['value'] );
				switch ( $filterData['operator'] ) {
					case 'ct':
						return strpos( $value, $test ) !== false;
					case 'eq':
					default:
						return $value === $test;
				}
			case 'list':
				return in_array( $state, $filterData['value'] );
		}

		return false;
	}
}

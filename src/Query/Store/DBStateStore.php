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
	public function complexQuery( $filter ): array {
		$ids = $this->query();
		$workSet = [];
		foreach ( $ids as $id ) {
			$workSet[] = $this->getModel( $id );
		}

		$filtered = [];
		foreach ( $workSet as $model ) {
			if ( $this->matchesFilter( $model, $filter ) ) {
				$filtered[] = $model->getWorkflowId();
			}
		}

		return $filtered;
	}

	/**
	 * @inheritDoc
	 */
	public function query(): array {
		$db = $this->lb->getConnection( DB_REPLICA );
		$res = $db->select(
			static::TABLE,
			[ 'wfs_workflow_id' ],
			$this->conditions,
			__METHOD__
		);

		$ids = [];
		foreach ( $res as $row ) {
			$ids[] = WorkflowId::fromString( $row->wfs_workflow_id );
		}

		// reset conditions
		$this->conditions = [];
		return $ids;
	}

	/**
	 * Handle message that has been fired
	 * @param Message $message
	 */
	public function handle( Message $message ) {
		$this->processEvent( $message->event(), $message->aggregateRootId() );
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
			return new DBStateModel( $id, Workflow::STATE_NOT_STARTED, null );
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
		$db = $this->lb->getConnection( DB_MASTER );
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
		$db = $this->lb->getConnection( DB_MASTER );
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
		$payload = $model->getPayload();

		foreach ( $filter as $key => $value ) {
			if ( !isset( $payload[$key] ) ) {
				continue;
			}
			foreach ( $value as $valueKey => $valueItem ) {
				if ( !isset( $payload[$key][$valueKey] ) ) {
					continue;
				}
				if ( $payload[$key][$valueKey] !== $valueItem ) {
					return false;
				}
			}
		}

		return true;
	}
}

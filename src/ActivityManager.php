<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;
use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\DataPreprocessorContext;

final class ActivityManager {

	/** @var Workflow */
	private $workflow;
	/** @var LogicObjectFactory */
	private $logicObjectFactory;
	/** @var DataPreprocessor */
	private $preprocessor;
	/** @var IActivity[] */
	private $activities = [];
	/** @var array */
	private $properties = [];
	/** @var array */
	private $states = [];
	/** @var array */
	private $stale = [];

	/**
	 * @param LogicObjectFactory $logicObjectFactory
	 * @param DataPreprocessor $preprocessor
	 */
	public function __construct(
		LogicObjectFactory $logicObjectFactory, DataPreprocessor $preprocessor
	) {
		$this->logicObjectFactory = $logicObjectFactory;
		$this->preprocessor = $preprocessor;
	}

	/**
	 * @param Workflow $workflow
	 */
	public function setWorkflow( Workflow $workflow ) {
		$this->workflow = $workflow;
	}

	/**
	 * @param ITask $task
	 * @return IActivity
	 * @throws Exception
	 */
	public function getActivityForTask( ITask $task ) {
		if (
			!isset( $this->activities[$task->getId()] ) ||
			isset( $this->stale[$task->getId()] )
		) {
			$this->activities[$task->getId()] = $this->logicObjectFactory->getActivityForTask(
				$task
			);
			$this->properties[$task->getId()] = $task->getDataProperties();
			$this->states[$task->getId()] = IActivity::STATUS_NOT_STARTED;
			unset( $this->stale[$task->getId()] );
		}

		return $this->activities[$task->getId()];
	}

	/**
	 * @param IActivity $activity
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function startActivity( IActivity $activity ) {
		$this->assertMembers( $activity );
		// Process (parse) activity properties before starting
		$this->setActivityProperties(
			$activity,
			$this->parseValues( $this->getActivityProperties( $activity ) )
		);
		$activity->start(
			$this->getActivityProperties( $activity ), $this->workflow->getContext()
		);
		$this->states[$activity->getTask()->getId()] = IActivity::STATUS_STARTED;
		$this->trySetDueDate( $activity );

		return true;
	}

	/**
	 * @param IActivity $activity
	 * @return mixed
	 * @throws WorkflowExecutionException
	 */
	public function getActivityStatus( IActivity $activity ) {
		$this->assertMembers( $activity );
		return $this->states[$activity->getTask()->getId()];
	}

	/**
	 * @param IActivity $activity
	 * @param ExecutionStatus $status
	 * @throws WorkflowExecutionException
	 */
	public function setActivityStatus( IActivity $activity, $status ) {
		$this->assertMembers( $activity );
		$this->states[$activity->getTask()->getId()] = $status;
	}

	/**
	 * @param IActivity $activity
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	public function getActivityProperties( IActivity $activity ): array {
		$this->assertMembers( $activity );
		return $this->properties[$activity->getTask()->getId()] ?? [];
	}

	/**
	 * @param IActivity $activity
	 * @param mixed $data
	 * @throws WorkflowExecutionException
	 */
	public function setActivityProperties( IActivity $activity, $data ) {
		$this->assertMembers( $activity );
		$this->updateActivityProperties( $activity, $data );
	}

	/**
	 * @param IActivity $activity
	 * @param mixed $data
	 * @param WorkflowContext $context
	 * @return ExecutionStatus
	 * @throws WorkflowExecutionException
	 */
	public function completeActivity( IActivity $activity, $data, WorkflowContext $context ) {
		$this->assertMembers( $activity );
		$this->updateActivityProperties( $activity, $this->parseValues( $data ) );
		$status = $activity->execute( $this->getActivityProperties( $activity ), $context );
		if ( !$status instanceof ExecutionStatus ) {
			throw new WorkflowExecutionException(
				'Activity execution must return instance of ' . ExecutionStatus::class
			);
		}
		if ( !$status instanceof ExecutionStatus\IntermediateExecutionStatus ) {
			$this->setActivityStatusFromExecutionStatus( $activity, $status );
		}
		$this->updateActivityProperties( $activity, $this->parseValues( $status->getPayload() ) );

		return $status;
	}

	public function probeActivityStatus( IActivity $activity ): bool {
		$status = $activity->probe();
		if ( !$status instanceof ExecutionStatus ) {
			return false;
		}
		if ( $this->hasDataChange( $activity, $status ) ) {
			$this->setActivityStatusFromExecutionStatus( $activity, $status );
			$this->updateActivityProperties( $activity, $this->parseValues( $status->getPayload() ) );
			return true;
		}

		return false;
	}

	/**
	 * Marking activity as stale flags it for re-instantiation on next call
	 *
	 * @param IActivity $activity
	 */
	public function markStale( IActivity $activity ) {
		if ( isset( $this->activities[$activity->getTask()->getId()] ) ) {
			$this->stale[$activity->getTask()->getId()] = true;
		}
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @return string[]|null
	 * @throws WorkflowExecutionException
	 */
	public function getTargetUsersForActivity( UserInteractiveActivity $activity ) {
		$properties = $this->getActivityProperties( $activity );

		$activityTarget = $activity->getTargetUsers( $properties );
		if ( $activityTarget === null && isset( $properties['assigned_user'] ) ) {
			return explode( ',', $properties['assigned_user'] );
		}

		return $activityTarget;
	}

	/**
	 * @param IActivity $activity
	 * @param array $data
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	public function getValidatedData( IActivity $activity, $data ) {
		$properties = $this->getActivityProperties( $activity );
		$properties = array_keys( $properties );
		$internals = $activity->getTask()->getExtensionElements()['_internal_properties'] ?? [];
		$publicValid = array_diff( $properties, $internals );

		return array_filter(
			$data,
			static function ( $key ) use ( $publicValid ) {
				return in_array( $key, $publicValid );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get list of property values that is safe to send to client
	 *
	 * @param IActivity $activity
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	public function getActivityPublicProperties( IActivity $activity ) {
		$internalProperties = $activity->getTask()->getExtensionElements()['_internal_properties'] ?? [];
		return array_filter(
			$this->getActivityProperties( $activity ),
			static function ( $i ) use ( $internalProperties ) {
				return !in_array( $i, $internalProperties );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * @param IActivity $activity
	 * @param ExecutionStatus $status
	 * @throws WorkflowExecutionException
	 */
	private function setActivityStatusFromExecutionStatus(
		IActivity $activity, ExecutionStatus $status
	) {
		$activityStatus = $status->getStatus();
		$this->setActivityStatus( $activity, $activityStatus );
	}

	/**
	 * Check if ExecutionStatus contains any data that
	 * is different than what is already set
	 *
	 * @param IActivity $activity
	 * @param ExecutionStatus $status
	 * @return bool
	 */
	private function hasDataChange( IActivity $activity, ExecutionStatus $status ): bool {
		if (
			$status->getStatus() === IActivity::STATUS_COMPLETE &&
			$this->getActivityStatus( $activity ) !== IActivity::STATUS_COMPLETE
		) {
			return true;
		}
		$newProperties = $status->getPayload();
		foreach ( $this->getActivityProperties( $activity ) as $key => $value ) {
			if ( !isset( $newProperties[$key] ) ) {
				continue;
			}
			if ( $newProperties[$key] !== $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param IActivity $activity
	 * @throws WorkflowExecutionException
	 */
	private function assertMembers( IActivity $activity ) {
		if ( !isset( $this->activities[$activity->getTask()->getId()] ) ) {
			throw new WorkflowExecutionException(
				'Manager does not manage this activity', $activity->getTask()
			);
		}
	}

	/**
	 * @param IActivity $activity
	 * @param array $data
	 */
	private function updateActivityProperties( IActivity $activity, $data ) {
		foreach ( $this->properties[$activity->getTask()->getId()] as $property => &$value ) {
			if ( isset( $data[$property] ) ) {
				$value = $data[$property];
				if ( $property === 'due_date' ) {
					$this->trySetDueDate( $activity );
				}
			}
		}
	}

	/**
	 * @param array $values
	 * @return mixed
	 */
	private function parseValues( $values ) {
		$context = DataPreprocessorContext::newFromWorkflowContext(
			$this->workflow->getContext()
		);
		return $this->preprocessor->preprocess(
			$values,
			$this->workflow->getContext()->getFlatRunningData(),
			$context
		);
	}

	/**
	 * @param IActivity $activity
	 * @throws WorkflowExecutionException
	 */
	public function trySetDueDate( IActivity $activity ) {
		if ( !$activity instanceof UserInteractiveActivity ) {
			return;
		}
		$properties = $this->getActivityProperties( $activity );
		if ( !isset( $properties['due_date'] ) || empty( $properties['due_date'] ) ) {
			return;
		}
		$date = DateTime::createFromFormat( 'YmdHis', $properties['due_date'] );
		if ( !$date ) {
			return;
		}
		$activity->setDueDate( $date );
	}
}

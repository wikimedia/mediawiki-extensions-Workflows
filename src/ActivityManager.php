<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;
use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowPropertyValidationException;
use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\DataPreprocessorContext;
use MediaWiki\User\UserFactory;
use User;

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
	/** @var PropertyValidatorFactory */
	private $propertyValidatorFactory;
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param LogicObjectFactory $logicObjectFactory
	 * @param DataPreprocessor $preprocessor
	 * @param PropertyValidatorFactory $propertyValidatorFactory
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		LogicObjectFactory $logicObjectFactory, DataPreprocessor $preprocessor,
		PropertyValidatorFactory $propertyValidatorFactory, UserFactory $userFactory
	) {
		$this->logicObjectFactory = $logicObjectFactory;
		$this->preprocessor = $preprocessor;
		$this->propertyValidatorFactory = $propertyValidatorFactory;
		$this->userFactory = $userFactory;
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
	 * @param ExecutionStatus|int $status
	 * @throws WorkflowExecutionException
	 */
	public function setActivityStatus( IActivity $activity, $status ) {
		$this->assertMembers( $activity );
		if ( $status instanceof ExecutionStatus ) {
			$status = $status->getStatus();
		}
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
		$this->setActivityStatusFromExecutionStatus( $activity, $status );
		$this->updateActivityProperties( $activity, $this->parseValues( $status->getPayload() ) );

		return $status;
	}

	/**
	 * Check if ongoing activity is completed
	 *
	 * @param IActivity $activity
	 * @param WorkflowContext $context
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function probeActivityStatus( IActivity $activity, WorkflowContext $context ): bool {
		$status = $activity->probe( $this->getActivityProperties( $activity ), $context );
		if ( !$status instanceof ExecutionStatus ) {
			return false;
		}
		$parsedPayload = $this->parseValues( $status->getPayload() );
		if ( $this->hasDataChange( $activity, $status->getStatus(), $parsedPayload ) ) {
			$this->setActivityStatusFromExecutionStatus( $activity, $status );
			$this->updateActivityProperties( $activity, $parsedPayload );
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
	 * @param bool $returnObjects If true, will return User objects
	 * @return User[]|string[]|null
	 * @throws WorkflowExecutionException
	 */
	public function getTargetUsersForActivity( UserInteractiveActivity $activity, $returnObjects = false ) {
		$properties = $this->getActivityProperties( $activity );

		$activityTarget = $activity->getTargetUsers( $properties );
		if ( $activityTarget === null ) {
			if ( isset( $properties['assigned_user'] ) ) {
				// explode and trim to handle comma-separated lists
				$activityTarget = array_map(
					'trim', explode( ',', $properties['assigned_user'] )
				);
			} else {
				return null;
			}
		}

		$validated = array_map( function ( $username ) use ( $returnObjects ) {
			$user = $this->userFactory->newFromName( $username );
			if ( !$user ) {
				return null;
			}
			return $returnObjects ? $user : $user->getName();
		}, $activityTarget );

		return array_filter( $validated, static function ( $user ) {
			return $user !== null;
		} );
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

		$data = array_filter(
			$data,
			static function ( $key ) use ( $publicValid ) {
				return in_array( $key, $publicValid );
			},
			ARRAY_FILTER_USE_KEY
		);

		$validated = [];
		$validation = $activity->getTask()->getExtensionElements()['_property_validators'] ?? [];
		foreach ( $data as $key => $value ) {
			if ( !isset( $validation[$key] ) ) {
				$validated[$key] = $value;
				continue;
			}
			foreach ( $validation[$key] as $validatorKey ) {
				$validator = $this->propertyValidatorFactory->getValidator( $validatorKey );
				if ( !$validator ) {
					throw new WorkflowExecutionException(
						"Validator {$validatorKey}, for property $key, does not exist"
					);
				}
				$toTest = $value;
				if ( !is_array( $toTest ) ) {
					$toTest = [ $toTest ];
				}
				foreach ( $toTest as $element ) {
					if ( !$validator->validate( $element, $activity ) ) {
						throw new WorkflowPropertyValidationException(
							$validator->getError( $element )->text(), $key
						);
					}
				}
			}

			$validated[$key] = $value;
		}

		return $validated;
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
		$this->setActivityStatus( $activity, $status );
	}

	/**
	 * Check if ExecutionStatus contains any data that
	 * is different than what is already set
	 *
	 * @param IActivity $activity
	 * @param int $status
	 * @param array $parsedPayload
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	private function hasDataChange( IActivity $activity, int $status, $parsedPayload ): bool {
		if (
			$status === IActivity::STATUS_COMPLETE &&
			$this->getActivityStatus( $activity ) !== IActivity::STATUS_COMPLETE
		) {
			return true;
		}
		foreach ( $this->getActivityProperties( $activity ) as $key => $value ) {
			if ( !isset( $parsedPayload[$key] ) ) {
				continue;
			}
			if ( $parsedPayload[$key] !== $value ) {
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
			$this->workflow->getContext()->flatSerialize(),
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

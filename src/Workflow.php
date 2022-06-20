<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;
use EventSauce\EventSourcing\AggregateRoot;
use Exception;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus\IntermediateExecutionStatus;
use MediaWiki\Extension\Workflows\Definition\Element\EndEvent;
use MediaWiki\Extension\Workflows\Definition\Element\Gateway;
use MediaWiki\Extension\Workflows\Definition\Element\SequenceFlow;
use MediaWiki\Extension\Workflows\Definition\IElement;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\StateTracker\MultiInstanceStateTracker;
use MediaWiki\Extension\Workflows\StateTracker\ParallelMultiInstanceStateTracker;
use MediaWiki\Extension\Workflows\StateTracker\ParallelStateTracker;
use MediaWiki\Extension\Workflows\StateTracker\SequentialStateTracker;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\WorkflowStorage;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityProbeChange;
use MediaWiki\Extension\Workflows\Storage\Event\Event;
use MediaWiki\Extension\Workflows\Storage\Event\GatewayDecisionMade;
use MediaWiki\Extension\Workflows\Storage\Event\ParallelMultiInstanceStateTrackerInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\ParallelStateTrackerInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\SequentialStateTrackerInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompletionStarted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskExpired;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowAborted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowAutoAborted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowStarted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowUnAborted;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use PermissionsError;
use RequestContext;
use TitleFactory;
use User;

final class Workflow {
	public const STATE_NOT_STARTED = 'not_started';
	public const STATE_RUNNING = 'running';
	public const STATE_FINISHED = 'finished';
	public const STATE_ABORTED = 'aborted';

	private const EXECUTION_MODE_EXECUTING = 'executing';
	private const EXECUTION_MODE_REPLAYING = 'replaying';

	private const _CONTINUE_EXECUTION_FLAG = 1;
	private const _PERSIST_FLAG = 2;

	/** @var string */
	private $state;
	/** @var string|array */
	private $stateMessage = '';
	/** @var IElement[]|null */
	private $current = null;
	/** @var array */
	private $completedTasks = [];
	/** @var WorkflowDefinition */
	private $definition;
	/** @var WorkflowStorage */
	private $storage;
	/** @var LogicObjectFactory */
	private $logicObjectFactory;
	/** @var string */
	private $executionMode;
	/** @var int */
	private $actionFlags = 0;
	/** @var PermissionManager */
	private $permissionManager;
	/** @var User|null */
	private $actor = null;
	/** @var WorkflowContext */
	private $publicContext = null;
	/** @var WorkflowContextMutable */
	private $privateContext = null;
	/** @var ActivityManager */
	private $activityManager;
	/** @var MultiInstanceStateTracker */
	private $multiInstanceStateTracker;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var bool */
	private $isBotProcess = false;
	/** @var bool */
	private $runningDry = false;

	/**
	 * Create a new engine, use only when starting a new workflow
	 *
	 * @param string $definitionId
	 * @param IDefinitionRepository $definitionRepository
	 * @return self
	 */
	public static function newEmpty( $definitionId, $definitionRepository ) {
		$services = MediaWikiServices::getInstance();

		$activityManagerFactory = $services->get( 'WorkflowsActivityManagerFactory' );
		$activityManager = $activityManagerFactory->newActivityManager();
		$instance = new self (
			$services->getService( 'WorkflowLogicObjectFactory' ),
			$activityManager,
			$services->getPermissionManager(),
			$services->getTitleFactory()
		);
		$workflowNotifier = new WorkflowNotifier(
			$services->getService( 'MWStakeNotificationsNotifier' ),
			$activityManager,
			$instance
		);
		/** @var WorkflowEventRepository $eventRepo */
		$eventRepo = $services->getService( 'WorkflowEventRepository' );
		$eventRepo->addConsumerToDispatcher( $workflowNotifier );
		$definition = $definitionRepository->getDefinition( $definitionId );
		$instance->setDefinition( $definition );
		$storage = WorkflowStorage::newInstance();
		$instance->setStorage( $storage );

		$storage->recordEvent(
			WorkflowInitialized::newFromData(
				$instance->getActor(),
				$definition->getSource(),
				$definition->getContext()
			)
		);
		$instance->setExecutionMode( self::EXECUTION_MODE_EXECUTING );

		return $instance;
	}

	/**
	 * Get engine from storage, use when loading an existing process
	 * Resulting engine will already be at the point that was last saved
	 *
	 * @param WorkflowId $id
	 * @param WorkflowEventRepository $repo
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 * @return self
	 * @throws WorkflowExecutionException
	 */
	public static function newFromInstanceID(
		WorkflowId $id, WorkflowEventRepository $repo,
		DefinitionRepositoryFactory $definitionRepositoryFactory
	) {
		$services = MediaWikiServices::getInstance();
		$activityManagerFactory = $services->get( 'WorkflowsActivityManagerFactory' );
		$activityManager = $activityManagerFactory->newActivityManager();
		$instance = new self(
			$services->getService( 'WorkflowLogicObjectFactory' ),
			$activityManager,
			$services->getPermissionManager(),
			$services->getTitleFactory()
		);
		$workflowNotifier = new WorkflowNotifier(
			$services->getService( 'MWStakeNotificationsNotifier' ),
			$activityManager,
			$instance
		);
		$repo->addConsumerToDispatcher( $workflowNotifier );

		$repo->setWorkflowForReplay( $instance, $definitionRepositoryFactory );
		$instance->setExecutionMode( self::EXECUTION_MODE_REPLAYING );
		$storage = $repo->retrieve( $id );
		if ( !$instance->definition instanceof WorkflowDefinition ) {
			throw new Exception( "Failed to retrieve process from ID {$id->toString()}" );
		}
		$instance->setStorage( $storage );
		$instance->setExecutionMode( self::EXECUTION_MODE_EXECUTING );

		// Check if current process should be continued
		if ( $instance->actionFlags & self::_CONTINUE_EXECUTION_FLAG ) {
			$first = null;
			if ( $instance->current && !empty( $instance->current ) ) {
				$first = $instance->current( array_keys( $instance->current )[0] );
			}
			$instance->continueExecution( $first );
		}
		if ( $instance->actionFlags & self::_PERSIST_FLAG ) {
			$instance->persist( $repo );
		}
		return $instance;
	}

	/**
	 * @param LogicObjectFactory $logicObjFactory
	 * @param ActivityManager $activityManager
	 * @param PermissionManager $pm
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		LogicObjectFactory $logicObjFactory,
		ActivityManager $activityManager,
		PermissionManager $pm,
		TitleFactory $titleFactory
	) {
		$this->logicObjectFactory = $logicObjFactory;
		$this->permissionManager = $pm;
		$this->titleFactory = $titleFactory;
		$this->activityManager = $activityManager;
		$this->state = static::STATE_NOT_STARTED;
	}

	/**
	 * Do not run in the user context
	 */
	public function markAsBotProcess() {
		$this->isBotProcess = true;
		$this->privateContext->setRunningAsBot( true );
	}

	/**
	 * @return bool
	 */
	public function runsAsBotProcess() {
		return $this->isBotProcess;
	}

	/**
	 * Set user that performs actions
	 *
	 * @param User $user
	 */
	public function setActor( User $user ) {
		$this->actor = $user;
		$this->getPrivateContext()->setActor( $user );
	}

	/**
	 * @param array $contextData
	 * @param bool $dry
	 * @throws PermissionsError
	 * @throws WorkflowExecutionException
	 */
	public function start( $contextData = [], $dry = false ) {
		$this->assertActorCan( 'execute' );
		$this->assertWorkflowState( static::STATE_NOT_STARTED );
		$this->assertMembers( __METHOD__ );
		$this->runningDry = $dry;
		$contextData = $this->assertAndFilterDefinitionContextData( $contextData );
		$startDate = new DateTime();
		$this->storage->recordEvent(
			WorkflowStarted::newFromData( $this->getActor(), $startDate, $contextData )
		);
		$this->getPrivateContext()->setStartDate( $startDate );
		$this->getPrivateContext()->setWorkflowId( $this->getStorage()->aggregateRootId() );
		$this->doStart( $contextData );
	}

	/**
	 * @param string $id
	 * @return ITask|null
	 */
	public function getTaskFromId( $id ): ?ITask {
		return $this->definition->getElementById( $id );
	}

	/**
	 * @param ITask $task
	 * @param array|null $data
	 * @return IElement|null
	 * @throws WorkflowExecutionException
	 */
	public function completeTask( $task, $data = [] ) {
		$this->setActor( RequestContext::getMain()->getUser() );
		$this->assertActorCan( 'execute' );
		$this->assertWorkflowState( static::STATE_RUNNING );
		$this->assertMembers( __METHOD__ );

		if ( !isset( $this->current[$task->getId()] ) ) {
			throw new WorkflowExecutionException(
				'Trying to complete task that is not currently reached', $task
			);
		}
		$activity = $this->activityManager->getActivityForTask( $task );
		$status = $this->activityManager->getActivityStatus( $activity );
		if ( $status !== IActivity::STATUS_STARTED ) {
			throw new WorkflowExecutionException(
				'Trying to complete non started activity', $task
			);
		}

		$this->assertActorMatches( $activity );
		$data = $this->getActivityManager()->getValidatedData( $activity, $data );
		$status = $this->activityManager->completeActivity( $activity, $data, $this->getContext() );
		if ( $status instanceof IntermediateExecutionStatus ) {
			// Does not progress the workflow, just update data
			$this->storage->recordEvent(
				TaskIntermediateStateChanged::newFromData(
					$task->getId(), $this->getActor(), $status->getPayload()
				)
			);
			return $this->continueExecution( $task );
		}
		if ( !$this->isAutomatic( $activity ) ) {
			$event = TaskCompletionStarted::newFromData(
				$task->getId(), $this->getActor(), $data
			);
		} else {
			$event = TaskCompletionStarted::newFromData(
				$task->getId(), null, $data
			);
		}
		$this->storage->recordEvent( $event );

		return $this->continueExecution( $task );
	}

	private function probeActivityStatus( IActivity $activity ) {
		$changed = $this->activityManager->probeActivityStatus( $activity, $this->getContext() );
		if ( !$changed ) {
			return $activity->getTask();
		}
		$this->storage->recordEvent(
			ActivityProbeChange::newFromData(
				$activity->getTask()->getId(),
				$this->activityManager->getActivityStatus( $activity ),
				$this->activityManager->getActivityProperties( $activity )
			)
		);
		// Make sure to persist this change on probe
		$this->actionFlags |= static::_PERSIST_FLAG;

		return $this->continueExecution( $activity->getTask() );
	}

	/**
	 * @param string|null $reason
	 * @throws WorkflowExecutionException
	 */
	public function abort( $reason = null ) {
		$this->assertActorCan( 'admin' );
		$this->assertWorkflowState( static::STATE_RUNNING );
		$this->assertMembers( __METHOD__ );
		$endDate = new DateTime();
		$this->getPrivateContext()->setEndDate( $endDate );
		$this->storage->recordEvent(
			WorkflowAborted::newFromData( $this->getActor(), $endDate, $reason )
		);
		$this->stateMessage = $reason;
		$this->state = static::STATE_ABORTED;
	}

	/**
	 * Programmatically abort a workflow on certain conditions
	 * Does not check user permissions
	 *
	 * @param string $type
	 * @param string $message
	 * @param bool|null $noReport if true, abort will not be brought to users attention (silent abort)
	 * @param bool|null $restorable Can the workflow be restored
	 * @throws WorkflowExecutionException
	 */
	public function autoAbort( $type, $message = '', $noReport = false, $restorable = true ) {
		$this->assertWorkflowState( static::STATE_RUNNING );
		$this->assertMembers( __METHOD__ );
		$stateMessage = [
			'type' => $type,
			'message' => $message,
			'noReport' => $noReport,
			'isRestorable' => $restorable,
			'isAuto' => true,
		];
		$endDate = new DateTime();
		$this->getPrivateContext()->setEndDate( $endDate );
		$this->storage->recordEvent(
			WorkflowAutoAborted::newFromData( $this->getActor(), $endDate, $stateMessage )
		);
		$this->stateMessage = $stateMessage;
		$this->state = static::STATE_ABORTED;
	}

	/**
	 * @param string $reason
	 * @throws WorkflowExecutionException
	 */
	public function unAbort( $reason ) {
		$this->assertActorCan( 'admin' );
		$this->assertWorkflowState( static::STATE_ABORTED );
		$this->assertMembers( __METHOD__ );
		$this->storage->recordEvent(
			WorkflowUnAborted::newFromData( $this->getActor(), $reason )
		);
		$this->getPrivateContext()->setEndDate( null );
		$this->state = static::STATE_RUNNING;
		$this->stateMessage = $reason;
		$this->extendDueDateIfExpired();
	}

	/**
	 * Extend due date of an expired activity by one day
	 * @throws WorkflowExecutionException
	 */
	private function extendDueDateIfExpired() {
		$current = $this->current();
		if ( !$current ) {
			return;
		}
		foreach ( $current as $task ) {
			$activity = $this->activityManager->getActivityForTask( $task );
			if ( !$activity ) {
				continue;
			}
			if ( $this->activityManager->getActivityStatus( $activity ) !== IActivity::STATUS_EXPIRED ) {
				continue;
			}
			$tomorrow = ( new DateTime( 'now' ) )->add( new \DateInterval( 'P1D' ) );
			$this->activityManager->setActivityProperties( $activity, [
				'due_date' => $tomorrow->format( 'YmdHis' )
			] );
			$this->storage->recordEvent(
				ActivityProbeChange::newFromData(
					$task->getId(),
					$this->activityManager->getActivityStatus( $activity ),
					$this->activityManager->getActivityProperties( $activity )
				)
			);
		}
	}

	/**
	 * Retrieve current element(s) in the process
	 *
	 * @param string|null $elementId Specific element to retrieve
	 * @return IElement[]|IElement|null if process is not started
	 * @throws Exception
	 */
	public function current( $elementId = null ) {
		$this->assertActorCan( 'view' );
		$this->assertMembers( __METHOD__ );
		if ( $elementId && isset( $this->current[$elementId] ) ) {
			return $this->current[$elementId];
		}

		return $this->current;
	}

	/**
	 * Get current state of the execution
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getCurrentState(): string {
		$this->assertActorCan( 'view' );
		$this->assertMembers( __METHOD__ );
		return $this->state;
	}

	/**
	 * Persist current state of the process to storage
	 *
	 * @param WorkflowEventRepository $repository
	 * @throws Exception
	 */
	public function persist( WorkflowEventRepository $repository ) {
		$this->assertActorCan( 'execute' );
		$this->assertMembers( __METHOD__ );
		$repository->persist( $this->storage );
	}

	/**
	 * @return WorkflowDefinition
	 * @throws Exception
	 */
	public function getDefinition() {
		$this->assertMembers( __METHOD__ );
		return $this->definition;
	}

	/**
	 * @return WorkflowStorage
	 * @throws Exception
	 */
	public function getStorage() {
		$this->assertMembers( __METHOD__ );
		return $this->storage;
	}

	/**
	 * @return IActivity[]
	 */
	public function getCompletedTasks(): array {
		$this->assertActorCan( 'view' );
		return $this->completedTasks;
	}

	public function getActivityForTask( ITask $task ) {
		$this->assertMembers( __METHOD__ );
		$this->assertActorCan( 'view' );
		return $this->activityManager->getActivityForTask( $task );
	}

	/**
	 * @param WorkflowDefinition $definition
	 */
	private function setDefinition( WorkflowDefinition $definition ) {
		$this->definition = $definition;
	}

	/**
	 * @param AggregateRoot $storage
	 */
	private function setStorage( AggregateRoot $storage ) {
		$this->storage = $storage;
		// If storage changes, we need to update Activity manager
		$this->activityManager->setWorkflow( $this );
	}

	/**
	 * @param string $mode
	 */
	private function setExecutionMode( $mode ) {
		$this->executionMode = $mode;
	}

	/**
	 * @param array $contextData
	 * @throws WorkflowExecutionException
	 */
	private function doStart( $contextData = [] ) {
		$start = $this->definition->getElementsOfType( 'startEvent' );
		if ( empty( $start ) ) {
			throw new Exception( 'Cannot start process: No start event found' );
		}
		$this->definition->setContextData( $contextData );
		$this->getPrivateContext()->setDefinitionContext( $this->definition->getContext() );
		if ( !$this->actor instanceof User ) {
			$this->setActor( RequestContext::getMain()->getUser() );
		}
		$this->getPrivateContext()->setInitiator( $this->getActor() );

		$this->state = static::STATE_RUNNING;
		$this->continueExecution( array_shift( $start ) );
	}

	/**
	 * @param IActivity $activity
	 * @param array|null $data
	 * @return IElement|null
	 * @throws WorkflowExecutionException
	 */
	private function afterActivityCompletion( IActivity $activity, $data = null ) {
		$taskID = $activity->getTask()->getId();
		if ( !isset( $this->current[$taskID] ) ) {
			throw new WorkflowExecutionException( "Execution failed: Execution order broken" );
		}
		$this->getPrivateContext()->updateRunningData( $taskID, $data );

		$this->completedTasks[$taskID] = $activity;
		// Once the activity is done, mark it as stale
		// This will ensure that the next time WF reached it,
		// a fresh instance would be created
		$this->activityManager->markStale( $activity );
		if ( $this->multiInstanceStateTracker ) {
			$this->multiInstanceStateTracker->markCompleted( $activity->getTask() );
		}
		unset( $this->current[$taskID] );
		return $this->continueExecution( $this->getNext( $activity->getTask() ) );
	}

	/**
	 * @param ITask $task
	 * @return IElement|null
	 * @throws WorkflowExecutionException
	 */
	private function handleTask( ITask $task ) {
		if ( $this->executionMode === static::EXECUTION_MODE_REPLAYING ) {
			// If we are replaying the events, do not call actual activity objects
			return $task;
		}
		if ( $this->runningDry ) {
			$this->current[$task->getId()] = $task;
			return $task;
		}

		$activity = $this->activityManager->getActivityForTask( $task );

		$status = $this->activityManager->getActivityStatus( $activity );
		if ( $status === IActivity::STATUS_EXPIRED ) {
			// Wait for un-expiration
			return $task;
		}
		if ( $status === IActivity::STATUS_NOT_STARTED ) {
			// If Activity is not started, start it
			if ( !$this->activityManager->startActivity( $activity ) ) {
				throw new WorkflowExecutionException( 'Cannot start task', $task );
			}

			$this->storage->recordEvent(
				TaskStarted::newFromData(
					$task->getId(), $this->getActor(),
					$this->activityManager->getActivityProperties( $activity )
				)
			);

			$this->current[$task->getId()] = $task;
			return $this->continueExecution( $task );
		}

		if ( !isset( $this->current[$task->getId()] ) ) {
			throw new WorkflowExecutionException( 'Execution failed: Execution order broken', $task );
		}
		if ( $status === IActivity::STATUS_STARTED ) {
			if ( !$this->isAutomatic( $activity ) ) {
				// Wait for external completion...
				return $task;
			}

			// ... otherwise, go on with execution
			return $this->completeTask( $task );
		} elseif ( $status === IActivity::STATUS_EXECUTING ) {
			return $this->probeActivityStatus( $activity );
		} elseif (
			!$this->isAutomatic( $activity ) &&
			$task->isLooping() &&
			$status === IActivity::STATUS_LOOP_COMPLETE
		) {
			$this->storage->recordEvent(
				TaskLoopCompleted::newFromData(
					$task->getId(), $this->getActor(),
					$this->activityManager->getActivityProperties( $activity )
				)
			);

			$this->afterTaskLoopComplete( $activity );
		} elseif ( $status === IActivity::STATUS_COMPLETE ) {
			$data = $this->activityManager->getActivityProperties( $activity );
			if ( $this->isAutomatic( $activity ) ) {
				$this->storage->recordEvent(
					TaskCompleted::newFromData( $task->getId(), null, $data )
				);
			} else {
				$this->storage->recordEvent(
					TaskCompleted::newFromData( $task->getId(), $this->getActor(), $data )
				);
			}

			return $this->afterActivityCompletion( $activity, $data );
		} elseif ( $status === IActivity::STATUS_EXPIRED ) {
			$data = $this->activityManager->getActivityProperties( $activity );
			return $this->afterActivityCompletion( $activity, $data );
		}

		return $task;
	}

	/**
	 * Go through the process
	 *
	 * @param IElement|array $from
	 * @return IElement|IElement[]|null
	 * @throws WorkflowExecutionException
	 */
	private function continueExecution( $from ) {
		if ( $this->executionMode === static::EXECUTION_MODE_REPLAYING ) {
			return null;
		}
		if ( $this->state === static::STATE_FINISHED ) {
			return null;
		}
		if ( $this->state === static::STATE_NOT_STARTED ) {
			throw new WorkflowExecutionException( 'Workflow not started, call Workflow::start() first' );
		}

		if ( $this->multiInstanceStateTracker !== null ) {
			if ( $this->multiInstanceStateTracker instanceof ParallelStateTracker ) {
				// We are currently executing tasks in parallel, handle each of them
				$returnedTasks = [];
				foreach ( $this->multiInstanceStateTracker->getPending() as $task ) {
					$task = $this->handleTask( $task );
					if ( $task !== null ) {
						$returnedTasks[] = $task;
					}
				}
				if ( !empty( $returnedTasks ) ) {
					return $returnedTasks;
				}
			} elseif ( $this->multiInstanceStateTracker instanceof SequentialStateTracker ) {
				$nextTask = $this->multiInstanceStateTracker->getNext();
				if ( $nextTask ) {
					$task = $this->handleTask( $nextTask );
					if ( $task !== null ) {
						return $task;
					}
				}
			}
			if ( $this->multiInstanceStateTracker->isCompleted() ) {
				$next = $this->multiInstanceStateTracker->getAfterRef();
				$this->multiInstanceStateTracker = null;
				return $this->continueExecution( $this->definition->getElementById( $next ) );
			}
		} elseif ( is_array( $from ) ) {
			return $this->startParallel( $from );
		} elseif ( $from instanceof ITask && $this->isTaskParallel( $from ) ) {
			return $this->startParallelMultiInstance( $from );
		} elseif ( $from instanceof ITask && $this->isTaskSequential( $from ) ) {
			return $this->startSequential( $from );
		}

		if ( !$from instanceof IElement ) {
			return null;
		}
		if ( $from instanceof ITask ) {
			$task = $this->handleTask( $from );
			if ( $task !== null ) {
				// Wait
				return $task;
			}
		}

		if ( !$from instanceof IElement ) {
			return null;
		}
		$next = $this->getNext( $from );

		if ( $next instanceof EndEvent ) {
			return $this->endProcess( $next );
		}

		return $this->continueExecution( $next );
	}

	/**
	 * End the process
	 *
	 * @param EndEvent $end
	 * @return EndEvent
	 */
	private function endProcess( EndEvent $end ) {
		if ( $this->executionMode === static::EXECUTION_MODE_EXECUTING ) {
			$endDate = new DateTime();
			$this->getPrivateContext()->setEndDate( $endDate );
			$this->storage->recordEvent(
				WorkflowEnded::newFromData( $end->getId(), $endDate )
			);
			return $this->markEnd( $end );
		}

		return null;
	}

	/**
	 * Mark process as ended
	 *
	 * @param EndEvent $end
	 * @return EndEvent
	 */
	private function markEnd( EndEvent $end ) {
		$this->state = static::STATE_FINISHED;
		$this->current = [ $end->getId() => $end ];
		return $end;
	}

	/**
	 * Get a decision on the flow path from a gateway
	 *
	 * @param Gateway $from
	 * @return string
	 * @throws WorkflowExecutionException
	 */
	private function getDecisionFromGateway( $from ): string {
		if ( $this->executionMode === static::EXECUTION_MODE_EXECUTING ) {
			// Let the gateway decide on the path of the flow and follow that path
			$decision = $this->logicObjectFactory->getDecisionForGateway( $from );
			$data = $this->getContext()->flatSerialize();
			$nextRef = $decision->decideFlow( $data, $this->definition );
			// TODO: Is this event even needed?
			$this->storage->recordEvent(
				GatewayDecisionMade::newFromData( $from->getId(), $nextRef )
			);
			return $nextRef;
		}

		return '';
	}

	/**
	 * @param IElement $from
	 * @return IElement|IElement[]|null
	 * @throws WorkflowExecutionException
	 */
	private function getNext( IElement $from ) {
		if ( $from instanceof SequenceFlow ) {
			return $this->definition->getElementById( $from->getTargetRef() );
		} elseif ( $from instanceof Gateway && $from->getElementName() === 'exclusiveGateway' ) {
			return $this->definition->getElementById( $this->getDecisionFromGateway( $from ) );
		} elseif ( $from instanceof Gateway && $from->getElementName() === 'parallelGateway' ) {
			$to = $from->getOutgoing();
			$toTasks = [];
			$outgoingAfterParallel = null;
			foreach ( $to as $flowRef ) {
				/** @var SequenceFlow $flow */
				$flow = $this->definition->getElementById( $flowRef );
				$task = $this->definition->getElementById( $flow->getTargetRef() );
				if ( !$task instanceof ITask ) {
					throw new WorkflowExecutionException( 'Parallel gateway must lead tasks only' );
				}
				$outgoingFlow = $this->definition->getElementById( $task->getOutgoing()[0] );
				if ( $outgoingAfterParallel && $outgoingFlow->getTargetRef() !== $outgoingAfterParallel ) {
					throw new WorkflowExecutionException( 'All parallel tasks must merge paths after completion' );
				}
				$outgoingAfterParallel = $outgoingFlow->getTargetRef();
				$toTasks[] = $task;
			}
			if ( count( $toTasks ) < 2 ) {
				throw new WorkflowExecutionException( 'Parallel gateway must lead to at least 2 tasks' );
			}

			return $toTasks;
		} else {
			$outgoing = $from->getOutgoing();
			if ( $outgoing === null || empty( $outgoing ) ) {
				throw new WorkflowExecutionException( 'Element has no outgoing connections', $from );
			}

			$nextRef = array_shift( $outgoing );
			return $this->definition->getElementById( $nextRef );
		}
	}

	/**
	 * Check if given activity expects some sort of input
	 *
	 * @param IActivity $activity
	 * @return bool
	 */
	private function isAutomatic( IActivity $activity ) {
		return !in_array( $activity->getTask()->getElementName(), [ 'userTask', 'manualTask' ] );
	}

	/**
	 * @param string $fname
	 * @throws Exception
	 */
	private function assertMembers( $fname ) {
		if ( !$this->definition instanceof WorkflowDefinition ) {
			throw new WorkflowExecutionException( "Cannot call $fname before setting the process" );
		}
		if ( !$this->storage instanceof AggregateRoot ) {
			throw new WorkflowExecutionException( "Cannot call $fname before setting the storage" );
		}
	}

	/** Code below is related to replaying events only */

	/**
	 * Handle events coming from the persistence layer
	 * Used to play though the process without execution
	 * Never to be called directly!
	 *
	 * @param Event $event
	 * @param WorkflowId $id
	 * @param array ...$dependencies
	 */
	public function handleEvent( Event $event, WorkflowId $id, ...$dependencies ) {
		if ( $this->executionMode !== static::EXECUTION_MODE_REPLAYING ) {
			throw new WorkflowExecutionException( __METHOD__ . ' called while not in replaying mode!' );
		}
		if ( $event instanceof WorkflowInitialized ) {
			$this->getPrivateContext()->setWorkflowId( $id );
			$defSource = $event->getDefinitionSource();
			/** @var DefinitionRepositoryFactory $defRepoFactory */
			$defRepoFactory = $dependencies[0];
			/** @var IDefinitionRepository $defRepo */
			$defRepo = $defRepoFactory->getRepository( $defSource->getRepositoryKey(), $defSource->getParams() );
			if ( $defRepo ) {
				$definition = $defRepo->getDefinition( $defSource->getName(), $defSource->getVersion() );
				if ( $definition instanceof WorkflowDefinition ) {
					$this->setDefinition( $definition );
				}
			}

			$this->actor = $event->getActor();
		}

		if ( $event instanceof WorkflowStarted ) {
			$this->actor = $event->getActor();
			if ( $event->getStartDate() instanceof DateTime ) {
				$this->getPrivateContext()->setStartDate( $event->getStartDate() );
			}
			$this->doStart( $event->getContextData() );
		}

		if ( $event instanceof ParallelStateTrackerInitialized ) {
			$tasks = [];
			foreach ( $event->getTasks() as $taskId ) {
				$tasks[] = $this->definition->getElementById( $taskId );
			}
			$this->multiInstanceStateTracker = new ParallelStateTracker( $tasks );
			$this->current = [];
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof SequentialStateTrackerInitialized ) {
			$this->multiInstanceStateTracker = new SequentialStateTracker(
				$this->definition->getElementById( $event->getTask() ),
				$this->getContext(),
				$this->activityManager
			);
			$this->current = [];
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof ParallelMultiInstanceStateTrackerInitialized ) {
			$this->multiInstanceStateTracker = new ParallelMultiInstanceStateTracker(
				$this->definition->getElementById( $event->getTask() ),
				$this->getContext(),
				$this->activityManager
			);
			$this->current = [];
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof ActivityEvent ) {
			$activity = $this->establishCurrent( $event->getElementId() );

			$this->activityManager->setActivityProperties( $activity, $event->getData() );
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
			$this->actor = $event->getActor();
		}

		if ( $event instanceof TaskStarted ) {
			$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_STARTED );
			$this->activityManager->trySetDueDate( $activity );
		}

		if ( $event instanceof TaskCompletionStarted ) {
			$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_EXECUTING );
		}

		if ( $event instanceof TaskLoopCompleted ) {
			$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_LOOP_COMPLETE );
		}

		if ( $event instanceof TaskExpired ) {
			$activity = $this->establishCurrent( $event->getElementId() );
			$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_EXPIRED );
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof ActivityProbeChange ) {
			$activity = $this->establishCurrent( $event->getElementId() );
			$this->activityManager->setActivityStatus( $activity, $event->getStatus() );
			$this->activityManager->setActivityProperties( $activity, $event->getProperties() );
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof TaskCompleted ) {
			$this->actionFlags &= ~static::_CONTINUE_EXECUTION_FLAG;
			$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_COMPLETE );
			$this->afterActivityCompletion( $activity, $event->getData() );
		}

		if ( $event instanceof GatewayDecisionMade ) {
			$this->actionFlags |= static::_CONTINUE_EXECUTION_FLAG;
		}

		if ( $event instanceof WorkflowEnded ) {
			/** @var EndEvent $endEvent */
			$endEvent = $this->definition->getElementById( $event->getElementId() );
			$this->getPrivateContext()->setEndDate( $event->getDate() );
			$this->markEnd( $endEvent );
		}

		if ( $event instanceof WorkflowAborted ) {
			$this->state = static::STATE_ABORTED;
			$this->actor = $event->getActor();
			if ( $event->getDate() instanceof DateTime ) {
				$this->getPrivateContext()->setEndDate( $event->getDate() );
			}
			$this->stateMessage = $event->getReason();
		}

		if ( $event instanceof WorkflowUnAborted ) {
			$this->state = static::STATE_RUNNING;
			$this->actor = $event->getActor();
			$this->stateMessage = $event->getReason();
		}
	}

	/**
	 * Set Current task and retrieve its activity
	 *
	 * @param string $taskID
	 * @return IActivity
	 */
	public function establishCurrent( $taskID ) {
		if ( is_array( $this->current ) ) {
			foreach ( $this->current as $element ) {
				if ( $element->getId() === $taskID ) {
					return $this->activityManager->getActivityForTask( $element );
				}
			}
		}
		/** @var ITask $task */
		$task = $this->definition->getElementById( $taskID );
		if ( $this->multiInstanceStateTracker ) {
			if ( !$task ) {
				$task = $this->multiInstanceStateTracker->getVirtualTask( $taskID );
			}
			$this->current[$task->getId()] = $task;
		} else {
			$this->current = [ $task->getId() => $task ];
		}

		return $this->activityManager->getActivityForTask( $task );
	}

	/**
	 * Make sure Context of the process is completely filled
	 *
	 * @param array $contextData
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	private function assertAndFilterDefinitionContextData( array $contextData ) {
		if ( !$this->definition->getContext()->verifyInputData( $contextData ) ) {
			throw new WorkflowExecutionException(
				'Incomplete context data provided: ' .
				implode( ', ', $this->definition->getContext()->getMissingDataKeys() )
			);
		}

		return $this->definition->getContext()->filterRequiredData( $contextData );
	}

	private function assertWorkflowState( ...$allowed ) {
		if ( !in_array( $this->state, $allowed ) ) {
			throw new WorkflowExecutionException(
				'This action can only be called on workflows in following states: ' .
				implode( ',', $allowed )
			);
		}
	}

	/**
	 * Assert actor has permissions
	 * @param string $action
	 * @param ITask|null $task
	 */
	private function assertActorCan( $action, $task = null ) {
		if ( $this->runsAsBotProcess() ) {
			return;
		}
		if ( !$this->actor instanceof User ) {
			return;
		}
		if ( $this->state === self::STATE_RUNNING ) {
			$initiator = $this->getContext()->getInitiator();
			if ( $initiator instanceof User && $initiator->equals( $this->actor ) ) {
				// Workflow initiator can execute "restricted" operations
				return;
			}
		}
		$right = "workflows-$action";
		if ( $task instanceof ITask ) {
			$extElements = $task->getExtensionElements();
			if ( isset( $extElements['permission' ] ) ) {
				$right = $extElements['permission'];
			}
		}
		if ( !$this->permissionManager->userHasRight( $this->getActor(), $right ) ) {
			throw new PermissionsError( $right );
		}
	}

	/**
	 * Get user that performs actions
	 * @return User
	 */
	private function getActor() {
		return $this->actor ?? RequestContext::getMain()->getUser();
	}

	/**
	 * @return WorkflowContext
	 */
	public function getContext(): WorkflowContext {
		if ( !$this->publicContext ) {
			$this->publicContext = new WorkflowContext( $this->getPrivateContext() );
		}
		return $this->publicContext;
	}

	/**
	 * @return WorkflowContextMutable
	 */
	private function getPrivateContext(): WorkflowContextMutable {
		if ( !$this->privateContext ) {
			$this->privateContext = new WorkflowContextMutable( $this->titleFactory );
		}

		return $this->privateContext;
	}

	/**
	 * @param IActivity $activity
	 * @return int
	 */
	public function getActivityStatus( IActivity $activity ): int {
		return $this->activityManager->getActivityStatus( $activity );
	}

	/**
	 * Loop back to the same task
	 *
	 * @param IActivity $activity
	 * @return IElement|null
	 * @throws WorkflowExecutionException
	 */
	private function afterTaskLoopComplete( IActivity $activity ) {
		if ( !isset( $this->current[$activity->getTask()->getId()] ) ) {
			throw new WorkflowExecutionException( "Execution failed: Execution order broken" );
		}

		$data = $this->activityManager->getActivityProperties( $activity );
		$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_STARTED );
		$this->getPrivateContext()->updateRunningData( $activity->getTask()->getId(), $data );

		// Continue execution by executing the same step again
		return $this->continueExecution( $activity->getTask() );
	}

	/**
	 * @return ActivityManager
	 */
	public function getActivityManager(): ActivityManager {
		return $this->activityManager;
	}

	/**
	 * @param ITask $task
	 * @return bool
	 */
	private function isTaskParallel( ITask $task ): bool {
		return $this->isTaskMultiInstance( $task ) && !$this->isTaskSequential( $task );
	}

	/**
	 * @param ITask $task
	 * @return bool
	 */
	private function isTaskSequential( ITask $task ): bool {
		return $this->isTaskMultiInstance( $task ) &&
			$task->getMultiInstanceCharacteristics()['isSequential'];
	}

	private function isTaskMultiInstance( ITask $task ) {
		return $task->getMultiInstanceCharacteristics() !== null;
	}

	/**
	 * @param ITask[] $from
	 * @return EndEvent|IElement|IElement[]|ITask|null
	 * @throws WorkflowExecutionException
	 */
	private function startParallel( array $from ) {
		// We have reached parallel tasks, multiple target tasks, create a parallel tracker
		$this->multiInstanceStateTracker = new ParallelStateTracker( $from );
		$this->storage->recordEvent(
			ParallelStateTrackerInitialized::newFromData( array_map( static function ( ITask $task ) {
				return $task->getId();
			}, $from ) )
		);
		$this->current = [];
		return $this->continueExecution( $from );
	}

	/**
	 * @param ITask[] $task
	 * @return EndEvent|IElement|IElement[]|ITask|null
	 * @throws WorkflowExecutionException
	 */
	private function startParallelMultiInstance( ITask $task ) {
		// We have reached parallel tasks, multiple target tasks, create a parallel tracker
		$this->multiInstanceStateTracker = new ParallelMultiInstanceStateTracker(
			$task, $this->getContext(), $this->getActivityManager()
		);
		$this->storage->recordEvent(
			ParallelMultiInstanceStateTrackerInitialized::newFromData( $task->getId() )
		);
		$this->current = [];
		return $this->continueExecution( $task );
	}

	private function startSequential( ITask $task ) {
		$this->multiInstanceStateTracker = new SequentialStateTracker(
			$task, $this->getContext(), $this->activityManager
		);
		$this->storage->recordEvent(
			SequentialStateTrackerInitialized::newFromData( $task->getId() )
		);
		$this->current = [];
		return $this->continueExecution( $task );
	}

	/**
	 * Make sure user that tries to complete activity is targeted by it
	 *
	 * @param IActivity $activity
	 * @throws WorkflowExecutionException
	 */
	private function assertActorMatches( IActivity $activity ) {
		if ( !$activity instanceof UserInteractiveActivity ) {
			return;
		}

		$users = $this->activityManager->getTargetUsersForActivity( $activity );
		if ( $users === null ) {
			return;
		}
		if ( !in_array( $this->getActor()->getName(), $users ) ) {
			throw new WorkflowExecutionException(
				'Actor mismatch: User that tried to complete this activity is not targeted by it'
			);
		}
	}

	public function expireActivity( UserInteractiveActivity $activity ) {
		$status = $this->activityManager->getActivityStatus( $activity );
		if ( $status !== IActivity::STATUS_EXECUTING && $status !== IActivity::STATUS_STARTED ) {
			// Sanity
			return;
		}
		$this->storage->recordEvent(
			TaskExpired::newFromData( $activity->getTask()->getId() )
		);
		$this->activityManager->setActivityStatus( $activity, IActivity::STATUS_EXPIRED );
		$this->autoAbort( 'duedate', 'Activity expired' );
	}

	/**
	 * @return string|array
	 */
	public function getStateMessage() {
		return $this->stateMessage;
	}
}

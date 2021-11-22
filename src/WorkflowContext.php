<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Util\DataFlattener;
use Title;
use TitleFactory;
use User;

class WorkflowContext {
	/** @var DefinitionContext */
	private $definitionContext;
	/** @var null */
	private $runningActor = null;
	/** @var array */
	private $runningData = [];
	/** @var DateTime|null */
	private $startDate = null;
	/** @var User|null */
	private $initiator;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var WorkflowId */
	private $workflowId;

	/**
	 * @param DefinitionContext $definitionContext
	 * @param TitleFactory $titleFactory
	 * @param WorkflowId $workflowId
	 * @param User|null $initiator
	 */
	public function __construct(
		DefinitionContext $definitionContext, TitleFactory $titleFactory,
		WorkflowId $workflowId, ?User $initiator = null
	) {
		$this->definitionContext = $definitionContext;
		$this->titleFactory = $titleFactory;
		$this->workflowId = $workflowId;
		$this->initiator = $initiator;
	}

	/**
	 *
	 * @return DefinitionContext
	 */
	public function getDefinitionContext(): DefinitionContext {
		return $this->definitionContext;
	}

	/**
	 * Set current actor
	 * @param User|null $user
	 */
	public function setActor( ?User $user ) {
		$this->runningActor = $user;
	}

	/**
	 * @return User|null
	 */
	public function getCurrentActor(): ?User {
		return $this->runningActor;
	}

	/**
	 * @param DateTime $startDate
	 */
	public function setStartDate( DateTime $startDate ) {
		$this->startDate = $startDate;
	}

	/**
	 * @return DateTime|null
	 */
	public function getStartDate(): ?DateTime {
		return $this->startDate;
	}

	/**
	 * Clear out running data
	 */
	public function resetRunningData() {
		$this->runningData = [];
	}

	/**
	 * Try to retrieve Title tied to workflow, if any
	 *
	 * @return Title|null
	 */
	public function getContextPage(): ?Title {
		$pageId = $this->getDefinitionContext()->getItem( 'pageId' );
		if ( !$pageId ) {
			return null;
		}

		return $this->titleFactory->newFromID( $pageId );
	}

	/**
	 * Update data set though workflow execution
	 *
	 * @param string $activityId
	 * @param array $data
	 */
	public function updateRunningData( $activityId, array $data ) {
		if ( isset( $this->runningData[$activityId] ) ) {
			$this->runningData[$activityId] = array_merge(
				$this->runningData[$activityId],
				$data
			);
			return;
		}
		$this->runningData[$activityId] = $data;
	}

	/**
	 * Get piece of running data
	 *
	 * @param string|null $activityId
	 * @param string|null $key Data key
	 * @return mixed|null if no data found
	 */
	public function getRunningData( $activityId = null, $key = null ) {
		if ( !$activityId ) {
			return $this->runningData;
		}
		if ( !isset( $this->runningData[$activityId] ) ) {
			return null;
		}
		if ( !$key ) {
			return $this->runningData[$activityId];
		}
		if ( !isset( $this->runningData[$activityId][$key] ) ) {
			return null;
		}

		return $this->runningData[$activityId][$key];
	}

	/**
	 * Return data in a simple hashmap
	 * Returns all output data of the activities as well any required "workflow-global" data
	 * data key. E.g. `[ 'activity1.field1' => 42, 'activity2.field1' => 23 ]`
	 *
	 * @return array
	 */
	public function flatSerialize(): array {
		$dataFlattener = new DataFlattener();

		$additionalData = [
			'initiator' => $this->initiator->getName(),
			'start_date' => $this->startDate->format( 'YmdHis' )
		];
		return $dataFlattener->flatten( array_merge( $this->runningData, $additionalData ) );
	}

	/**
	 * User who started the WF, or null if started by script
	 *
	 * @return User|null
	 */
	public function getInitiator(): ?User {
		return $this->initiator;
	}

	/**
	 * @return WorkflowId
	 */
	public function getWorkflowId(): WorkflowId {
		return $this->workflowId;
	}
}

<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use Title;
use User;

/**
 * @package MediaWiki\Extension\Workflows
 * @public
 */
class WorkflowContext {
	/** @var WorkflowContextMutable */
	private $mutable;

	/**
	 * @param WorkflowContextMutable $contextMutable
	 */
	public function __construct( WorkflowContextMutable $contextMutable ) {
		$this->mutable = $contextMutable;
	}

	/**
	 *
	 * @return DefinitionContext
	 */
	public function getDefinitionContext(): DefinitionContext {
		return $this->mutable->getDefinitionContext();
	}

	/**
	 * @return User|null
	 */
	public function getCurrentActor(): ?User {
		return $this->mutable->getCurrentActor();
	}

	/**
	 * @return DateTime|null if not started
	 */
	public function getStartDate(): ?DateTime {
		return $this->mutable->getStartDate();
	}

	/**
	 * @return DateTime|null if not finished
	 */
	public function getEndDate(): ?DateTime {
		return $this->mutable->getEndDate();
	}

	/**
	 * Try to retrieve Title tied to workflow, if any
	 *
	 * @return Title|null
	 */
	public function getContextPage(): ?Title {
		return $this->mutable->getContextPage();
	}

	/**
	 * Get piece of running data
	 *
	 * @param string|null $activityId
	 * @param string|null $key Data key
	 * @return mixed|null if no data found
	 */
	public function getRunningData( $activityId = null, $key = null ) {
		return $this->mutable->getRunningData( $activityId, $key );
	}

	/**
	 * Return data in a simple hashmap
	 * Returns all output data of the activities as well any required "workflow-global" data
	 * data key. E.g. `[ 'activity1.field1' => 42, 'activity2.field1' => 23 ]`
	 *
	 * @return array
	 */
	public function flatSerialize(): array {
		return $this->mutable->flatSerialize();
	}

	/**
	 * User who started the WF, or null if started by script
	 *
	 * @return User|null
	 */
	public function getInitiator(): ?User {
		return $this->mutable->getInitiator();
	}

	/**
	 * @return WorkflowId
	 */
	public function getWorkflowId(): WorkflowId {
		return $this->mutable->getWorkflowId();
	}

	/**
	 * @return bool
	 */
	public function isRunningAsBot(): bool {
		return $this->mutable->isRunningAsBot();
	}
}

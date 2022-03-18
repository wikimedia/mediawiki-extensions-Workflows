<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowTriggerException;
use MediaWiki\Extension\Workflows\ITrigger;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class GenericTrigger implements ITrigger, LoggerAwareInterface {
	/** @var WorkflowFactory */
	protected $workflowFactory;
	/** @var string */
	protected $type = '';

	/** @var string */
	protected $name;
	/** @var string */
	protected $definition;
	/** @var string */
	protected $repo;
	/** @var array */
	protected $contextData;
	/** @var array */
	protected $initData;
	/** @var array */
	protected $rules;
	/** @var bool */
	private $active;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param string $name
	 * @param array $data
	 * @return static
	 */
	public static function factory( $name, $data ) {
		$instance = new static(
			$name,
			$data['type'],
			$data['definition'],
			$data['repository'],
			$data['contextData'] ?? [],
			$data['initData'] ?? [],
			$data['rules'] ?? [],
			$data['active'] ?? true
		);

		return $instance;
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param string $definition
	 * @param string $repo
	 * @param array $contextData
	 * @param array $initData
	 * @param array $rules
	 * @param bool $active
	 */
	public function __construct(
		$name, $type, $definition, $repo, $contextData, $initData, $rules, $active = true
	) {
		$this->name = $name;
		$this->type = $type;
		$this->definition = $definition;
		$this->repo = $repo;
		$this->contextData = $contextData;
		$this->initData = $initData;
		$this->rules = $rules;
		$this->active = $active;
	}

	/**
	 * @param WorkflowFactory $workflowFactory
	 */
	public function setWorkflowFactory( WorkflowFactory $workflowFactory ) {
		$this->workflowFactory = $workflowFactory;
	}

	/**
	 * @return bool
	 * @throws WorkflowTriggerException
	 */
	public function trigger(): bool {
		if ( !$this->workflowFactory ) {
			throw new WorkflowTriggerException( 'Workflow factory not set', $this );
		}
		try {
			return $this->startWorkflow(
				$this->repo, $this->definition, $this->getContextData(), $this->initData
			);
		} catch ( WorkflowExecutionException $ex ) {
			$this->logger->error( $ex->getMessage(), [
				'repository' => $this->repo,
				'definition' => $this->definition,
				'contextData' => $this->getContextData(),
				'initData' => $this->initData,
			] );
			return false;
		}
	}

	/**
	 * @return array
	 */
	protected function getContextData() {
		return $this->contextData;
	}

	/**
	 * @param string $repo
	 * @param string $definition
	 * @param array $contextData
	 * @param array|null $initData
	 * @return string
	 * @throws \MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException
	 */
	protected function startWorkflow( $repo, $definition, $contextData = [], $initData = null ): string {
		$workflow = $this->workflowFactory->newEmpty( $definition, $repo );
		$workflow->markAsBotProcess();
		$workflow->start( $contextData );
		$initializer = $this->getInitializer( $workflow );
		if ( $initData && $initializer ) {
			$workflow->completeTask( $initializer->getTask(), $initData );
		}

		$this->workflowFactory->persist( $workflow );

		return $workflow->getStorage()->aggregateRootId()->toString();
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getAttributes(): array {
		return [
			'contextData' => $this->contextData,
			'initData' => $this->initData,

		];
	}

	/**
	 * @return array
	 */
	public function getRuleset(): array {
		return $this->rules;
	}

	/**
	 * @param Workflow $engine
	 * @return \MediaWiki\Extension\Workflows\IActivity|UserInteractiveActivity|null
	 * @throws \Exception
	 */
	protected function getInitializer( Workflow $engine ) {
		$currentTasks = $engine->current();
		foreach ( $currentTasks as $id => $item ) {
			if ( $item instanceof ITask ) {
				$activity = $engine->getActivityForTask( $item );
				if ( $activity instanceof UserInteractiveActivity && $activity->isInitializer() ) {
					return $activity;
				}
			}
		}

		return null;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->active;
	}

	/**
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		return true;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'type' => $this->getType(),
			'active' => $this->active,
			'definition' => $this->definition,
			'repository' => $this->repo,
			'initData' => $this->initData,
			'contextData' => $this->contextData,
			'rules' => $this->rules
		];
	}
}

<?php

namespace MediaWiki\Extension\Workflows\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator;
use BlueSpice\Discovery\IAttentionIndicator;
use MediaWiki\Config\Config;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Throwable;

class Workflows extends AttentionIndicator {

	/**
	 * @var WorkflowStateStore
	 */
	protected $stateStore;

	/**
	 * @var UserFactory
	 */
	protected $userFactory;

	/**
	 * @var WorkflowFactory
	 */
	protected $workflowFactory;

	/**
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param WorkflowStateStore $stateStore
	 * @param UserFactory $userFactory
	 * @param WorkflowFactory $workflowFactory
	 */
	public function __construct(
		string $key, Config $config, User $user,
		WorkflowStateStore $stateStore, UserFactory $userFactory, WorkflowFactory $workflowFactory
	) {
		$this->stateStore = $stateStore;
		$this->userFactory = $userFactory;
		$this->workflowFactory = $workflowFactory;
		parent::__construct( $key, $config, $user );
	}

	/**
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param MediaWikiServices $services
	 * @param WorkflowStateStore|null $stateStore
	 * @param UserFactory|null $userFactory
	 * @param WorkflowFactory|null $workflowFactory
	 * @return IAttentionIndicator
	 */
	public static function factory(
		string $key, Config $config, User $user, MediaWikiServices $services,
		?WorkflowStateStore $stateStore = null, ?UserFactory $userFactory = null,
		?WorkflowFactory $workflowFactory = null
	) {
		if ( !$stateStore ) {
			$stateStore = $services->getService( 'WorkflowsStateStore' );
		}
		if ( !$userFactory ) {
			$userFactory = $services->getUserFactory();
		}
		if ( !$workflowFactory ) {
			$workflowFactory = $services->getService( 'WorkflowFactory' );
		}

		return new static(
			$key,
			$config,
			$user,
			$stateStore,
			$userFactory,
			$workflowFactory
		);
	}

	/**
	 * @return int
	 */
	protected function doIndicationCount(): int {
		return $this->getUserActivityCount();
	}

	/**
	 * @return int
	 */
	private function getUserActivityCount(): int {
		$ids = array_merge(
			$this->stateStore->active()->onEvent( TaskStarted::class )->query(),
			$this->stateStore->active()->onEvent( TaskLoopCompleted::class )->query(),
			$this->stateStore->active()->onEvent( TaskIntermediateStateChanged::class )->query()
		);

		$assignedActivitiesCount = 0;
		$models = $this->stateStore->modelsFromIds( $ids );
		foreach ( $models as $model ) {
			$validTask = false;
			foreach ( $model->getAssignees() as $assigneeName ) {
				$assignedUser = $this->userFactory->newFromName( $assigneeName );
				if ( $assignedUser === null ) {
					continue;
				}
				if ( $assignedUser->getId() === $this->user->getId() ) {
					try {
						$workflow = $this->workflowFactory->getWorkflow( $model->getWorkflowId() );
						$current = $workflow->current();
						foreach ( $current as $item ) {
							if ( !$item instanceof ITask || $item->getElementName() !== 'userTask' ) {
								continue;
							}
							$activity = $workflow->getActivityForTask( $item );
							if ( !$activity instanceof UserInteractiveActivity ) {
								continue;
							}
							$validTask = true;
						}
					} catch ( Throwable ) {
						// NOOP - skip
					}
				}
			}

			if ( $validTask ) {
				$assignedActivitiesCount++;
			}
		}

		return $assignedActivitiesCount;
	}

}

<?php

namespace MediaWiki\Extension\Workflows\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator;
use BlueSpice\Discovery\IAttentionIndicator;
use Config;
use EventSauce\EventSourcing\AggregateRootId;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\MediaWikiServices;
use User;

class Workflows extends AttentionIndicator {

	/**
	 * @var WorkflowStateStore
	 */
	protected $stateStore;

	/**
	 * @var WorkflowFactory
	 */
	protected $workflowFactory;

	/**
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param WorkflowStateStore $stateStore
	 * @param WorkflowFactory $workflowFactory
	 */
	public function __construct( string $key, Config $config, User $user,
		WorkflowStateStore $stateStore, WorkflowFactory $workflowFactory ) {
		$this->stateStore = $stateStore;
		$this->workflowFactory = $workflowFactory;
		parent::__construct( $key, $config, $user );
	}

	/**
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param MediaWikiServices $services
	 * @param WorkflowStateStore|null $stateStore
	 * @param WorkflowFactory|null $workflowFactory
	 * @return IAttentionIndicator
	 */
	public static function factory( string $key, Config $config, User $user,
		MediaWikiServices $services, WorkflowStateStore $stateStore = null,
		WorkflowFactory $workflowFactory = null ) {
		if ( !$stateStore ) {
			$stateStore = $services->getService( 'WorkflowsStateStore' );
		}
		if ( !$workflowFactory ) {
			$workflowFactory = $services->getService( 'WorkflowFactory' );
		}

		return new static(
			$key,
			$config,
			$user,
			$stateStore,
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

		$activities = 0;
		/** @var AggregateRootId $id */
		foreach ( $ids as $id ) {
			try {
				$workflow = $this->workflowFactory->getWorkflow( $id );
				$current = $workflow->current();

				foreach ( $current as $item ) {
					if ( !$item instanceof ITask || $item->getElementName() !== 'userTask' ) {
						continue;
					}
					$activity = $workflow->getActivityForTask( $item );
					if ( !$activity instanceof UserInteractiveActivity ) {
						continue;
					}
					$target = $workflow->getActivityManager()->getTargetUsersForActivity(
						$activity
					);
					if ( $target === null || in_array( $this->user->getName(), $target ) ) {
						$activities++;
					}

				}
			} catch ( WorkflowExecutionException $ex ) {
				// TODO: Log
				continue;
			}
		}

		return $activities;
	}

}

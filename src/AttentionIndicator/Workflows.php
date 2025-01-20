<?php

namespace MediaWiki\Extension\Workflows\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator;
use BlueSpice\Discovery\IAttentionIndicator;
use Config;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Extension\Workflows\Storage\Event\TaskIntermediateStateChanged;
use MediaWiki\Extension\Workflows\Storage\Event\TaskLoopCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;

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
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param WorkflowStateStore $stateStore
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		string $key, Config $config, User $user,
		WorkflowStateStore $stateStore, UserFactory $userFactory
	) {
		$this->stateStore = $stateStore;
		$this->userFactory = $userFactory;
		parent::__construct( $key, $config, $user );
	}

	/**
	 * @param string $key
	 * @param Config $config
	 * @param User $user
	 * @param MediaWikiServices $services
	 * @param WorkflowStateStore|null $stateStore
	 * @param UserFactory|null $userFactory
	 *
	 * @return IAttentionIndicator
	 */
	public static function factory(
		string $key, Config $config, User $user, MediaWikiServices $services,
		?WorkflowStateStore $stateStore = null, ?UserFactory $userFactory = null
	) {
		if ( !$stateStore ) {
			$stateStore = $services->getService( 'WorkflowsStateStore' );
		}
		if ( !$userFactory ) {
			$userFactory = $services->getUserFactory();
		}

		return new static(
			$key,
			$config,
			$user,
			$stateStore,
			$userFactory
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
			foreach ( $model->getAssignees() as $assigneeName ) {
				$assignedUser = $this->userFactory->newFromName( $assigneeName );
				if ( $assignedUser === null ) {
					continue;
				}
				if ( $assignedUser->getId() === $this->user->getId() ) {
					$assignedActivitiesCount++;
				}
			}
		}

		return $assignedActivitiesCount;
	}

}

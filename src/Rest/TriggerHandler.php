<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Rest\Handler;
use MediaWiki\User\UserIdentity;

abstract class TriggerHandler extends Handler {
	/** @var TriggerRepo */
	private $triggerRepo;
	/** @var PermissionManager|null */
	private $permissionManager;

	/**
	 * @param TriggerRepo $triggerRepo
	 * @param PermissionManager|null $permissionManager
	 */
	public function __construct(
		TriggerRepo $triggerRepo, ?PermissionManager $permissionManager = null
	) {
		$this->triggerRepo = $triggerRepo;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param UserIdentity $user
	 * @return void
	 */
	protected function assertUserIsAdmin( $user ) {
		if ( !$this->permissionManager ) {
			return false;
		}
		if ( !$this->permissionManager->userHasRight( $user, 'workflows-admin' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @return TriggerRepo
	 */
	protected function getTriggerRepo() {
		return $this->triggerRepo;
	}
}

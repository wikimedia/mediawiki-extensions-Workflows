<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;

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

	protected function assertUserIsAdmin() {
		if ( !$this->permissionManager ) {
			return;
		}
		$user = \RequestContext::getMain()->getUser();
		if ( !$this->permissionManager->userHasRight( $user, 'workflows-admin' ) ) {
			throw new HttpException( 'permissiondenied', 401 );
		}
	}

	/**
	 * @return TriggerRepo
	 */
	protected function getTriggerRepo() {
		return $this->triggerRepo;
	}
}

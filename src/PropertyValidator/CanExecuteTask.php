<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserFactory;
use Message;

class CanExecuteTask extends ValidUser {
	/** @var PermissionManager */
	private $permissionManager;

	/**
	 * @param UserFactory $userFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( UserFactory $userFactory, PermissionManager $permissionManager ) {
		parent::__construct( $userFactory );
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( $value, IActivity $activity ) {
		if ( !parent::validate( $value, $activity ) ) {
			return false;
		}
		$user = $this->userFactory->newFromName( $value );

		return $this->permissionManager->userHasRight( $user, 'workflows-execute' );
	}

	/**
	 * @inheritDoc
	 */
	public function getError( $value ): Message {
		return Message::newFromKey(
			'workflows-property-validator-valid-task-executor-error'
		)->params(
			$value
		);
	}
}

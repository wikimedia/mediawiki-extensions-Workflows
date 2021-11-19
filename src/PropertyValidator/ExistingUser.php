<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\User\UserFactory;
use Message;

class ExistingUser implements IPropertyValidator {
	/** @var UserFactory */
	protected $userFactory;

	/**
	 * @param UserFactory $userFactory
	 */
	public function __construct( UserFactory $userFactory ) {
		$this->userFactory = $userFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( $value, IActivity $activity ) {
		$user = $this->userFactory->newFromName( $value );
		return $user instanceof \User && $user->isRegistered();
	}

	/**
	 * @inheritDoc
	 */
	public function getError( $value ): Message {
		return Message::newFromKey(
			'workflows-property-validator-valid-user-error'
		)->params( $value );
	}
}

<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\User\UserFactory;
use Message;

class EmailRecipient implements IPropertyValidator {
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
		if ( !$value ) {
			return true;
		}
		if ( strpos( $value, '@' ) !== false ) {
			// Email
			return true;
		}
		$user = $this->userFactory->newFromName( $value );
		if ( !$user instanceof \User && $user->isRegistered() ) {
			return false;
		}
		if ( !$user->getEmail() ) {
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getError( $value ): Message {
		return Message::newFromKey(
			'workflows-property-validator-email-recipient-error'
		)->params( $value );
	}
}

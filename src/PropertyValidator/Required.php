<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use Message;

class Required implements IPropertyValidator {
	/**
	 * @inheritDoc
	 */
	public function validate( $value, IActivity $activity ) {
		if ( is_string( $value ) || is_array( $value ) ) {
			return !empty( $value );
		}
		return (string)$value !== '';
	}

	/**
	 * @inheritDoc
	 */
	public function getError( $value ): Message {
		return Message::newFromKey(
			'workflows-property-validator-required-error'
		);
	}
}

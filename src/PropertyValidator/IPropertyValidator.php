<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use Message;

interface IPropertyValidator {
	/**
	 * @param mixed $value
	 * @param IActivity $activity
	 * @return bool
	 */
	public function validate( $value, IActivity $activity );

	/**
	 * @param string $value Value for which validation failed
	 * @return Message
	 */
	public function getError( $value ): Message;
}

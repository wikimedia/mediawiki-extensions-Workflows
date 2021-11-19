<?php

namespace MediaWiki\Extension\Workflows\PropertyValidator;

use MediaWiki\Extension\Workflows\IActivity;
use Message;
use TitleFactory;

class ExistingTitle implements IPropertyValidator {
	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( $value, IActivity $activity ) {
		$title = $this->titleFactory->newFromText( $value );
		return $title instanceof \Title && $title->exists();
	}

	/**
	 * @inheritDoc
	 */
	public function getError( $value ): Message {
		return Message::newFromKey(
			'workflows-property-validator-valid-title-error'
		)->params( $value );
	}
}

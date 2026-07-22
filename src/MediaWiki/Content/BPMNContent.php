<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use MediaWiki\Content\TextContent;

class BPMNContent extends TextContent {

	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'BPMN' );
	}

	/**
	 * @inheritDoc
	 */
	public function isValid() {
		if ( empty( $this->getText() ) ) {
			return true;
		}

		if ( simplexml_load_string( $this->getText() ) === false ) {
			return false;
		}

		return true;
	}
}

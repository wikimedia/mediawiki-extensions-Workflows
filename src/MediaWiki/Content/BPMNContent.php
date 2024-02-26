<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use TextContent;

class BPMNContent extends TextContent {
	public function __construct( $text ) {
		parent::__construct( $text, 'BPMN' );
	}

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

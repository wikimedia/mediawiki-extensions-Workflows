<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use TextContent;

class BPMNContent extends TextContent {
	public function __construct( $text ) {
		parent::__construct( $text, 'BPMN' );
	}
}

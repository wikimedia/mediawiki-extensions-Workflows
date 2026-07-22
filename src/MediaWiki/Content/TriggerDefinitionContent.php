<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use MediaWiki\Content\JsonContent;

class TriggerDefinitionContent extends JsonContent {

	/**
	 * @param string $text
	 * @param string $modelId
	 */
	public function __construct( $text, $modelId = 'workflow-triggers' ) {
		parent::__construct( $text, $modelId );
	}
}

<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use JsonContent;

class TriggerDefinitionContent extends JsonContent {
	public function __construct( $text, $modelId = 'workflow-triggers' ) {
		parent::__construct( $text, $modelId );
	}
}

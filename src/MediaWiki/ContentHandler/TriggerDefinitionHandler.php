<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use JsonContentHandler;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;

class TriggerDefinitionHandler extends JsonContentHandler {
	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'workflow-triggers' ) {
		parent::__construct( $modelId );
	}

	/**
	 * @inheritDoc
	 */
	protected function getContentClass() {
		return TriggerDefinitionContent::class;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsSections() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsCategories() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsRedirects() {
		return false;
	}
}

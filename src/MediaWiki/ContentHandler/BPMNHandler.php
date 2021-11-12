<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use MediaWiki\Extension\Workflows\MediaWiki\Content\BPMNContent;
use TextContentHandler;

class BPMNHandler extends TextContentHandler {
	public function __construct( $modelId = 'BPMN' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );
	}

	protected function getContentClass() {
		return BPMNContent::class;
	}

	public function supportsSections() {
		return false;
	}

	public function supportsCategories() {
		return true;
	}

	public function supportsRedirects() {
		return false;
	}
}

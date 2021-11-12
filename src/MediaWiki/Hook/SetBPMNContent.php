<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;

class SetBPMNContent implements ContentHandlerDefaultModelForHook {

	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( preg_match( '/\.bpmn$/', $title->getText() ) && !$title->isTalkPage() ) {
			$model = 'BPMN';
			return false;
		}

		return true;
	}
}

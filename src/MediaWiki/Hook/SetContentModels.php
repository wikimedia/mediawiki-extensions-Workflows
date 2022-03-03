<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;

class SetContentModels implements ContentHandlerDefaultModelForHook {

	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( preg_match( '/\.bpmn$/', $title->getText() ) && !$title->isTalkPage() ) {
			$model = 'BPMN';
			return false;
		}
		// Hardcoded pagename
		if ( $title->getPrefixedDBkey() === 'MediaWiki:WorkflowTriggers' ) {
			$model = 'workflow-triggers';
			return false;
		}

		return true;
	}
}

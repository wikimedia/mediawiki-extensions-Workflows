<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;

class SetContentModels implements ContentHandlerDefaultModelForHook {
	/**
	 * @var TriggerRepo
	 */
	private $triggerRepo;

	/**
	 * @param TriggerRepo $triggerRepo
	 */
	public function __construct( TriggerRepo $triggerRepo ) {
		$this->triggerRepo = $triggerRepo;
	}

	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( preg_match( '/\.bpmn$/', $title->getText() ) && !$title->isTalkPage() ) {
			$model = 'BPMN';
			return false;
		}
		// Hardcoded pagename
		if ( $title->equals( $this->triggerRepo->getTitle() ) ) {
			$model = 'workflow-triggers';
			return false;
		}

		return true;
	}
}

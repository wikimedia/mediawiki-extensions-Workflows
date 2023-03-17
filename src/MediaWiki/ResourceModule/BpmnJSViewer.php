<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ResourceModule;

class BpmnJSViewer extends BpmnJS {

	/**
	 * @return string
	 */
	protected function getForDebug(): string {
		return 'ui/editor/bpmn-js/bpmn-viewer.development.js';
	}

	/**
	 * @return string
	 */
	protected function getMainScript(): string {
		return 'ui/editor/bpmn-js/bpmn-viewer.production.min.js';
	}
}

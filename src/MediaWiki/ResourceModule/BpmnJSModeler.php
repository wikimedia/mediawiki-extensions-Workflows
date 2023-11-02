<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ResourceModule;

class BpmnJSModeler extends BpmnJS {

	/**
	 * @return string
	 */
	protected function getForDebug(): string {
		return 'ui/editor/bpmn-js/bpmn-modeler.development.js';
	}

	/**
	 * @return string
	 */
	protected function getMainScript(): string {
		return 'ui/editor/bpmn-js/bpmn-modeler.production.min.js';
	}
}

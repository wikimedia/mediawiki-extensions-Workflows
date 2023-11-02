<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ResourceModule;

use MediaWiki\ResourceLoader as RL;

abstract class BpmnJS extends RL\FileModule {

	/**
	 * @inheritDoc
	 * @param RL\Context $context
	 * @return string|array
	 */
	public function getScript( RL\Context $context ) {
		if ( $context->getDebug() ) {
			array_unshift( $this->scripts, $this->getForDebug() );
		} else {
			array_unshift( $this->scripts, $this->getMainScript() );
		}

		return parent::getScript( $context );
	}

	/**
	 * Get a list of file paths for all styles in this module, in order of proper inclusion.
	 *
	 * @param RL\Context $context
	 * @return array List of file paths
	 */
	public function getStyleFiles( RL\Context $context ) {
		$styleFiles = parent::getStyleFiles( $context );
		if ( !isset( $styleFiles['all'] ) ) {
			$styleFiles['all'] = [];
		}
		$styleFiles['all'] = array_merge( [
			'ui/editor/bpmn-js/assets/bpmn-js.css',
			'ui/editor/bpmn-js/assets/diagram-js.css',
			'ui/editor/bpmn-js/assets/bpmn-font/css/bpmn.css'
		], $styleFiles['all'] );

		return $styleFiles;
	}

	/**
	 * @return string
	 */
	abstract protected function getForDebug(): string;

	/**
	 * @return string
	 */
	abstract protected function getMainScript(): string;

}

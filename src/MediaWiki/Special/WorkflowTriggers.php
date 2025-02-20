<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Special;

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use OOUI\ProgressBarWidget;

class WorkflowTriggers extends SpecialPage {

	public function __construct() {
		parent::__construct( 'WorkflowTriggers', 'read' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$out = $this->getOutput();

		$out->addModules( [ 'ext.workflows.triggers' ] );
		$this->getContext()->getOutput()->enableOOUI();
		$out->addHTML(
			Html::rawElement( 'div', [
				'id' => 'workflows-triggers-editor-cnt'
			], new ProgressBarWidget( [ 'progress' => false ] )
			)
		);
	}
}

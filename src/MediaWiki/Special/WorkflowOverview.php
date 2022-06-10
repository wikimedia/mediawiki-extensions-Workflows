<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Special;

use Html;
use SpecialPage;

class WorkflowOverview extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WorkflowsOverview', 'workflows-view' );
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->getOutput()->enableOOUI();
		$this->getOutput()->addModules( 'ext.workflows.special.overview' );
		$this->getOutput()->addHTML( Html::element( 'div', [
			'id' => 'workflows-overview-loader',
			'style' => 'height: 30px;'
		] ) );
		$this->getOutput()->addHTML( Html::element( 'div', [ 'id' => 'workflows-overview' ] ) );
	}
}

<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Special;

use MediaWiki\Html\Html;
use OOJSPlus\Special\OOJSGridSpecialPage;

class WorkflowOverview extends OOJSGridSpecialPage {
	public function __construct() {
		parent::__construct( 'WorkflowsOverview', 'workflows-view' );
	}

	public function doExecute( $subPage ) {
		$this->getOutput()->addModules( 'ext.workflows.special.overview' );
		$this->getOutput()->addHTML( Html::element( 'div', [
			'id' => 'workflows-overview-loader',
			'style' => 'height: 30px;'
		] ) );
		$this->getOutput()->addHTML( Html::element( 'div', [ 'id' => 'workflows-overview' ] ) );
	}
}

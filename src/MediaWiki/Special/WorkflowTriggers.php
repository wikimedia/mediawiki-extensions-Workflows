<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Special;

use MediaWiki\Html\Html;
use OOJSPlus\Special\OOJSCardSpecialPage;

class WorkflowTriggers extends OOJSCardSpecialPage {

	public function __construct() {
		parent::__construct( 'WorkflowTriggers', 'workflows-admin' );
	}

	/**
	 * @inheritDoc
	 */
	public function doExecute( $subPage ) {
		$out = $this->getOutput();

		$out->addModules( [ 'ext.workflows.triggers' ] );
		$out->addHTML(
			Html::rawElement( 'div', [
				'id' => 'workflows-triggers-hint',
				'style' => 'display: none'
			], $this->msg( 'workflows-triggers-edit-json-label' )->parse()
			)
		);
		$out->addHTML(
			Html::rawElement( 'div', [
				'id' => 'workflows-triggers-editor-cnt'
			] )
		);
	}
}

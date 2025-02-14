<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Action;

use MediaWiki\Html\Html;
use OOUI\ProgressBarWidget;

class EditTriggers extends \EditAction {

	/**
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @return string
	 */
	public function getRestriction() {
		return 'workflows-admin';
	}

	/**
	 * @return void
	 */
	public function show() {
		$this->useTransactionalTimeLimit();

		$out = $this->getOutput();
		$out->setRobotPolicy( 'noindex,nofollow' );
		$out->addBacklinkSubtitle( $this->getTitle() );

		$out->addModules( [ 'ext.workflows.triggers' ] );
		$this->getContext()->getOutput()->enableOOUI();
		$out->setPageTitle( $this->getContext()->msg( 'workflows-ui-trigger-page-title' )->text() );
		$out->addHTML(
			Html::rawElement( 'div', [
				'id' => 'workflows-triggers-editor-cnt'
			], new ProgressBarWidget( [ 'progress' => false ] )
			)
		);
	}
}

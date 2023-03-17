<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Action;

use EditAction;
use ExtensionRegistry;
use JsonContent;
use MediaWiki\EditPage\Constraint\UnicodeConstraint;
use OutputPage;

class EditDiagramAction extends EditAction {

	/**
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @return void
	 */
	public function show() {
		$this->useTransactionalTimeLimit();
		if ( !$this->getArticle()->getPage()->getTitle()->exists() ) {
			// Temp: Do not handle creation
			parent::show();
			return;
		}

		$out = $this->getOutput();
		$out->setRobotPolicy( 'noindex,nofollow' );
		$out->addBacklinkSubtitle( $this->getTitle() );

		$out->setPageTitle(
			$this->getContext()->msg( 'workflows-editor-title' )
				->params( $this->getTitle()->getText() )
				->text()
		);

		/** @var JsonContent $content */
		$content = $this->getArticle()->getPage()->getContent();
		$out->addHTML( \Html::element( 'div', [
			'id' => 'workflows-editor-panel',
			'data-action' => 'edit',
			'data-xml' => $content->getText(),
			'data-revid' => $out->getTitle()->getLatestRevID(),
			'data-token' => $out->getRequest()->getSession()->getToken()->toString(),
			'data-unicode_check' => UnicodeConstraint::VALID_UNICODE,
			'style' => 'height: 1000px; width: 100%'
		] ) );

		$this->addPluginModules( $out );
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return void
	 */
	private function addPluginModules( OutputPage $out ) {
		$modules = ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsEditorPluginModules' );
		$out->addJsConfigVars( 'workflowPluginModules', $modules );
	}
}

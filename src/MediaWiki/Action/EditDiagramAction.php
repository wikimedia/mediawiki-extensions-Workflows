<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Action;

use EditAction;
use MediaWiki\EditPage\Constraint\UnicodeConstraint;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\SlotRecord;
use RuntimeException;

class EditDiagramAction extends EditAction {

	/**
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @return true
	 */
	public function requiresWrite() {
		return true;
	}

	/**
	 * @return string
	 */
	public function getRestriction() {
		return 'edit';
	}

	/**
	 * @return void
	 */
	public function show() {
		$this->useTransactionalTimeLimit();

		$out = $this->getOutput();
		$out->setRobotPolicy( 'noindex,nofollow' );
		$out->addBacklinkSubtitle( $this->getTitle() );

		$out->setPageTitle(
			$this->getContext()->msg( 'workflows-editor-title' )
				->params( $this->getTitle()->getText() )
				->text()
		);
		if ( !$out->getTitle() ) {
			$oldid = '';
		} else {
			$oldid = $this->getContext()->getRequest()->getVal( 'oldid', $out->getTitle()->getLatestRevID() );
		}

		$revision = null;
		if ( $oldid ) {
			$revision = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionById( $oldid );
		}
		$xml = $revision?->getContent( SlotRecord::MAIN )?->getText();
		if ( !$xml ) {
			$xml = $this->getDefaultXml();
		}

		$out->addHTML( Html::element( 'div', [
			'id' => 'workflows-editor-panel',
			'data-action' => $this->getArticle()->getPage()->getTitle()->exists() ? 'edit' : 'create',
			'data-xml' => $xml,
			'data-revid' => $oldid,
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

	/**
	 * @return string
	 */
	private function getDefaultXml(): string {
		$file = dirname( __DIR__, 3 ) . '/workflow/empty.bpmn';
		if ( $file ) {
			return file_get_contents( $file );
		}
		throw new RuntimeException(
			$this->context->msg( 'workflows-editor-default-xml-not-found' )->text()
		);
	}

}

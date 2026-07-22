<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use Article;
use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\TextContentHandler;
use MediaWiki\Extension\Workflows\MediaWiki\Action\EditDiagramAction;
use MediaWiki\Extension\Workflows\MediaWiki\Action\EditDiagramXmlAction;
use MediaWiki\Extension\Workflows\MediaWiki\Content\BPMNContent;
use MediaWiki\Html\Html;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;

class BPMNHandler extends TextContentHandler {

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'BPMN' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getContentClass() {
		return BPMNContent::class;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsSections() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsCategories() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsRedirects() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionOverrides() {
		return [
			'edit' => EditDiagramAction::class,
			'editxml' => EditDiagramXmlAction::class,
		];
	}

	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		$dbKey = $cpoParams->getPage()->getDBkey();
		$title = Title::newFromDBkey( $dbKey );
		if ( $content->isRedirect() ) {
			$destTitle = $content->getRedirectTarget();
			if ( $destTitle instanceof Title ) {
				$output->addLink( $destTitle );
				if ( $cpoParams->getGenerateHtml() ) {
					$output->setRawText(
						Article::getRedirectHeaderHtml( $title->getPageLanguage(), $destTitle )
					);
					$output->addModuleStyles( [ 'mediawiki.action.view.redirectPage' ] );
				}
			}
			return;
		}

		$output->setRawText( Html::element( 'div', [
			'id' => 'workflows-editor-panel',
			'action' => 'view',
			'data-xml' => $content->getText()
		] ) );
	}
}

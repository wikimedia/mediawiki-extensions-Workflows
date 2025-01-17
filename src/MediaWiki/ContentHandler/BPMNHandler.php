<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use Article;
use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Extension\Workflows\MediaWiki\Action\EditDiagramAction;
use MediaWiki\Extension\Workflows\MediaWiki\Action\EditDiagramXmlAction;
use MediaWiki\Extension\Workflows\MediaWiki\Content\BPMNContent;
use MediaWiki\Title\Title;
use ParserOutput;
use TextContentHandler;

class BPMNHandler extends TextContentHandler {
	public function __construct( $modelId = 'BPMN' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );
	}

	protected function getContentClass() {
		return BPMNContent::class;
	}

	public function supportsSections() {
		return false;
	}

	public function supportsCategories() {
		return true;
	}

	public function supportsRedirects() {
		return false;
	}

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
					$output->setText(
						Article::getRedirectHeaderHtml( $title->getPageLanguage(), $destTitle )
					);
					$output->addModuleStyles( [ 'mediawiki.action.view.redirectPage' ] );
				}
			}
			return;
		}

		$output->setText( \Html::element( 'div', [
			'id' => 'workflows-editor-panel',
			'action' => 'view',
			'data-xml' => $content->getText()
		] ) );
	}
}

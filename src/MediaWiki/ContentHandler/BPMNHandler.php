<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use Article;
use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Extension\Workflows\MediaWiki\Content\BPMNContent;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use TextContentHandler;
use Title;

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
				if ( $cpoParams->generateHtml ) {
					$output->setText(
						Article::getRedirectHeaderHtml( $title->getPageLanguage(), $destTitle, false )
					);
					$output->addModuleStyles( [ 'mediawiki.action.view.redirectPage' ] );
				}
			}
			return;
		}

		$output = MediaWikiServices::getInstance()->getParser()->parse(
			"<syntaxhighlight lang=\"xml\">" . $content->getTextForSummary() . "</syntaxhighlight>",
			$title, $cpoParams->getParserOptions()
		);
	}
}

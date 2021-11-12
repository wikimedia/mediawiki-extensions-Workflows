<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use Article;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use ParserOutput;
use TextContent;
use Title;

class BPMNContent extends TextContent {
	public function __construct( $text ) {
		parent::__construct( $text, 'BPMN' );
	}

	protected function fillParserOutput(
		Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output
	) {
		if ( $this->isRedirect() ) {
			$destTitle = $this->getRedirectTarget();
			if ( $destTitle instanceof Title ) {
				$output->addLink( $destTitle );
				if ( $generateHtml ) {
					$chain = $this->getRedirectChain();
					if ( $chain ) {
						$output->setText(
							Article::getRedirectHeaderHtml( $title->getPageLanguage(), $chain, false )
						);
						$output->addModuleStyles( 'mediawiki.action.view.redirectPage' );
					}
				}
			}
			return;
		}

		$output = MediaWikiServices::getInstance()->getParser()->parse(
			"<syntaxhighlight lang=\"xml\">" . $this->getText() . "</syntaxhighlight>",
			$title, $options
		);
	}
}

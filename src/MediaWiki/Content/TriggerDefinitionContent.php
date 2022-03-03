<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Content;

use Html;
use JsonContent;
use MediaWiki\MediaWikiServices;
use OOUI\ProgressBarWidget;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Title;

class TriggerDefinitionContent extends JsonContent {
	public function __construct( $text, $modelId = 'workflow-triggers' ) {
		parent::__construct( $text, $modelId );
	}

	protected function fillParserOutput(
		Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output
	) {
		$context = RequestContext::getMain();
		$output->addModules( 'ext.workflows.triggers' );
		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		if ( !$pm->userHasRight( $context->getUser(), 'workflows-admin' ) ) {
			// TODO: Message could be improved
			$output->setText( $context->msg( 'badaccess' )->text() );
			return;
		}

		$context->getOutput()->enableOOUI();
		$output->setTitleText( $context->msg( 'workflows-ui-trigger-page-title' )->text() );
		$output->setText(
			Html::rawElement( 'div', [
					'id' => 'workflows-triggers-cnt'
				], new ProgressBarWidget( [ 'progress' => false ] )
			)
		);
	}
}

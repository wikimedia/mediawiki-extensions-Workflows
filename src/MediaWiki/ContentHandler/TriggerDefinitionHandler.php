<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use Content;
use Html;
use JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\MediaWikiServices;
use OOUI\ProgressBarWidget;
use ParserOutput;
use RequestContext;

class TriggerDefinitionHandler extends JsonContentHandler {
	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'workflow-triggers' ) {
		parent::__construct( $modelId );
	}

	/**
	 * @inheritDoc
	 */
	protected function getContentClass() {
		return TriggerDefinitionContent::class;
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
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsRedirects() {
		return false;
	}

	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		$context = RequestContext::getMain();
		$output->addModules( [ 'ext.workflows.triggers' ] );
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

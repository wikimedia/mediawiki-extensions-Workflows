<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\ContentHandler;

use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use OOUI\ProgressBarWidget;

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
		ParserOutput &$parserOutput
	) {
		$context = RequestContext::getMain();
		$parserOutput->addModules( [ 'ext.workflows.triggers' ] );
		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		if ( !$pm->userHasRight( $context->getUser(), 'workflows-admin' ) ) {
			// TODO: Message could be improved
			$parserOutput->setRawText( $context->msg( 'badaccess' )->text() );
			return;
		}

		$context->getOutput()->enableOOUI();
		$parserOutput->setTitleText( $context->msg( 'workflows-ui-trigger-page-title' )->text() );
		$parserOutput->setRawText(
			Html::rawElement( 'div', [
					'id' => 'workflows-triggers-cnt'
				], new ProgressBarWidget( [ 'progress' => false ] )
			)
		);
	}
}

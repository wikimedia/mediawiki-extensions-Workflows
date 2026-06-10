<?php

namespace MediaWiki\Extension\Workflows\Tag;

use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class MyOpenWorkflowsHandler implements ITagHandler {

	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		$user = $parser->getUserIdentity();
		if ( !$user->isRegistered() ) {
			return Html::element(
				'p',
				[],
				wfMessage( 'workflows-tag-myopenworkflows-anon' )->text()
			);
		}

		return Html::element( 'div', [ 'class' => 'workflows-my-open-workflows' ] );
	}
}

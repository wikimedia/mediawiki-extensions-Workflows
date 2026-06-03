<?php

namespace MediaWiki\Extension\Workflows\Tag;

use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class MyOpenWorkflowsHandler implements ITagHandler {

	public function __construct(
		private readonly WorkflowStateStore $stateStore,
		private readonly TitleFactory $titleFactory,
		private readonly LinkRenderer $linkRenderer
	) {
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

		$userObj = User::newFromIdentity( $user );
		$models = $this->stateStore->active()->initiatedByUser( $userObj )->query( true );

		if ( empty( $models ) ) {
			return Html::element(
				'p',
				[],
				wfMessage( 'workflows-tag-myopenworkflows-empty' )->text()
			);
		}

		$out = Html::openElement( 'ul', [ 'class' => 'workflows-my-open-workflows' ] );
		foreach ( $models as $model ) {
			$payload = $model->getPayload();
			$label = $this->getWorkflowLabel( $payload );
			$contextPage = $this->getPageFromContext( $payload );

			$out .= Html::openElement( 'li' );
			if ( $contextPage ) {
				$out .= $this->linkRenderer->makeLink( $contextPage, $label );
			} else {
				$out .= htmlspecialchars( $label );
			}
			$out .= Html::closeElement( 'li' );
		}
		$out .= Html::closeElement( 'ul' );

		return $out;
	}

	/**
	 * @param array $payload
	 * @return string
	 */
	private function getWorkflowLabel( array $payload ): string {
		$contextPage = $this->getPageFromContext( $payload );
		if ( $contextPage ) {
			$defTitle = $this->getDefinitionTitle( $payload );
			if ( $defTitle ) {
				return $defTitle . ': ' . $contextPage->getPrefixedText();
			}
			return $contextPage->getPrefixedText();
		}

		$defTitle = $this->getDefinitionTitle( $payload );
		if ( $defTitle ) {
			return $defTitle;
		}

		return wfMessage( 'workflows-tag-myopenworkflows-unknown' )->text();
	}

	/**
	 * @param array $payload
	 * @return string|null
	 */
	private function getDefinitionTitle( array $payload ): ?string {
		$def = $payload['definition'] ?? null;
		if ( !$def ) {
			return null;
		}
		$definitionSource = DefinitionSource::newFromArray( $def );
		return $definitionSource->getTitle();
	}

	/**
	 * @param array $payload
	 * @return \MediaWiki\Title\Title|null
	 */
	private function getPageFromContext( array $payload ): ?\MediaWiki\Title\Title {
		$pageId = $payload['context']['pageId'] ?? null;
		if ( !$pageId ) {
			return null;
		}
		return $this->titleFactory->newFromID( (int)$pageId );
	}
}

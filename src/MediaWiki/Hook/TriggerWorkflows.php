<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\TriggerRunner;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use Title;

class TriggerWorkflows implements PageSaveCompleteHook, PageMoveCompleteHook {
	/** @var TriggerRunner */
	private $runner;

	/**
	 * @param TriggerRunner $runner
	 */
	public function __construct( TriggerRunner $runner ) {
		$this->runner = $runner;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveComplete( $old, $new, $user, $pageid, $redirid, $reason, $revision ) {
		if ( $this->shouldSkip() ) {
			return true;
		}
		$this->runner->triggerAllOfType( 'move', Title::newFromLinkTarget( $new ) );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete(
		$wikiPage, $user, $summary, $flags, $revisionRecord, $editResult
	) {
		if ( $this->shouldSkip() ) {
			return true;
		}
		$type = 'edit';
		if ( $flags === EDIT_NEW ) {
			$type = 'create';
		}
		$this->runner->triggerAllOfType( $type, $wikiPage->getTitle(), [
			'editType' => $revisionRecord->isMinor() ? 'minor' : 'major'
		] );

		return true;
	}

	/**
	 * @return bool
	 */
	private function shouldSkip() {
		return defined( 'MW_PHPUNIT_TEST' );
	}
}

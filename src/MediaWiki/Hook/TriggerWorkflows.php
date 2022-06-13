<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\TriggerRunner;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;

class TriggerWorkflows implements PageSaveCompleteHook {
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
	public function onPageSaveComplete(
		$wikiPage, $user, $summary, $flags, $revisionRecord, $editResult
	) {
		if ( $this->shouldSkip() ) {
			return true;
		}
		$type = 'edit';
		if ( $flags & EDIT_NEW ) {
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

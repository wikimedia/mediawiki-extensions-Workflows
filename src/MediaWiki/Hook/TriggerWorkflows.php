<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\TriggerRunner;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use Psr\Log\LoggerInterface;

class TriggerWorkflows implements PageSaveCompleteHook {
	/** @var TriggerRunner */
	private $runner;

	/** @var LoggerInterface */
	private $logger = null;

	/**
	 * @param TriggerRunner $runner
	 * @param LoggerInterface $logger
	 */
	public function __construct( TriggerRunner $runner, LoggerInterface $logger ) {
		$this->runner = $runner;
		$this->logger = $logger;
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
		$this->logger->debug( "Detected page save" );
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

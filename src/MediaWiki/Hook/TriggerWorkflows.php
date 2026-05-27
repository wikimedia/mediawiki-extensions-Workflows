<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Extension\Workflows\TriggerRunner;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use Psr\Log\LoggerInterface;

class TriggerWorkflows implements PageSaveCompleteHook {
	/** @var TriggerRunner */
	private $runner;

	/** @var TriggerRepo */
	private $triggerRepo;

	/** @var LoggerInterface */
	private $logger = null;

	/**
	 * @param TriggerRunner $runner
	 * @param LoggerInterface $logger
	 * @param TriggerRepo $triggerRepo
	 */
	public function __construct( TriggerRunner $runner, LoggerInterface $logger, TriggerRepo $triggerRepo ) {
		$this->runner = $runner;
		$this->logger = $logger;
		$this->triggerRepo = $triggerRepo;
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

		$savedTitle = $wikiPage->getTitle();

		$triggersTitle = $this->triggerRepo->getTitle();
		if ( $triggersTitle && $savedTitle->equals( $triggersTitle ) ) {
			$this->triggerRepo->invalidateCache();
		}

		$this->logger->debug( "Detected page save" );
		$type = 'edit';
		if ( $flags & EDIT_NEW ) {
			$type = 'create';
		}
		$this->runner->triggerAllOfType( $type, $savedTitle, [
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

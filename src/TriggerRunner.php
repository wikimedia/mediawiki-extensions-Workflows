<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Page\PageProps;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;

class TriggerRunner {
	/** @var TriggerRepo */
	private $repo;
	/** @var LoggerInterface */
	private $logger;

	/** @var PageProps */
	private PageProps $pageProps;

	/** @var array */
	private $triggered = [];

	/**
	 * @param TriggerRepo $repo
	 * @param LoggerInterface $logger
	 * @param PageProps $pageProps
	 */
	public function __construct( TriggerRepo $repo, LoggerInterface $logger, PageProps $pageProps ) {
		$this->repo = $repo;
		$this->logger = $logger;
		$this->pageProps = $pageProps;
	}

	/**
	 * @param string $type
	 * @param Title|null $title
	 * @param array $qualifyingData
	 */
	public function triggerAllOfType( $type, ?Title $title = null, $qualifyingData = [] ) {
		$this->logger->debug( "Triggering workflows for $type" );
		$triggers = $this->repo->getActive( $type );
		foreach ( $triggers as $trigger ) {
			if ( in_array( $trigger->getId(), $this->triggered ) ) {
				// Do not evaluate same trigger multiple times in one request
				continue;
			}
			$this->logger->debug( "Evaluating trigger {$trigger->getId()}" );
			$this->triggered[] = $trigger->getId();
			if ( $trigger instanceof IPageTrigger ) {
				if ( $title === null || !$this->canRunWorkflowForTitle( $title ) ) {
					$this->logger->error( 'Page context trigger called without title', [
						'trigger' => $trigger->getId(),
						'action' => $type
					] );
					continue;
				}
				$this->logger->debug( "Page context trigger called with title {$title->getPrefixedText()}" );
				$trigger->setTitle( $title );
			}

			$this->logger->debug( "Evaluating trigger {$trigger->getId()}", [
				'trigger' => $trigger->getId(),
				'action' => $type,
				'qualifyingData' => $qualifyingData
			] );
			if ( $trigger->shouldTrigger( $qualifyingData ) ) {
				$res = $trigger->trigger();
				$logContext = [
					'trigger' => $trigger->getId(),
					'attributes' => $trigger->getAttributes()
				];
				if ( $res ) {
					$this->logger->info( 'Start of workflow based on trigger', $logContext );
				} else {
					$this->logger->error( 'Could not start a workflow based on a trigger', $logContext );
				}
			} else {
				$this->logger->debug( "Trigger did not qualify" );
			}
		}
	}

	/**
	 * General conditions to qualify title as capable of running a WF
	 *
	 * @param Title $title
	 * @return bool
	 */
	private function canRunWorkflowForTitle( Title $title ) {
		$isEligible = $title->getContentModel() === 'wikitext' && $title->isContentPage();
		if ( !$isEligible ) {
			return false;
		}
		if ( !$title->exists() ) {
			return $isEligible;
		}
		$props = $this->pageProps->getProperties( $title, [ 'NOWORKFLOWEXECUTION' ] );
		$props = $props[$title->getArticleId()] ?? [];
		return !isset( $props['NOWORKFLOWEXECUTION'] );
	}
}

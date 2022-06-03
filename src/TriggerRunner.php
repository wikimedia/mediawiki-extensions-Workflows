<?php

namespace MediaWiki\Extension\Workflows;

use Psr\Log\LoggerInterface;
use Title;

class TriggerRunner {
	/** @var TriggerRepo */
	private $repo;
	/** @var LoggerInterface */
	private $logger;
	/** @var array */
	private $triggered = [];

	/**
	 * @param TriggerRepo $repo
	 * @param LoggerInterface $logger
	 */
	public function __construct( TriggerRepo $repo, LoggerInterface $logger ) {
		$this->repo = $repo;
		$this->logger = $logger;
	}

	/**
	 * @param string $type
	 * @param Title|null $title
	 * @param array $qualifyingData
	 */
	public function triggerAllOfType( $type, ?Title $title = null, $qualifyingData = [] ) {
		$triggers = $this->repo->getActive( $type );
		foreach ( $triggers as $trigger ) {
			if ( in_array( $trigger->getId(), $this->triggered ) ) {
				// Do not evaluate same trigger multiple times in one request
				continue;
			}
			$this->triggered[] = $trigger->getId();
			if ( $trigger instanceof IPageTrigger ) {
				if ( $title === null || !$this->canRunWorkflowForTitle( $title ) ) {
					$this->logger->error( 'Page context trigger called without title', [
						'trigger' => $trigger->getId(),
						'action' => $type
					] );
					continue;
				}
				$trigger->setTitle( $title );
			}

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
		return $title->getContentModel() === 'wikitext' && $title->isContentPage();
	}
}

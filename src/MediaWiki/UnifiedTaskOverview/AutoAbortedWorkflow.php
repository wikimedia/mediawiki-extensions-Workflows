<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use Exception;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\Special\WorkflowOverview;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Special\SpecialPageFactory;
use Message;
use RawMessage;
use Title;

class AutoAbortedWorkflow implements ITaskDescriptor {
	/** @var Workflow */
	protected $workflow;
	/** @var WorkflowOverview */
	protected $workflowOverviewSP;
	/** @var Title|null */
	protected $title = null;

	/**
	 * @param Workflow $workflow
	 */
	public function __construct( Workflow $workflow, WorkflowOverview $workflowOverview ) {
		$this->workflow = $workflow;
		$this->workflowOverviewSP = $workflowOverview;

		$title = $this->workflow->getContext()->getContextPage();
		if ( $title instanceof Title ) {
			$this->title = $title;
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'workflows-auto-aborted-workflow';
	}

	/**
	 * @return string
	 */
	public function getURL(): string {
		return $this->workflowOverviewSP->getPageTitle()->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getHeader(): Message {
		return new RawMessage( $this->title->getPrefixedText() );
	}

	/**
	 * @return Message
	 * @throws Exception
	 */
	public function getSubHeader(): Message {
		return  \Message::newFromKey(
			'workflows-uto-auto-aborted-workflow-' . $this->getAbortType()
		);
	}

	/**
	 * @return Message
	 */
	public function getBody(): Message {
		return new RawMessage( $this->workflow->getStateMessage()['message'] );
	}

	/**
	 * @return int
	 */
	public function getSortKey(): int {
		return 1;
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.workflows.uto.styles' ];
	}

	private function getAbortType() {
		return $this->workflow->getStateMessage()['type'];
	}
}

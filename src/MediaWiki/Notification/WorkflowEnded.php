<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowEnded extends BaseNotification {

	/** @var string */
	protected $workflowName;

	/**
	 * @param User $targetUser
	 * @param string $workflowName Workflow name
	 * @param Title $title Target page title object
	 */
	public function __construct( User $targetUser, string $workflowName, Title $title ) {
		parent::__construct(
			'workflows-ended',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		$this->workflowName = $workflowName;

		$this->addAffectedUsers( [ $targetUser ] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return parent::getParams() + [
			'workflow-name' => $this->workflowName
		];
	}
}

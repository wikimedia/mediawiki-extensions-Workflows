<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowAborted extends BaseNotification {
	/** @var string */
	protected $reason;
	/** @var string */
	protected $workflowName;

	/**
	 * @param User|User[] $targetUsers Recipient or list of recipients
	 * @param string $workflowName Workflow name
	 * @param Title|null $title Target page title object
	 * @param string $reason
	 */
	public function __construct( $targetUsers, string $workflowName, ?Title $title, string $reason ) {
		if ( !$title instanceof Title ) {
			$title = Title::newMainPage();
		}
		parent::__construct(
			'workflows-aborted',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);
		if ( !is_array( $targetUsers ) ) {
			$targetUsers = [ $targetUsers ];
		}

		$this->reason = $reason;
		$this->workflowName = $workflowName;
		$this->addAffectedUsers( $targetUsers );
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return parent::getParams() + [
			'reason' => $this->reason,
			'workflow-name' => $this->workflowName
		];
	}
}

<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use Message;
use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowEnded extends BaseNotification {

	/** @var Message */
	protected $workflowNameMsg;

	/**
	 * @param User $targetUser
	 * @param Message $workflowNameMsg Workflow name
	 * @param Title|null $title Target page title object
	 */
	public function __construct( User $targetUser, Message $workflowNameMsg, ?Title $title ) {
		if ( !$title instanceof Title ) {
			$title = Title::newMainPage();
		}
		parent::__construct(
			'workflows-ended',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		$this->workflowNameMsg = $workflowNameMsg;

		$this->addAffectedUsers( [ $targetUser ] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return parent::getParams() + [
			'workflow-name' => $this->workflowNameMsg
		];
	}
}

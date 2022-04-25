<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use Message;
use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowAborted extends BaseNotification {
	/** @var string */
	protected $reason;
	/** @var Message */
	protected $workflowNameMsg;

	/**
	 * @param User|User[] $targetUsers Recipient or list of recipients
	 * @param Message $workflowNameMsg Message, containing workflow name. Will be translated for recipient
	 * @param Title|null $title Target page title object
	 * @param string $reason
	 */
	public function __construct( $targetUsers, Message $workflowNameMsg, ?Title $title, string $reason ) {
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
		$this->workflowNameMsg = $workflowNameMsg;
		$this->addAffectedUsers( $targetUsers );
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return parent::getParams() + [
			'reason' => $this->reason,
			'workflow-name' => $this->workflowNameMsg
		];
	}
}

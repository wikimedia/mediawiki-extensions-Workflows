<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowAborted extends BaseNotification {
	/** @var string */
	protected $reason;

	/**
	 * @param User $targetUser
	 * @param Title $title Target page title object
	 * @param string $reason
	 */
	public function __construct( $targetUser, $title, $reason ) {
		parent::__construct(
			'workflows-aborted',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);
		$this->reason = $reason;
		$this->addAffectedUsers( [ $targetUser ] );
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return parent::getParams() + [
			'reason' => $this->reason,
		];
	}
}

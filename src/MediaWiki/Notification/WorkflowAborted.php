<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowAborted extends BaseNotification {

	/**
	 * @param User $targetUser
	 * @param Title $title Target page title object
	 * @param string $activity
	 */
	public function __construct( $targetUser, $title ) {
		parent::__construct(
			'workflows-aborted',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		$this->addAffectedUsers( [ $targetUser ] );
	}
}

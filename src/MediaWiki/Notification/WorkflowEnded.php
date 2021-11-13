<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use Title;
use User;

class WorkflowEnded extends BaseNotification {

	/**
	 * @param User $targetUser
	 * @param Title $title Target page title object
	 */
	public function __construct( $targetUser, $title ) {
		parent::__construct(
			'workflows-ended',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		$this->addAffectedUsers( [ $targetUser ] );
	}
}

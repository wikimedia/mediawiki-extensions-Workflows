<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification;

use Title;
use User;

class VoteAccept extends VoteNotification {
	/**
	 * @param User $agent Agent of notification
	 * @param Title $title Target page title object
	 * @param User|null $owner User to receive notification
	 * @param string $activity
	 * @param string $comment Additional comment
	 */
	public function __construct( $agent, $title, $owner, $activity, $comment ) {
		parent::__construct( 'workflows-vote-accept', $agent, $title, $owner, $activity, $comment );
	}
}

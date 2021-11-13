<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification;

use Title;
use User;

class VoteDelegate extends VoteNotification {
	/** @var User */
	protected $delegateTo;

	/**
	 * @param User $agent Agent of notification
	 * @param Title $title Target page title object
	 * @param User $owner User to receive notification
	 * @param string $activity
	 * @param string $comment Additional comment
	 * @param User $delegateTo
	 */
	public function __construct( $agent, $title, $owner, $activity, $comment, $delegateTo ) {
		parent::__construct( 'workflows-vote-delegate', $agent, $title, $owner, $activity, $comment );
		$this->delegateTo = $delegateTo;

		$this->addAffectedUsers( [ $this->delegateTo ] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return parent::getParams() + [
			'delegated-from' => $this->owner->getRealName() ?? $this->owner->getName(),
		];
	}
}

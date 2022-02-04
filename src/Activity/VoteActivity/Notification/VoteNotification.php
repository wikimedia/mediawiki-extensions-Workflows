<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use SpecialPage;
use Title;
use User;

abstract class VoteNotification extends BaseNotification {

	/**
	 * @var string
	 */
	protected $comment;
	/** @var string */
	protected $activity;
	/** @var User */
	protected $owner;

	/**
	 * @param string $key
	 * @param User $agent Agent of notification
	 * @param Title $title Target page title object
	 * @param User|null $owner User to receive notification
	 * @param string $activity
	 * @param string $comment Additional comment
	 */
	public function __construct( $key, $agent, $title, $owner, $activity, $comment ) {
		parent::__construct( $key, $agent, $title );

		if ( $owner instanceof User ) {
			$this->addAffectedUsers( [ $owner ] );
		}
		$this->owner = $owner;
		$this->comment = $comment;
		$this->activity = $activity;
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return [
			'comment' => $this->comment,
			'activity-type' => $this->activity
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getSecondaryLinks() {
		return [
			'mytasks' => SpecialPage::getTitleFor( 'UnifiedTaskOverview' )->getFullURL()
		];
	}
}

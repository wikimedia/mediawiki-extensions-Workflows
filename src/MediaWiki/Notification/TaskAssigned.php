<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use SpecialPage;
use Title;
use User;

class TaskAssigned extends BaseNotification {
	/** @var string */
	protected $activity;

	/**
	 * @param User $targetUser User task is assigned to
	 * @param Title $title Target page title object
	 * @param string $activity
	 */
	public function __construct( $targetUser, $title, $activity ) {
		parent::__construct(
			'workflows-task-assign',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		$this->addAffectedUsers( [ $targetUser ] );
		$this->activity = $activity;
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return [
			'activity-type' => $this->activity,
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

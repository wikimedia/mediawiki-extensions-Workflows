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
	 * @param array $targetUsers
	 * @param Title|null $title Target page title object
	 * @param string $activity
	 */
	public function __construct( $targetUsers, $title, $activity ) {
		if ( !$title instanceof Title ) {
			$title = Title::newMainPage();
		}
		parent::__construct(
			'workflows-task-assign',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		if ( !is_array( $targetUsers ) ) {
			$targetUsers = [ $targetUsers ];
		}
		$this->addAffectedUsers( $targetUsers );
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

<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use SpecialPage;
use Title;
use User;

class FeedbackNotification extends BaseNotification {

	/**
	 * @var string
	 */
	protected $feedback;
	/** @var string */
	protected $activity;

	/**
	 * @param User $agent Agent of notification
	 * @param Title $title Target page title object
	 * @param User|null $owner User to receive notification
	 * @param string $activity
	 * @param string $feedback Additional comment
	 */
	public function __construct( $agent, $title, $owner, $activity, $feedback ) {
		parent::__construct( 'workflows-feedback', $agent, $title );

		if ( $owner instanceof User ) {
			$this->addAffectedUsers( [ $owner ] );
		}
		$this->feedback = $feedback;
		$this->activity = $activity;
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return [
			'feedback' => $this->feedback,
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

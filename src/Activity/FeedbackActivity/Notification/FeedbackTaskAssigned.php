<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity\Notification;

use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use SpecialPage;
use Title;
use User;

class FeedbackTaskAssigned extends BaseNotification {
	/** @var string */
	protected $activity;
	/** @var User|null */
	protected $initiator;
	/** @var string */
	protected $instructions;

	/**
	 * @param array $targetUsers
	 * @param Title $title Target page title object
	 * @param string $activity
	 * @param User|null $initiator Workflow initiator
	 * @param string $instructions Instructions for a user, who task is assigned to
	 */
	public function __construct( $targetUsers, $title, $activity, $initiator, string $instructions ) {
		parent::__construct(
			'workflows-feedback-task-assign',
			User::newSystemUser( 'Mediawiki default' ),
			$title
		);

		if ( !is_array( $targetUsers ) ) {
			$targetUsers = [ $targetUsers ];
		}
		$this->addAffectedUsers( $targetUsers );
		$this->activity = $activity;
		$this->initiator = $initiator;
		$this->instructions = $instructions;
	}

	/**
	 * @inheritDoc
	 */
	public function getParams() {
		return parent::getParams() + [
			'activity-type' => $this->activity,
			'initiator' => $this->initiator instanceof User ? $this->initiator->getName() : '-',
			'instructions' => $this->instructions
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getSecondaryLinks() {
		return [
			// TODO: Refactor out implicit dependency to "Extension:UnifiedTaskOverview"
			'mytasks' => SpecialPage::getTitleFor( 'UnifiedTaskOverview' )->getFullURL()
		];
	}
}

<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Notification;

use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Notifications\BaseNotification;
use SpecialPage;
use Title;
use User;

class DueDateProximity extends BaseNotification {
	/** @var string */
	protected $activity;

	/**
	 * @param User $agent
	 * @param array $assigned
	 * @param Title $title
	 * @param string $activity
	 */
	public function __construct( $agent, $assigned, $title, $activity ) {
		$agent = $agent ?? User::newSystemUser( 'Mediawiki default' );
		$assigned = array_map( static function ( $username ) {
			return MediaWikiServices::getInstance()->getUserFactory()->newFromName( $username );
		}, $assigned );
		$assigned = array_filter( $assigned, static function ( User $user ) {
			return $user->isRegistered();
		} );
		if ( !$title instanceof Title ) {
			$title = Title::newMainPage();
		}
		parent::__construct(
			'workflows-due-date-proximity',
			$agent,
			$title
		);

		$this->addAffectedUsers( $assigned );
		$this->addAffectedUsers( [ $agent ] );
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

<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity\Action;

class ActionList {

	/**
	 * User votes "yes" or "no"
	 */
	public const ACTION_VOTE = 'vote';

	/**
	 * User delegates voting to another user
	 */
	public const ACTION_DELEGATE = 'delegate';

	/**
	 * Non-existing action.
	 * Used for testing purposes
	 */
	public const ACTION_INVALID = 'invalid';

	/**
	 * Returns array with all possible vote user actions
	 *
	 * @return array List of all possible vote user actions
	 */
	public static function allActions(): array {
		return [
			self::ACTION_VOTE,
			self::ACTION_DELEGATE
		];
	}
}

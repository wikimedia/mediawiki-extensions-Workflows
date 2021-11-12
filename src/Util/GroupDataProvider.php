<?php

namespace MediaWiki\Extension\Workflows\Util;

use MediaWiki\MediaWikiServices;

/**
 * Data access object class for group vote activity.
 * Used mostly for getting information about user groups
 */
class GroupDataProvider {

	/**
	 * Gets users in specified group
	 *
	 * @param string $groupName Name of group
	 * @return array Array of users, where key int user ID and value is user name
	 */
	public function getUsersInGroup( string $groupName ): array {
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
			DB_REPLICA
		);
		$res = $db->select(
			[ 'user', 'user_groups' ],
			[ 'user_id', 'user_name' ],
			[
				'ug_group' => $groupName,
				'ug_user = user_id'
			],
			__METHOD__
		);

		$users = [];
		foreach ( $res as $row ) {
			$users[(int)$row->user_id] = $row->user_name;
		}

		return $users;
	}

	/**
	 * Gets number of users in specified group
	 *
	 * @param string $groupName Name of group
	 * @return int Number of users
	 */
	public function getNumberOfUsersInGroup( string $groupName ): int {
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
			DB_REPLICA
		);
		$res = $db->selectRow(
			'user_groups',
			'COUNT(*) as count',
			[ 'ug_group' => $groupName ],
			__METHOD__
		);

		return (int)$res->count;
	}

}

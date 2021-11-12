<?php

namespace MediaWiki\Extension\Workflows;

use DateTime;

interface UserInteractiveActivity extends IActivity {
	/**
	 * Returns name of the RL module that contains
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule;

	/**
	 * Does this activity initializes the workflow
	 * @return bool
	 */
	public function isInitializer(): bool;

	/**
	 * Get list of usernames that are targeted by this activity
	 *
	 * @param array $properties Task properties
	 * @return array|null if anyone can complete
	 */
	public function getTargetUsers( array $properties ): ?array;

	/**
	 * Set due date for the activity
	 * Will be passed a value at the time of activity start
	 *
	 * @param DateTime $date
	 * @return mixed
	 */
	public function setDueDate( DateTime $date );

	/**
	 * Get expiration date
	 *
	 * @return DateTime|null if does not expire
	 */
	public function getDueDate(): ?DateTime;

	/**
	 * Used for presenting the activity on the UI
	 *
	 * @return IActivityDescriptor
	 */
	public function getActivityDescriptor(): IActivityDescriptor;
}

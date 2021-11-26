<?php

namespace MediaWiki\Extension\Workflows;

use JsonSerializable;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use Message;
use MWStake\MediaWiki\Component\Notifications\INotification;

interface IActivityDescriptor extends JsonSerializable {
	/**
	 * Get text for the AlertBanner
	 *
	 * @return Message
	 */
	public function getAlertText(): Message;

	/**
	 * Text for the "complete task" button
	 *
	 * @return Message
	 */
	public function getCompleteButtonText(): Message;

	/**
	 * @return Message
	 */
	public function getActivityName(): Message;

	/**
	 * Get UI-friendly due date
	 *
	 * @return string|null if
	 */
	public function getDueDate();

	/**
	 * How many days to due date
	 * @return int|null
	 */
	public function getDueDateProximity();

	/**
	 * Get UnifiedTaskOverview task descriptor
	 *
	 * @param Workflow $workflow
	 * @return ITaskDescriptor
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor;

	/**
	 * @param ActivityEvent $event
	 * @param Workflow $workflow
	 * @return INotification|null
	 */
	public function getNotificationFor( ActivityEvent $event, Workflow $workflow ): ?INotification;

	/**
	 * Return user-formatted data that was a result of the activity
	 * Basically, nicely formatted running-data
	 * Only needs to return something on completed activities,
	 * and on activities that had loop-completed event
	 *
	 * @param Workflow $workflow
	 * @return array
	 */
	public function getHistoryReport( Workflow $workflow ): array;
}

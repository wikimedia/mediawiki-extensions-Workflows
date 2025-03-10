<?php

namespace MediaWiki\Extension\Workflows;

use JsonSerializable;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\Events\INotificationEvent;

interface IActivityDescriptor extends JsonSerializable {

	/**
	 * @return Message
	 */
	public function getActivityName(): Message;

	/**
	 * Actual translated (if there is translation for it) task name.
	 * There is some difference between "task name" and "activity name".
	 * Here is an example:
	 *
	 * <bpmn:userTask id="UserVoteReview" name="user-vote-review">
	 * 		<bpmn:extensionElements>
	 * 			<wf:type>user_vote</wf:type>
	 * 		</bpmn:extensionElements>
	 * 		...
	 * </bpmn:userTask>
	 *
	 * In this case "user-vote-review" is specific task name, and "User vote" - name of activity.
	 * One "User vote" activity may be used several times during the workflow, in context of different tasks.
	 * For example, "user-vote-review", "user-vote-approve" etc.
	 *
	 * Task name is written in such way (lowercase with dashes separation) to easily look for translation.
	 * Obviously, if new task appear - it should be properly translated.
	 *
	 * @return Message
	 */
	public function getTaskName(): Message;

	/**
	 * Gets in an array of activity properties and translates them.
	 * If for some properties necessary messages does not exist - they will not be translated.
	 *
	 * @param array $properties Array with activity properties, which can be got here
	 * 		{@link \MediaWiki\Extension\Workflows\ActivityManager::getActivityPublicProperties()}.
	 *
	 * @return array Array with translated properties, where key is translated property name
	 * 		and value is property value
	 */
	public function getLocalizedProperties( array $properties ): array;

	/**
	 * @param ActivityEvent $event
	 * @param Workflow $workflow
	 * @return INotificationEvent|null
	 */
	public function getNotificationFor( ActivityEvent $event, Workflow $workflow ): ?INotificationEvent;

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

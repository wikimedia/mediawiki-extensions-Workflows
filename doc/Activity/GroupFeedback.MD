# SetTemplateParams Activity

Key: `group_feedback`
Type: manual, mutli-user interaction required

Example:

	<bpmn:task id="GroupFeedback" name="Group feedback">
    	<bpmn:extensionElements>
			<wf:type>group_feedback</wf:type>
		</bpmn:extensionElements>
			<!-- Can be substituted for `assigned_users` to defined a fixed set of assigned users, instead of a group -->
			<bpmn:property name="assigned_group">sysop</bpmn:property>

			<bpmn:property name="instructions"></bpmn:property>
			<bpmn:property name="comment"/>
			<bpmn:property name="users_feedbacks"/>
			<bpmn:property name="due_date">20221201101010</bpmn:property>
			<bpmn:property name="threshold_unit">user</bpmn:property>
			<bpmn:property name="threshold_value">2</bpmn:property>
      ...

    </bpmn:task>


## Description
This activity allows users from the group specified in the `assigned_group` (or `assigned_users`) property to provide feedback on the workflow. The feedback can be a comment, a rating, or both. The activity can be configured to require a minimum number of users to provide feedback before the workflow can continue.
Actual number of required users is controlled by threshold properties:
- `threshold_unit` - can be `user` or `percent` - absolute number of users required to complete the activity
- `threshold_value` - number of users or percentage of users required to provide feedback

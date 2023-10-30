# GroupVote Activity

Key: `group_vote`
Type: manual, mutli-user interaction required

Example:

	<bpmn:task id="GroupVote" name="Group vote">
    	<bpmn:extensionElements>
			<wf:type>group_vote</wf:type>
		</bpmn:extensionElements>
			<!-- Can be substituted for `assigned_users` to defined a fixed set of assigned users, instead of a group -->
			<bpmn:property name="assigned_group">sysop</bpmn:property>

			<bpmn:property name="instructions"></bpmn:property>
			<bpmn:property name="comment"/>
			<!-- for internal use only, do not set -->
			<bpmn:property name="users_voted"/>
			<bpmn:property name="due_date">20221201101010</bpmn:property>
			<bpmn:property name="threshold_yes_unit">user</bpmn:property>
			<bpmn:property name="threshold_yes_value">2</bpmn:property>
			<bpmn:property name="threshold_no_unit">user</bpmn:property>
			<bpmn:property name="threshold_no_value">2</bpmn:property>
      ...

    </bpmn:task>


## Description
This activity allows users from the group specified in the `assigned_group` (or `assigned_users`) property to vote on page quality.
Actual number of required users for approval or rejection is controlled by threshold properties:
- `threshold_yes_unit` - can be `user` or `percent` - absolute number of users required to a approve a page
- `threshold_yes_value` - number of users or percentage of users required to approve a page
- `threshold_no_unit` - can be `user` or `percent` - absolute number of users required to a reject a page
- `threshold_no_value` - number of users or percentage of users required to reject a page

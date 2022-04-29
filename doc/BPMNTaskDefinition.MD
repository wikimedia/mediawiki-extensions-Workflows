# Defining a task in BPMN diagrams

## Task types
The engine recognizes two main types of a task:
- tasks where user action is required, `userTask` and `manualTask`. Process will halt when these tasks are reached, pending user action.
- tasks that are executed automatically, `task`, `serviceTask`... and all other types. Process will automatically complete these tasks.

## WF specific data
This is custom data that is evaluated by Workflows extension, and its added exclusively within `<bmpn:extensionElements>` tags, within the task definition

### Assigning Activity to a task
As we have many `IActivity` classes defined in code, we need a way to connect BPMN task definition with the background implementation.
To do this, specify `<wf:type>activity_key</wf:type>` within `extensionElements` tag.

### Assigning a form to a task
tasks of type `userTask`, usually are related to the form, to be displayed to the user. There are several ways to specify a form for a task:
- Letting `IActivity` decide. If `wf:type` is specified, the underlying activity implementation can specify which form to use
- Specifying form defined on wiki. Use `<wf:form>FormName</wf:form>` to specify a form defined on wiki. In this case, it would
reference form  `FormName.form`.
- Specifying form defined in code. We can also use forms defined in the extensions. These forms must be instances of
`workflows.object.form.Form`. To specify it, use complex type `wf:formModule`

		<wf:formModule>
			 <wf:module>resource.loader.module</wf:module>
  			 <wf:class>class.name</wf:class>
  			 <!-- or specify callback that will instantiate form. Specify either class or callback, not both -->
  			 <wf:callback>class.name</wf:callback>
	   	</wf:formModule>

## Properties
Properties of a task, and therefore the activity define which pieces of data activity operates on. Data passed to the activity
must be a subset of these properties, and also output data of the activity must be consisted of these properties only.

Properties are defined as `<bpmn:property name="MyProperty" default="MyDefaultValue"/>` . Default value will be used if no data for this prop
is passed during the execution. Its optional.

### Internal properties
Property can be marked as internal by specifying `internal="1"` ( `<bpmn:property name="MyProperty" internal="1" default="MyDefaultValue"/>` ).

This means that the property will not be sent in API requests (to the client) and cannot be set by data coming from the client.

### Property value syntax
How to set the value of the property?

Setting the default value:
- As an attribute: `<bpmn:property name="myprop" default="test"/>`
- As the node value `<bpmn:property name="myprop">test</bpmn:property>`

Both of these ways support specifying the value dynamically from another (past) activity or WF context.
Syntax for that is `{{{ActivityName.propertyName}}}`. For specifying context values, its `{{{definition_context.contextItem}}}` where `contextItem` is key of the context item

## Multi-instance characteristics
Engine supports parallel and sequential multi-instance execution. Marking a task as multi-instance is done using
`<bpmn:multiInstanceLoopCharacteristics>` tag.
By default, such a declaration will create parallel execution, add `isSequential="true"` as the tag attribute
to mark it as sequential.

With both of these, we must defined properties that will be used as data sets for each instance:

	<bpmn:multiInstanceLoopCharacteristics isSequential="true">
      	<bpmn:extensionElements>
      		<wf:multiInstanceProperty source="Activity1.user" target="user"/>
      		<wf:multiInstanceProperty source="Activity1.type" target="type" />
      	</bpmn:extensionElements>
      </bpmn:multiInstanceLoopCharacteristics>

All `multiInstanceProperty`(-ies) must come from the running data from the context.
Source of the data is specified in `source` attribute, which will set corresponding task property, specified in `target`, when activity is started.
Source should follow the pattern:
- `ActivityID.property` for data from past activities, or
- `propertyName` for data retrieved from the same task (from its default values); In this case `target` is not necessary, unless different from the source.

For example, if we pass

	{
		"user": [ "UserA", "UserB" ],
		"type": [ "single", "double" ]
	}

as data to the task, it will create two instances, each of which will be passed one set of data:

	new Activity( "UserA", "single" );
	new Activity( "UserB", "double" );


### Parallel
Parallel execution means that, once this task is reached, engine will create multiple instances of corresponding activity immediately, one for each data set passed.
Workflow will only continue from this task, once all of the instances are completed

### Sequential
Sequential execution means that, once this task is reached, engine will create one instance of the corresponding activity
passing in the first data set. Once that is completed, it will create next one, and so on, until all of the data sets are used up.
Only then workflow will go on.

### True parallel, with `parallelGateway`
It is also possible to utilize `parallelGateways` to run multiple tasks in parallel. Difference to multi-instance flag on a task
is that this way, multiple DIFFERENT tasks can be run at the same time.
Constrains:
- Parallel tasks must be preceeded by a `parallelGateway`
- All tasks must lead back to a single element (no separation of paths)

## Looping tasks
Looping tasks are similar to sequential tasks, but there are key differences;
- All of the loops are executed in a single instance
- Every loop received the whole data, no separate data sets
- Activity itself, rather than the engine, determines when its completed

Its usually used to execute the same thing over and over until certain condition is met, as opposed to multi-instance, where
same operation is called on different data.

Task can be declared as "Looping" using `<bpmn:standardLoopCharacteristics />` tag

## Due dates
In oder to specify the due date of an activity, the activity needs to have `due_date` property set.
This property needs to be a valid date in format `Ymd` or `YmdHis` (TS_MW);

It will be processed at the start of the activity (not workflow) and, if reached, will abort (pause) the workflow pending
admin or initiator action.

Only `userTask` tasks can have due dates.

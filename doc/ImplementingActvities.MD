# Connecting activity with certain task in diagram
WIP

## Extension elements of activity
- `<wf:initializer>true</wf:initializer>` Marks the activity as initializing activity, meaning that it will be executed
when user starts the workflow. Diagram must have only one of these activities, and it must be the first activity in the flow (not necessary the first element)


# Registering activity
Register your activity in an attribute

	"Workflows": {
		"LogicObjects": {
			"ActivityRegistry": {
				"my_activity": "factory\\callback::function"
			}
		}
	}

Factory method will be called with the `ITask` instance for which activity is being instantiated

# Implementation
Eventhough `IActivity` interface is available, you are, in most cases, required to
extend `MediaWiki\Extension\Workflows\Activity\Activity` class. So do that, unless you really know what you are doing.

There is only two methods to implement:

- `execute( $data, WorkflowContext $context )`Called when "completeTask" is called from the Workflow. It should contain all of the execution logic
	- `$data` will contain all activity properties (and only properties) updated with data passed to the activity from, eg. a Form
	- `$context` is the normal WF context, that has data of all passed activities
	- Return type:
	  You must return a `ExecutionStatus`, which contains whether activity is complete or not, and payload.
	  Payload data is what will be used to set properties, and update running data in context. Data returned here will only be
	  considered if it exists in properties, in other words, data returned here can only set properties, so no arbitrary data can
	  be returned!
- `probe()` This function will be called to check activity status. It will be called any time the workflow is loaded, AFTER activity's execute method is called. It should be used only in cases when execute does not.
	return STATUS_COMPLETE, to give activity a chance to complete itself. It will be called until STATUS_COMPLETE is returned It is allowed for this function to change its internal state (props and status) but should not contain any logic that could have otherwise went to execute()
  - Return type:
	Can return null if no change has happened and activity remains in execution, or an `ExecutionStatus` with `complete=true` to mark activity as completed.
	Also some data can be returned, that will override running data in context

## Throwing exceptions
An activity can throw Exceptions in case an unexpected conditions are met.
There are two primary types of exceptions activity should throw:
- `WorkflowExecutionException` - throw whenever state can be adjusted and fixed (invalid input data from user ...)
- `NonRecoverableExecutionException` - throw whenever activity (and therefore the workflow) cannot go on. This will abort the workflow
until the admin or initiator reacts to the exception.

# Initializators

Initializators are special kind of activities that are specifically intended for gathering initial data for starting a workflow.

Note that this initial data is not the `contextData` that is passed to `Workflow::start()`, but its an actual
task that is intended to provide a form and gather data from the user when starting a workflow.

This exists to address problem that in order to gather user data before starting the workflow, we would need to have forms that
are external to the workflow itself, and not included in the diagram, which is not acceptable in our use-case.
This provides kind of a nice workaround that still allows us to enter the workflow and get a form for the user without
persisting anything, until after the user has provided the data.

Initializer must be the first activity in the workflow, and there must be only one initializer per workflow.

# How to mark activity as `Initializer`
Put `<wf:initializer>true</wf:initializer>` to `<extensionElements>` of the task definition.

# Start workflow with init data set

When calling `workflows.initiate.startWorkflowOfType` data you pass can be:
- just a key/value object of context items => in this case, this data will be passed to `start` method and no
further initialization will take place
- object with `startData` and `initData` keys. In this case, `startData` will be passed to `start`  as the context,
and `initData` will be passed to the initializer. If `initData` is present, workflow will find the initializer activity,
  and complete it with data passed, and only then persists the workflow, so that the next time its loaded, it will be at the position
  after the initializer. Note that if some automatic elements follow the initializer, Workflow will execute them before persisting,
  or in other words, it will stop at the next user action.

# Due date reached
Once the activity due date is reached, workflow will be aborted. It will stay in the special abort state
that indicates that its aborted due to the due date being reached.
Admin or initiator can then restore the workflow, which would extend the due date for one more day. (one day after restoration, not from the original due date)

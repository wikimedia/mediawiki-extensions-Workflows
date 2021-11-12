# Using contexts in Workflows
There are two types of Contextes in Workflows:
- `DefinitionContext` - context of the process set in definition. This contains some data that are useful for
the further steps in the process, but can be declared statically in the process definition itself.
  Additionally, this data can/must be overriden by data passed when starting the workflow.
  After workflow has been started, this data is immutable!
  Values in this context object are mandatory, if no defaults are provided by the definition itself, data must be
  provided when starting a review, otherwise an exception will be thrown!

- `WorkflowContext` - context of the running workflow. This is an object that contains information about the execution of the process.
Its dynamic, and will be constructed when the process is started. It contains all data outputted by any task, as well as current executing user (actor).

# Usage

`WorkflowContext` object will be made available to all activities, by passing it into the `start` and `completeItem` functions.
Additionally, it can be accessed by calling `$workflow->getContext()`, where `$workflow` is instance of `Workflow`.

It exposes the following functions (other ones are for internal use):
- `getDefinitionContext()` => returns `DefinitionContext` (described above, and usage explained below)
- `getCurrentActor()` => returns `User` object of the user that is performing current action, or `null` if its an automatic action
- `getRunningData( $activityID, $itemKey )` => returns data provided by past (completed) activities (or activity steps).
Can be called with `activityID` to return only data of given activity, with `activityID and itemkey` to provide a particular piece
  of data from given activity, or with no args to get all the data

## Using `DefinitionContext`
It is basically just a key/value map, that allows you to call following functions:
- `getItemKeys()` - to retrieve available keys
- `getitem( $key )` - to retrieve value of given key

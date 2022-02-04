# Usage

Frontend implementaion of Workflows is completely abstracted, and workflows should only
be interacted with using these abstraction object.

## Methods
There are several methods available for retrieving the Workflow objects on the client-side.

***Initializing a workflow***

	// List all available definitions in all repos
	workflows.initiate.listAvailableTypes();

	// Use reponse from the call above to start a workflow
	workflows.initiate.startWorkflowOfType(
		repo, // Respository containing the definition you want to start an instance of
		definitionName, // Name of the definition as returned by previous call
		startData // optional - data passed here will be stored into the Workflow context
	);
	// Instance of workflows.object.Workflow is retured

***Retrieving workflows***

	// Retrieve all workflows ever created on the wiki
	workflows.list.all();

	// Retrieve only active workflows (running)
	// this method accepts a bool argument. If true, only active workflows will be retrieved. If false, only NON-ACTIVE will be retrieved. Default: true
	workflows.list.active( active );

	// Retrieve filtered workflow on a number of factory
	workflows.list.filtered ( onlyActive, filterData );
	// - based on arbitrary context data (all workflows which have context item "page", and value 55)
	workflows.list.filtered( undefined, { context: { page: 55 } } );
	// - based on task data - properties of a task (can only be called on completed tasks)
	workflows.list.filtered( undefined, { taskData: { ID_of_task: { prop: value } } } );

functions above will retrieve array of workflow IDs. After you have the ID, you can retrieve the Workflow object

	workflows.getWorkflows( id );

This returns instance of `workflows.object.Workflow`.

***Handling workflows***

Base of the Workflow interaction is `workflows.object.Workflow` object.
Through it all info on the state of the workflow can be retrieved.

By calling `workflow.getCurrent()` current element in the workflow can be retrieved.
This will be instance of `workflows.object.Element` or `null` if Workflow is not started or is already finished.

Workflow usually pauses on `userTask`s, tasks that require user input.
In such a case, `workflow.getCurrent()` will return instance of `workflows.object.UserInteractiveActivity`.
This represents an activity that allows and requires some data to be provided by the user.

All user input is done in Forms, so only input mechanism task can provide to the user is a Form.
All forms used in Workflows must extend `workflows.object.form.Form`!

To get the form from a task, call `workflow.getCurrent().getForm()` (deferred).
You can then display the form in whichever container is suitable.
Once the form is submitted, call `workflow.getCurrent().complete( dataFromForm)`.
This will post the data to the server, and complete that step. Workflow object will be automatically reloaded and will reflect the latest data.

Also, any already completed task can also be retrieved using `workflow.getTask( taskID )`.
In case it's a user task, retrieving its form will return form in view mode.


***Example***

	var types = await workflows.initiate.listAvailableTypes();

	// Start a workflow
	try {
		var workflow = await workflows.initiate.startWorkflowOfType( 'workflow-file', 'groupVote', {
			page: mw.config.get( 'wgArticleId' )
		} );

		if ( workflow.getCurrent() instanceof workflows.object.UserInteractiveActivity ) {
			workflow.getCurrent().getForm().done( function( formObject ) {
				$( '.container' ).append( formObject.$element );
				formObject.connect( this, {
					submit: function( data ) {
						// Complete a task
						workflow.getCurrent().complete( data );
					}
				} );
			} );
		}
	} catch( e ) {
		console.log( e );
	}

## Triggers API

All trigger API endopoints are located in object `workflows.triggers`;

- `getAll()` retrieve all trigger data
- `get( key )` Retrieve only details of one trigger
- `persist(triggers)` Save triggers. This data will replace all trigger data previously saved
- `delete(key)` Delete particular trigger

Example on how to read, and persist modified trigger data

	workflows.triggers.getAll().done( function( triggers ) {
		// Modify/add/delete triggers
		triggers['in-main']['active'] = true;
		workflows.triggers.persist( triggers );
	} );

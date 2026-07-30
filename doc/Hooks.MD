# Hooks fired by this extension

## WorkflowsStateChanged

Fired whenever a workflow moves between the states defined in `Workflow`
(`not_started`, `running`, `finished`, `aborted`), that is on the transitions caused by
`WorkflowStarted`, `WorkflowEnded`, `WorkflowAborted` and `WorkflowUnAborted`. Events that
leave the state alone - a task starting or being completed, for instance - do not fire it.

```php
public function onWorkflowsStateChanged( WorkflowStateModel $model, string $stateBefore ): void;
```

- `$model` is the state model **after** the transition. `$model->getState()` is the new state,
  `$model->getWorkflowId()` identifies the workflow, and `$model->getPayload()['context']`
  holds the context the workflow was started with (`pageId` for page-bound workflows).
- `$stateBefore` is the state the workflow was in before the event was handled.

The hook is not fired while replaying events (`maintenance/rebuildWorkflowStateData.php`),
since nothing actually changes then - the stored state is merely being rebuilt.

Use this to keep data derived from workflow state up to date. Workflows start and end without
anyone editing the page, so consumers that cache or project such data have no other point to
react to. Note that `WorkflowsUpdateTask` is **not** a substitute: it only fires for user
interactive activities, and says nothing about the workflow's state as a whole.

## WorkflowsUpdateTask

```php
public function onWorkflowsUpdateTask( ITaskDescriptor $descriptor, User $user, bool $isCompleted ): void;
```

Fired for each target user when a user interactive activity is started or completed, so that
Extension:UnifiedTaskOverview can add or remove the corresponding task.

## WorkflowFileDefinitionRepositoryLoad

```php
public function onWorkflowFileDefinitionRepositoryLoad( WorkflowFileDefinitionRepository $repository ): void;
```

Fired after the file definition repository has registered the definitions from the
`WorkflowsDefinitionRepositories` attribute, to let extensions register further definitions.

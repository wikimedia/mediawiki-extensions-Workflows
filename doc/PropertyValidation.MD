# Run-time property validation

To ensure properties are valid beyond client-side validation, we have a machanism of `PropertyValidator`s.
Validation will be executed when the task completion method is called (invalid defaults are not a problem)
For array values, each element will be validated.

## Usage

### Registering validators
Use `WorkflowsPropertyValidators` attribute registry to register validators. Format is `key` => `ObjectFactory spec`

### Assigning validation in XML
Use `validation` attribute on the property. `<bpmn:property name="myProperty" validation="existsing-user"/>`
Multiple validators can also be specified, as a comma-separated list => `validation="existing-user,can-execute-task""`

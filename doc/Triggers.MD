# Trigger management

Workflows can be triggered on different conditions, by defining a `trigger`. Those are defined on a wiki page
( default: `MediaWiki:WorkflowTriggers.json` )

## Format
Triggers are kept in JSON format, in an object, where key is the unique name of the trigger, and value is the definition of the trigger.

Definition keys:
- `type` (required) => type of the trigger. One of the keys in `WorkflowsTriggerTypes` registry
- `active` (optional, default true) => whether the trigger is active. If false, trigger will not be evaluated
- `definition` (required) => Workflow definition key to start when trigger is triggered. Not applicable for triggers
of type `manual`, as user manually picks the definition for those
- `repository`  (required) => Workflow repository key, where the definition set above is available. Tied to `definition`
field. If one is specified, other must be too
- `initData` (optional) => Data for the `Initializer` of the workflow
- `contextData` (optional) => Data to be passed to the workflow as the context. For `PageRelatedTrigger`s context
data will be set to the correct title automatically
- `rules` (optional, object) => This object holds a set of rules to check if the trigger is applicable for the situation:
	- `include` - list of rules, if any of the rules is applicable, trigger will be triggered
	- `exclude` - list of rules, if any of the rules is applicable, trigger will not be triggered, even if rules
	from `include` match

		Individual rules can be:
		- `namespace` =>   namespace ID or array of namespaces IDs. Pages in these namespace will match this rule
		- `category` => category names (without Namespace prefix).
		- `editType` => `minor` / `major`. Type of edit to match. If set to `major`, only major edit will match this rule
		- `pages` => plainlist of pipe (|) separated page titles. Supports wikitext parsing, can be calculated field (like `#ask` query)
- `days` (optional) => only applicable for time-based triggers. Number of days since a particular action until the WF is triggered
- `definitions` (optional) => only applicable for `manual` triggers. Array of objects containing `definition` and `definition-repo`.
Only workflows from this list will be listed in the list when starting the workflow manually, for all pages that match trigger's rules

 ## Example
	{
    "start-approval-on-edit": {
        "type": "edit",
        "definition": "user-approval",
        "repository": "workflow-file",
        "initData": {
            "username": "Dsavuljesku",
            "instructions": "Do this thing!"
        },
        "rules": {
			// Includes all pages in NS_MAIN and NS_HELP,
			// except if those pages are also in category "Test" and if edit was a minor
            "include": {
                "namespace": [
                    0,
                    12
                ]
            },
            "exclude": {
                "category": [
                    "Test"
                ],
                "editType": "minor"
            }
        }
    },
	// Trigger `user-approval` from `workflow-file` repo 3 days after last major edit
    "since-major": {
        "type": "since-last-major",
        "definition": "user-approval",
        "repository": "workflow-file",
        "days": 3,
        "initData": {
            "username": "Dsavuljesku",
            "instructions": "Do this thing!"
        }
    },
	// Ina NS_MAIN and NS_HELP allow only manual starting of `user-approval` and `user-feedback` workflows
    "in-main": {
        "type": "manual",
        "definitions": [
            {
                "repo": "workflow-file",
                "definition": "user-approval"
            },
            {
                "repo": "workflow-file",
                "definition": "user-feedback"
            }
        ],
        "rules": {
            "include": {
                "namespace": [
                    0,
                    12
                ]
            }
        }
    }
}




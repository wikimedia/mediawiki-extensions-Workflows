# Quick start

DL;DR: Quick overview of steps needed to create a custom extension for Workflows editor and be able to inspect
your custom activity

- Create a RL module that will load your custom files
  - Register custom activity [using a declarative method](#using-descriptors)
  - Create a stylesheet to style your custom activity button
  - Create a custom inspector
	- Create a class that extends `workflows.editor.inspector.ActivityInspector`
	- Override `getItems` method to return your custom form fields
	- Override `updateModel` method to update BPMN model
	- Register your inspector using `window.workflows.editor.inspector.registry.register( 'my_inspector', MyInspectorClass )`
- Register your RL module in `WorkflowsEditorPluginModules` attribute
- Need a custom bpmn element (eg. `wf:MyCrazyElement`)? see [amending schema](#amending-schema)

# Custom elements

Custom elements in this context mean in majority of cases, custom activities.
In order to allow users to create diagrams using Workflows activities, we need to register those custom activities,
otherwise user will be able to only create generic `bpmn:Task`/`bpmn:UserTask` activities and need to manually change
properties to match Workflows activities. We do not want that, so, in the "palette" (menu of available elements on the left),
we offer custom activities.

## Registering custom elements

Use `window.workflows.editor.element.registry` registry to register custom elements. There are two ways you can register elements:

### Using objects

All elements in Workflows editor context are children of `workflows.editor.element.CustomElement`. You can create your own
class that will inherit from this class, in case you need some complex processing. Also, this is the mandatory way
if you are registering element that is NOT a Workflows activity (e.g. special gateway model, or even a new element completely )

If you are using this approach, register your element like this:

```javascript
window.workflows.editor.element.registry.register( 'myElement', MyElementClass );
```

### Using descriptors

In the great majority of cases, you will be registering Workflows activities. In that case, you can use descriptors, to spare
implementation of your own classes. Descriptors are simple objects, that contain all the information needed to create
an activity element. Objects created using this way will internally be of type `workflows.editor.element.WorkflowActivityElement`.

```javascript
window.workflows.editor.element.registry.register( 'my_activity', {
	isUserActivity: true|false, // create bpmn:UserTask or bpmn:Task
	label: 'My fancy activity', // to be shown in the palette
	class: 'custom_class', // For the palette button
	defaultData: {
		extensionElements: {
			'wf:MyItem': 'my value',
		},
		properties: {
			my_property1: 'my value',
			my_property2: ''
		}
	}
} );
```

### Loading stylesheets
As you saw above, class for different activities needs to be specified. These classes will be added to the buttons
in the palette.

Load stylesheets in RL modules that load the rest of the custom files for your extension.

In stylesheets, access the button by `.activity-icon.your_class` selector.
Default implementation assigns icons in `:before` pseudo-element, so you can use `content` property to assign icon.

# Custom activity inspectors

Once activities are added to the diagram (or existing diagram is loaded), we need a way to modify data of the elements.
Here is where inspectors come into play. Inspectors are dialogs that allow to modify data of the elements.

## Editing with inspectors - workflow

- *Opening inspector*
- `InspectorDialog` calls `Inspector::getForm`
  - `Inspector::getDefaultItems` - get default fields that will be displayed at the top of the form
  - `Inspector::getItems` * - get form fields
  - `Inspector::convertDataForForm`- convert data retrieved from BPMN model to format that will be passed to the form
  	- `Inspector::getElementData` - get data retrieved from BPMN model
      - `Inspector::getElementName`
      - `Inspector::getElementId`
      - `Inspector::getExtensionElements`
        - `Inspector::convertExtensionElements` - convert BPMN extension elements to raw data
      - `Inspector::getProperties`
       - `Inspector::getPropertyData` * - get value/attributes of a property
- *Form displayed in dialog*
- *Form is submitted*
- `Inspector::updateModel` - passed data coming from the form and updates BPMN businessObject model
  - `Inspector::updateElementData` - update basic element attributes
  - `Inspector::updateProperties` - update properties
  - `Inspector::updateExtensionElements` - update extension elements

## Custom property fields
To return your custom Forms form field for a property, override `getPropertyField( propertyName )` method.
Important: Name of that field must be `properties.{propertyName}`, or you must adapt `convertDataForForm` method
to modify data being passed to the form

## Update data model
Once user submits the inspector, underlying BPMN model will be updated. In general, your inspector is allowed to update:
- name
- properties
- extension elements

For model to be updated properly, your form must return values in this format (note different formats for `extensionElements`)

```json
{
	"name": "activityName",
	"properties": {
		"propertyName": "propertyValue"
	},
	"extensionElements": {
		"wf:MyItem": "my value",
		"wf:MyNestedItem": {
			"wf:Nested1": "value",
			"wf:Nested2": "value2"
		},
		"wf:Dummy": {
			"attributes": {
				"name": "dummyName"
			},
			"value": "dummy value"
		},
		"wf:Dummy2": {
			"attributes": {
				"name": "dummyName2"
			},
			"value": [ "val1", "val2" ]
		}
	}
}
```

That means, that your field names will be something like `extensionElements.wf:MyNestedItem.wf:Nested1`.
This will translate into:

```xml
<bpmn:userTask id="activityId" name="activityName">
	<bpmn:extensionElements>
		<wf:type>activity_type</wf:type>
		<wf:myItem>my value</wf:myItem>
		<wf:myNestedItem>
			<wf:nested1>value</wf:nested1>
			<wf:nested2>value2</wf:nested2>
		</wf:myNestedItem>
		<wf:dummy name="dummyName">dummy value</wf:dummy>
		<wf:dummy2 name="dummyName2">va1</wf:dummy2>
		<wf:dummy2 name="dummyName2">val2</wf:dummy2>

	</bpmn:extensionElements>
	<bpmn:property name="propertyName">propertyValue</bpmn:property>
</bpmn:userTask>
```

Note that `wf:type` is added automatically, inspector is already bound to a type, so it cannot be changed.

Important: `extensionElements` can contain arbitrary XML elements, but they must be prefixed with `wf:` namespace.
(for all Workflows related BPMNs). Also, if its an element that does not exist yet, you need to amend the schema.
See [amending schema](#amending-schema)

# Amending schema

Warning: Amending schema is something that is very, very rarely needed. If you are not sure, you probably don't need it.

There are two schemas that need to be updated in case of new types:
- `extensions/Workflows/data/wfSchema.xsd` - schema used for runtime
- `extensions/Workflows/editor/schema.js` - schema used for editor

Edit both schemas directly in Workflows extension, extension by external extensions not supported.

# Loading RL Modules

All modules you create to extend Workflows should be loaded by specifying them in `WorkflowsEditorPluginModules` attribute

```json
{
	"attributes": {
		"Workflows": {
			"EditorPluginModules": [
				"my_module"
			]
		}
	}
}
```

## Dependencies

Your RL modules will likely require dependencies to `"ext.workflows.editor.customElements.bootstrap` and/or
`ext.workflows.editor.inspector.bootstrap`


# Important notes

- In the code, wherever elements are mentioned, they are always UCFirst, eg. `bpmn:Process`, `bpmn:UserTask`, `wf:Type`...
It will only work like that, so make sure you use this format when creating/accessing elements

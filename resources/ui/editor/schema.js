workflows.editor.Schema = {
	wf: {
		// See data/schema.json for the BPMN 2.0 schema
		name: "Workflows",
		uri: "http://hallowelt.com/schema/bpmn/wf",
		prefix: "wf",
		"xml": {
			"tagAlias": "lowerCase"
		},
		types: [
			{
				name: "Context",
				superClass: [ "Element" ],
				properties: [
					{
						name: "items",
						isMany: true,
						type: "ContextItem"
					}
				]
			},
			{
				name: "ContextItem",
				properties: [
					{
						name: "name",
						isAttr: true,
						type: "String"
					}
				]
			},
			{
				name: "Type",
				superClass: [ "Element" ],
				properties: [
					{
						name: "text",
						isBody: true,
						type: "String"
					}
				]
			},
			{
				name: "Initializer",
				superClass: [ "Element" ],
				properties: [
					{
						name: "text",
						isBody: true,
						type: "String"
					}
				]
			},
			{
				name: "FormModule",
				superClass: [ "Element" ],
				properties: [
					{
						name: "formModule",
						isMany: false,
						type: "Module"
					},
					{
						name: "formClass",
						isMany: false,
						type: "Class"
					}
				]
			},
			{
				// wf:formModule.wf:module
				name: "Module",
				properties: [
					{
						name: "text",
						isBody: true,
						type: "String"
					}
				]
			},
			{
				// wf:formModule.wf:class
				name: "Class",
				properties: [
					{
						name: "text",
						isBody: true,
						type: "String"
					}
				]
			},
			{
				// wf:form
				name: "Form",
				superClass: [ "Element" ],
				properties: [
					{
						name: "text",
						isBody: true,
						type: "String"
					}
				]
			}
		]
	}
};

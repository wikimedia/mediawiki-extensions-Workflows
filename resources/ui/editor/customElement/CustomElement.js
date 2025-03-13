workflows.editor.element.CustomElement = function ( type ) {
	this.type = type;
};

OO.initClass( workflows.editor.element.CustomElement );

workflows.editor.element.CustomElement.prototype.getType = function () {
	return this.type;
};

workflows.editor.element.CustomElement.prototype.getGroup = function () {
	// STUB - can be `tools`, `event`, `activity`
	return '';
};

workflows.editor.element.CustomElement.prototype.getClass = function () {
	// STUB
	return '';
};

workflows.editor.element.CustomElement.prototype.getLabel = function () {
	return this.type;
};

workflows.editor.element.CustomElement.prototype.getDefaultData = function () {
	/* Example:
	{
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
	 */
	return {};
};

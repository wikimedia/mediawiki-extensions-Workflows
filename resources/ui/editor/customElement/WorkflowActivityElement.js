workflows.editor.element.WorkflowActivityElement = function ( name, cfg ) {
	cfg = cfg || {};
	workflows.editor.element.WorkflowActivityElement.parent.call(
		this, ( cfg.isUserActivity || false ) ? 'bpmn:UserTask' : 'bpmn:Task'
	);
	this.class = 'bpmn-icon-task activity-icon ' + cfg.class || '';
	this.defaultData = cfg.defaultData || {};
	this.name = name;
	this.label = cfg.label || name;
};

OO.inheritClass( workflows.editor.element.WorkflowActivityElement, workflows.editor.element.CustomElement );

workflows.editor.element.WorkflowActivityElement.prototype.getGroup = function () {
	return 'activity';
};

workflows.editor.element.WorkflowActivityElement.prototype.getClass = function () {
	return this.class;
};

workflows.editor.element.WorkflowActivityElement.prototype.getLabel = function () {
	return this.label;
};

workflows.editor.element.WorkflowActivityElement.prototype.getDefaultData = function () {
	return this.defaultData;
};

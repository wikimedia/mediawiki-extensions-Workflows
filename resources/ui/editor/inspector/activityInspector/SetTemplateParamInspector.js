workflows.editor.inspector.SetTemplateParamInspector = function( element, dialog ) {
	workflows.editor.inspector.UserVoteInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.SetTemplateParamInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.SetTemplateParamInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-title' ).text();
};

workflows.editor.inspector.SetTemplateParamInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'user_picker',
			name: 'properties.user',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-user' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-user-help' ).text(),
		},
		{
			type: 'title',
			name: 'properties.title',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-title' ).text(),
			required: true
		},
		{
			type: 'number',
			name: 'properties.template-index',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template_index' ).text(),
			required: true,
			min: 0
		},
		{
			type: 'number',
			name: 'properties.template-param',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template_param' ).text(),
			required: true,
			min: 0
		},
		{
			type: 'text',
			name: 'properties.value',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-value' ).text()
		},
		{
			type: 'checkbox',
			name: 'properties.minor',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-minor' ).text()
		},
		{
			type: 'text',
			name: 'properties.comment',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-comment' ).text()
		}
	];
};

workflows.editor.inspector.Registry.register( 'set_template_param', workflows.editor.inspector.SetTemplateParamInspector );

workflows.editor.inspector.EditRequestInspector = function( element, dialog ) {
	workflows.editor.inspector.EditRequestInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.EditRequestInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.EditRequestInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-edit-request-title' ).text();
};

workflows.editor.inspector.EditRequestInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'user_picker',
			name: 'properties.assigned_user',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-assigned_user' ).text(),
			required: true
		},
		{
			type: 'text',
			name: 'properties.instructions',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-instructions' ).text()
		},
		{
			type: 'date',
			name: 'properties.due_date',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-due_date' ).text(),
			widget_$overlay: this.dialog.$overlay,
			required: true
		}
	];
};

workflows.editor.inspector.Registry.register( 'edit_request', workflows.editor.inspector.EditRequestInspector );

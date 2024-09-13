workflows.editor.inspector.UserFeedbackInspector = function( element, dialog ) {
	workflows.editor.inspector.UserFeedbackInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.UserFeedbackInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.UserFeedbackInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-user-feedback-title' ).text();
};

workflows.editor.inspector.UserFeedbackInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'text',
			name: 'properties.assigned_user',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-assigned_user' ).text()
		},
		{
			type: 'text',
			name: 'properties.instructions',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-instructions' ).text()
		},
		{
			type: 'text',
			name: 'properties.due_date',
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-due_date' ).text(),
		},
		{
			type: 'text',
			name: 'properties.comment',
			hidden: true
		}
	];
};

workflows.editor.inspector.Registry.register( 'user_feedback', workflows.editor.inspector.UserFeedbackInspector );

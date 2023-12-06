workflows.editor.inspector.EditPageInspector = function( element, dialog ) {
	workflows.editor.inspector.EditPageInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.EditPageInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.EditPageInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-edit-page-title' ).text();
};

workflows.editor.inspector.EditPageInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'title',
			name: 'properties.title',
			label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-title' ).text(),
			required: true
		},
		{
			type: 'user_picker',
			name: 'properties.user',
			label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-user' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-user-help' ).text(),
		},
		{
			type: 'textarea',
			name: 'properties.content',
			label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-content' ).text()
		},
		{
			type: 'checkbox',
			name: 'properties.minor',
			label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-minor' ).text()
		},
		{
			type: 'dropdown',
			name: 'properties.mode',
			label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-mode' ).text(),
			options: [
				{ data: 'prepend', label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-mode-prepend' ).text() },
				{ data: 'append', label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-mode-append' ).text() },
				{ data: 'replace', label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-property-mode-replace' ).text() }
			],
		}
	];
};

workflows.editor.inspector.Registry.register( 'edit_page', workflows.editor.inspector.EditPageInspector );

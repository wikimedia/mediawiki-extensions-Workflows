workflows.editor.inspector.SendMailInspector = function ( element, dialog ) {
	workflows.editor.inspector.SendMailInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.SendMailInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.SendMailInspector.prototype.getDialogTitle = function () {
	return mw.message( 'workflows-ui-editor-inspector-activity-send-mail-title' ).text();
};

workflows.editor.inspector.SendMailInspector.prototype.getItems = function () {
	return [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'text',
			name: 'properties.recipient',
			label: mw.message( 'workflows-ui-editor-inspector-activity-send-mail-property-recipient' ).text(),
			required: true
		},
		{
			type: 'text',
			name: 'properties.subject',
			label: mw.message( 'workflows-ui-editor-inspector-activity-send-mail-property-subject' ).text(),
			required: true
		},
		{
			type: 'textarea',
			name: 'properties.body',
			label: mw.message( 'workflows-ui-editor-inspector-activity-send-mail-property-body' ).text()
		}
	];
};

workflows.editor.inspector.Registry.register( 'send_mail', workflows.editor.inspector.SendMailInspector );

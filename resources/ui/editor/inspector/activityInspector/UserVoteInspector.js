workflows.editor.inspector.UserVoteInspector = function ( element, dialog ) {
	workflows.editor.inspector.UserVoteInspector.parent.call( this, element, dialog );
	workflows.mixin.SignedActivity.call( this, {} );
};

OO.inheritClass( workflows.editor.inspector.UserVoteInspector, workflows.editor.inspector.ActivityInspector );
OO.mixinClass( workflows.editor.inspector.UserVoteInspector, workflows.mixin.SignedActivity );

workflows.editor.inspector.UserVoteInspector.prototype.getDialogTitle = function () {
	return mw.message( 'workflows-ui-editor-inspector-activity-user-vote-title' ).text();
};

workflows.editor.inspector.UserVoteInspector.prototype.getItems = function () {
	return [
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
			label: mw.message( 'workflows-ui-editor-inspector-activity-user-activity-property-due_date' ).text()
		},
		{
			type: 'text',
			name: 'properties.action',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.vote',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.delegate_to',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.delegate_comment',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.comment',
			hidden: true
		}
	].concat( this.getSignatureEditorFormFields() );
};

workflows.editor.inspector.Registry.register( 'user_vote', workflows.editor.inspector.UserVoteInspector );

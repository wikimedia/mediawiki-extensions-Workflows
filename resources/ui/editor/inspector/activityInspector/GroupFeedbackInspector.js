workflows.editor.inspector.GroupFeedbackInspector = function( element, dialog ) {
	workflows.editor.inspector.GroupFeedbackInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.GroupFeedbackInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.GroupFeedbackInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-title' ).text();
};

workflows.editor.inspector.GroupFeedbackInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'dropdown',
			name: 'assignmentType',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-assignment-type' ).text(),
			options: [
				{ data: 'user-list', label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-assignment-type-user-list-assignment' ).text() },
				{ data: 'group', label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-assignment-type-group-assignment' ).text() }
			],
			listeners: {
				change: function( assignmentType ) {
					this.getItem( 'properties.assigned_group' ).setRequired( assignmentType === 'group' );
					this.getItem( 'properties.assigned_users' ).setRequired( assignmentType === 'user-list' );
					if ( assignmentType === 'user-list' ) {
						this.hideItem( 'properties.assigned_group' );
						this.showItem( 'properties.assigned_users' );
					} else {
						this.showItem( 'properties.assigned_group' );
						this.hideItem( 'properties.assigned_users' );
					}
				}
			}
		},
		{
			type: 'user_multiselect',
			name: 'properties.assigned_users',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-assigned_users' ).text(),
			required: true
		},
		{
			type: 'group_picker',
			name: 'properties.assigned_group',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-assigned_group' ).text(),
			widget_$overlay: this.dialog.$overlay,
			hidden: true
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
		},
		{
			type: 'dropdown',
			name: 'properties.threshold_unit',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-threshold_unit' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-threshold_unit-help' ).text(),
			options: [
				{ data: 'user', label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-threshold_unit-user' ).text() },
				{ data: 'percent', label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-threshold_unit-percent' ).text() }
			],
			listeners: {
				change: function( thresholdUnit ) {
					var item = this.getItem( 'properties.threshold_value' );
					if ( thresholdUnit === 'user' ) {
						item.max = 1000;
						item.setStep( 1 );
					} else {
						item.max = 100;
						item.setStep( 5 );
					}
				}
			}
		},
		{
			type: 'number',
			name: 'properties.threshold_value',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-property-threshold_value' ).text(),
			required: true,
			min: 1,
			max: 1000
		},
		{
			type: 'text',
			name: 'properties.users_feedbacks',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.comment',
			hidden: true
		}
	];
};

workflows.editor.inspector.GroupFeedbackInspector.prototype.convertDataForForm = function( data ) {
	data.properties = this.getPropertiesKeyValue();

	if (
		data.properties.hasOwnProperty( 'assigned_users' ) &&
		!Array.isArray( data.properties.assigned_users )
	) {
		data.properties.assigned_users = data.properties.assigned_users.split( ',' );
	}

	return data;
};

workflows.editor.inspector.GroupFeedbackInspector.prototype.preprocessDataForModelUpdate = function( data ) {
	if ( data.assignmentType === 'user-list' ) {
		delete ( data['properties']['assigned_group'] );
	}
	if ( data.assignmentType === 'group' ) {
		delete ( data['properties']['assigned_users'] );
	}
	return workflows.editor.inspector.GroupFeedbackInspector.parent.prototype.preprocessDataForModelUpdate.call( this, data );
};

workflows.editor.inspector.Registry.register( 'group_feedback', workflows.editor.inspector.GroupFeedbackInspector );
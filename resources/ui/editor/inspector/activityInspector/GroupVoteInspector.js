workflows.editor.inspector.GroupVoteInspector = function( element, dialog ) {
	workflows.editor.inspector.GroupVoteInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.GroupVoteInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.GroupVoteInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-group-vote-title' ).text();
};

workflows.editor.inspector.GroupVoteInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'dropdown',
			name: 'assignmentType',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-assignment-type' ).text(),
			options: [
				{ data: 'user-list', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-assignment-type-user-list-assignment' ).text() },
				{ data: 'group', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-assignment-type-group-assignment' ).text() }
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
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-assigned_users' ).text(),
			widget_$overlay: this.dialog.$overlay
		},
		{
			type: 'group_picker',
			name: 'properties.assigned_group',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-assigned_group' ).text(),
			hidden: true,
			widget_$overlay: this.dialog.$overlay
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
			required: true
		},
		{
			type: 'dropdown',
			name: 'properties.threshold_yes_unit',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_yes_unit' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_yes_unit-help' ).text(),
			options: [
				{ data: 'user', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_unit-user' ).text() },
				{ data: 'percent', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_unit-percent' ).text() }
			],
			listeners: {
				change: function( thresholdUnit ) {
					var item = this.getItem( 'properties.threshold_yes_value' );
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
			name: 'properties.threshold_yes_value',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_yes_value' ).text(),
			required: true,
			min: 1,
			max: 1000
		},
		{
			type: 'dropdown',
			name: 'properties.threshold_no_unit',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_no_unit' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_no_unit-help' ).text(),
			options: [
				{ data: 'user', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_unit-user' ).text() },
				{ data: 'percent', label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_unit-percent' ).text() }
			],
			listeners: {
				change: function( thresholdUnit ) {
					var item = this.getItem( 'properties.threshold_no_value' );
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
			name: 'properties.threshold_no_value',
			label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-property-threshold_no_value' ).text(),
			required: true,
			min: 1,
			max: 1000
		},
		{
			type: 'text',
			name: 'properties.users_voted',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.vote',
			hidden: true
		},
		{
			type: 'text',
			name: 'properties.comment',
			hidden: true
		}
	];
};

workflows.editor.inspector.GroupVoteInspector.prototype.convertDataForForm = function( data ) {
	data.properties = this.getPropertiesKeyValue();

	if (
		data.properties.hasOwnProperty( 'assigned_users' ) &&
		!Array.isArray( data.properties.assigned_users )
	) {
		data.properties.assigned_users = data.properties.assigned_users.split( ',' );
	}

	// First element "" could appear if we try to split empty string by ","
	if ( data.properties.assigned_users[0] === '' ) {
		data.properties.assigned_users.shift();
	}

	return data;
};

workflows.editor.inspector.GroupVoteInspector.prototype.preprocessDataForModelUpdate = function( data ) {
	if ( data.assignmentType === 'user-list' ) {
		delete ( data['properties']['assigned_group'] );
	}
	if ( data.assignmentType === 'group' ) {
		delete ( data['properties']['assigned_users'] );
	}
	return workflows.editor.inspector.GroupVoteInspector.parent.prototype.preprocessDataForModelUpdate.call( this, data );
};

workflows.editor.inspector.Registry.register( 'group_vote', workflows.editor.inspector.GroupVoteInspector );

workflows.editor.inspector.CustomFormInspector = function( element, dialog ) {
	workflows.editor.inspector.CustomFormInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.CustomFormInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.CustomFormInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-custom-form-title' ).text();
};

workflows.editor.inspector.CustomFormInspector.prototype.getItems = function() {
	return  [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-to-use' ).text()
		},
		{
			type: 'dropdown',
			name: 'formType',
			label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-type' ).text(),
			options: [
				{ data: 'on-wiki', label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-type-on-wiki' ).text() },
				{ data: 'backend', label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-type-backend' ).text() }
			],
			listeners: {
				change: function( formType ) {
					this.getItem( 'extensionElements.wf:FormModule.wf:Module' ).setRequired( formType === 'backend' );
					this.getItem( 'extensionElements.wf:FormModule.wf:Class' ).setRequired( formType === 'backend' );
					this.getItem( 'extensionElements.wf:Form' ).setRequired( formType === 'on-wiki' );
					if ( formType === 'on-wiki' ) {
						this.hideItem( 'extensionElements.wf:FormModule.wf:Module' );
						this.hideItem( 'extensionElements.wf:FormModule.wf:Class' );
						this.showItem( 'extensionElements.wf:Form' );
					} else {
						this.showItem( 'extensionElements.wf:FormModule.wf:Module' );
						this.showItem( 'extensionElements.wf:FormModule.wf:Class' );
						this.hideItem( 'extensionElements.wf:Form' );
					}
				}
			}
		},
		{
			type: 'title',
			label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-title' ).text(),
			name: 'extensionElements.wf:Form',
			widget_$overlay: this.dialog.$overlay,
			validate: function( title ) {
				var form = this;
				if ( typeof this.getForm === 'function' ) {
					form = this.getForm();
				}
				if ( !( form instanceof mw.ext.forms.widget.Form ) ) {
					// No form context, we can return true here, as main validation on submit will kick in
					return true;
				}
				if ( form.getItem( 'formType' ).getValue() !== 'on-wiki' ) {
					return true;
				}
				// Check if title ends with .form
				return title.match( /\.form$/ );
			}
		},
		{
			type: 'text',
			name: 'extensionElements.wf:FormModule.wf:Module',
			label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-module' ).text(),
			hidden: true,
		},
		{
			type: 'text',
			name: 'extensionElements.wf:FormModule.wf:Class',
			label: mw.message( 'workflows-ui-editor-inspector-activity-custom-form-form-class' ).text(),
			hidden: true,
		},
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-activity-section-initializer' ).text()
		},
		{
			type: 'checkbox',
			name: 'extensionElements.wf:Initializer',
			label: mw.message( 'workflows-ui-editor-inspector-activity-initializer' ).text()
		}
	].concat( workflows.editor.inspector.CustomFormInspector.parent.prototype.getItems.call( this ) );
};

workflows.editor.inspector.CustomFormInspector.prototype.getPropertyField = function( propertyName ) {
	if ( propertyName === 'username' ) {
		return {
			type: 'user_picker',
			name: 'properties.' + propertyName,
			label: this.getPropertyLabel( propertyName ),
			widget_$overlay: this.dialog.$overlay
		};
	}
	return workflows.editor.inspector.CustomFormInspector.parent.prototype.getPropertyField.call( this, propertyName );
};

workflows.editor.inspector.CustomFormInspector.prototype.convertDataForForm = function( data ) {
	data = workflows.editor.inspector.CustomFormInspector.parent.prototype.convertDataForForm.call( this, data );
	if ( data.extensionElements.hasOwnProperty( 'wf:Form' ) ) {
		data.formType = 'on-wiki';
		if ( data.extensionElements.hasOwnProperty( 'wf:Form' ) ) {
			data.extensionElements['wf:Form'] = data.extensionElements['wf:Form'] + '.form';
		}
	} else {
		data.formType = 'backend';
	}
	return data;
};

workflows.editor.inspector.CustomFormInspector.prototype.preprocessDataForModelUpdate = function( data ) {
	if ( data.formType === 'on-wiki' ) {
		var formName = data['extensionElements']['wf:Form'];
		// Strip .form from the form name
		formName = formName.replace( /\.form$/, '' );
		data['extensionElements']['wf:Form'] = formName;
		delete ( data['extensionElements']['wf:FormModule'] );
	}
	if ( data.formType === 'backend' ) {
		delete ( data['extensionElements']['wf:Form'] );
	}
	return workflows.editor.inspector.CustomFormInspector.parent.prototype.preprocessDataForModelUpdate.call( this, data );
};

workflows.editor.inspector.Registry.register( 'custom_form', workflows.editor.inspector.CustomFormInspector );

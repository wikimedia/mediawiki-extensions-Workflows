workflows.editor.inspector.Inspector = function( element, dialog ) {
	this.dialog = dialog;
	this.element = element;

	this.initialId = '';

	this.elementData = {
		name: this.getElementName(),
		id: this.getElementId(),
		extensionElements: this.getExtensionElements(),
		properties: this.getProperties()
	};
};

OO.initClass( workflows.editor.inspector.Inspector );

workflows.editor.inspector.Inspector.prototype.getDialogTitle = function() {

};

workflows.editor.inspector.Inspector.prototype.getForm = function() {
	return new mw.ext.forms.standalone.Form( {
		definition: {
			items: this.getDefaultItems().concat( this.getItems() ),
			buttons: []
		},
		errorReporting: false,
		data: this.convertDataForForm( $.extend( {}, this.getElementData() ) )
	} );
};

workflows.editor.inspector.Inspector.prototype.getDefaultItems = function() {
	// Need that to get access to inspector object inside of validation function
	var inspectorObj = this;

	return [
		{
			type: 'text',
			name: 'name',
			label: mw.message( 'workflows-ui-editor-inspector-name' ).text(),
			listeners: {
				change: function( val ) {
					if ( val !== '' ) {
						var id = val.replaceAll( /\W/g, '' );

						// ID equals to the one which element currently has, nothing to do
						if ( id === inspectorObj.initialId ) {
							return;
						}

						var elements = workflows.editor.modeler.get('elementRegistry')._elements;

						// If expected element ID is "Element" and there is already other element with such ID
						// Then change it to "Element_1"
						if ( elements.hasOwnProperty( id ) ) {
							var i = 1;

							// But it could be that there is already "Element_1" existing
							// So increment the counter after we'll get yet not existing ID
							while( true ) {
								var possibleId = id + '_' + i;

								if ( !elements.hasOwnProperty( possibleId ) ) {
									id = possibleId;

									break;
								}

								i++;
							}
						}

						this.items.inputs.id.setValue( id );
					}
				}
			}
		},
		{
			type: 'text',
			name: 'id',
			label: mw.message( 'workflows-ui-editor-inspector-id' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-id-help' ).text(),
			widget_disabled: true
		}
	];
};

workflows.editor.inspector.Inspector.prototype.getItems = function() {
	return [];
};

workflows.editor.inspector.Inspector.prototype.getElementData = function() {
	return this.elementData;
};

workflows.editor.inspector.Inspector.prototype.convertDataForForm = function( data ) {
	if ( this.initialId === '' && data.id !== '' ) {
		this.initialId = data.id;
	}

	data.properties = this.getPropertiesKeyValue();
	return data;
};


workflows.editor.inspector.Inspector.prototype.getElementName = function() {
	return this.element.businessObject.get( 'name' ) || '';
};

workflows.editor.inspector.Inspector.prototype.getElementId = function() {
	return this.element.businessObject.get( 'id' ) || '';
};

workflows.editor.inspector.Inspector.prototype.getExtensionElements = function() {
	var extensionElements = workflows.editor.util.extensionElements.getAll( this.element );
	if ( extensionElements ) {
		return this.convertExtensionElements( extensionElements );
	}
	return [];
};

workflows.editor.inspector.Inspector.prototype.convertExtensionElements = function( extensionElements ) {
	// STUB => OVERRIDE
	return [];
};

workflows.editor.inspector.Inspector.prototype.getProperties = function() {
	var formatted = {};
	var properties = this.element.businessObject.get( 'properties' ) || [];
	window.el = this.element;
	for ( var i = 0; i < properties.length; i++ ) {
		formatted[properties[i].name] = this.getPropertyData( properties[i] );
	}

	return formatted;
};

workflows.editor.inspector.Inspector.prototype.getPropertyData = function( property ) {
	var data = {
		value: ''
	};
	var attribs = property.$attrs || {};
	if ( attribs.hasOwnProperty( 'default' ) ) {
		data.value = attribs.default;
	}
	if ( property.get( 'text' ) ) {
		data.value = property.get( 'text' );
	}
	data.attributes = attribs;
	return data;
};

workflows.editor.inspector.Inspector.prototype.getPropertiesKeyValue = function() {
	var keyValue = {},
		properties = this.elementData.properties;
	for ( var propertyKey in properties ) {
		if ( !properties.hasOwnProperty( propertyKey ) ) {
			continue;
		}
		keyValue[propertyKey] = properties[propertyKey].value;
	}
	return keyValue;
};

workflows.editor.inspector.Inspector.prototype.preprocessDataForModelUpdate = function( data ) {
	return data;
};

workflows.editor.inspector.Inspector.prototype.updateModel = function( data ) {
	data = this.preprocessDataForModelUpdate( data );
	this.updateElementData( data );
	this.updateExtensionElements( data.extensionElements );
	this.updateProperties( data.properties || {} );
};

workflows.editor.inspector.Inspector.prototype.updateElementData = function( data ) {
	if ( data.name ) {
		this.element.businessObject.set( 'name', data.name );
	}
	this.element.businessObject.set( 'id', data.id );
	if ( this.element.businessObject.$type !== 'bpmn:Process' ) {
		// Process has no visual representation, do not update view
		workflows.editor.modeler.get( 'modeling' ).updateProperties( this.element, { name: data.name } );
	}
};

workflows.editor.inspector.Inspector.prototype.updateExtensionElements = function( data ) {
	var ee = workflows.editor.util.extensionElements.getAll( this.element );
	if ( ee ) {
		var toRemove = [];
		for ( var i = 0; i < ee.values.length; i++ ) {
			if ( ee.values[i].$type === 'wf:Type' ) {
				continue;
			}
			toRemove.push( ee.values[i].$type );
		}
		for ( var i = 0; i < toRemove.length; i++ ) {
			workflows.editor.util.extensionElements.remove( this.element, toRemove[i] );
		}
	}

	workflows.editor.util.extensionElements.assignFromData( this.element, data );
};

workflows.editor.inspector.Inspector.prototype.updateProperties = function( data ) {
	var oldProp = this.getPropertiesKeyValue();

	for ( var oldPropKey in oldProp ) {
		if ( !oldProp.hasOwnProperty( oldPropKey ) ) {
			continue;
		}
		if ( !data.hasOwnProperty( oldPropKey ) ) {
			workflows.editor.util.properties.remove( this.element, oldPropKey );
		}
	}
	for ( var newPropKey in data ) {
		if ( !data.hasOwnProperty( newPropKey ) ) {
			continue;
		}
		workflows.editor.util.properties.set( this.element, newPropKey, data[newPropKey] );
	}
};

workflows.editor.inspector.Inspector.prototype.getNonEditableProperties = function() {
	return [];
};

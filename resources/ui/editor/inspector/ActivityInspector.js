workflows.editor.inspector.ActivityInspector = function( element, dialog ) {
	workflows.editor.inspector.ActivityInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.ActivityInspector, workflows.editor.inspector.Inspector );

workflows.editor.inspector.ActivityInspector.prototype.getDialogTitle = function() {
	return mw.message( 'workflows-ui-editor-inspector-activity-title' ).text();
};

workflows.editor.inspector.ActivityInspector.prototype.getItems = function() {
	var items = [
		{
			name: 'sl-1',
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		}
	];
	for ( var propertyName in this.elementData.properties ) {
		if ( !this.elementData.properties.hasOwnProperty( propertyName ) ) {
			continue;
		}
		if ( this.getNonEditableProperties().indexOf( propertyName ) === -1 ) {
			items.push( this.getPropertyField( propertyName ) );
		}
	}
	items.push( {
		type: 'multiplier',
		name: 'additionalProperties',
		noLayout: true,
		addNewLabel: mw.message( 'workflows-ui-editor-inspector-properties-additional' ).text(),
		style: 'border-top: 1px solid #ddd; margin-top: 10px;',
		returnType: 'object',
		returnKey: 'propertyName',
		base: [ {
			type: 'text',
			name: 'propertyName',
			label: mw.message( 'workflows-ui-editor-inspector-properties-additional-name' ).text()
		}, {
			type: 'text',
			name: 'propertyValue',
			label: mw.message( 'workflows-ui-editor-inspector-properties-additional-value' ).text()
		} ],
		listeners: {
			change: function() {
				this.emit( 'layoutChange' );
			}
		}
	} );
	return items;
};

workflows.editor.inspector.ActivityInspector.prototype.getPropertyField = function( propertyName ) {
	return {
		type: 'text',
		name: 'properties.' + propertyName,
		label: this.getPropertyLabel( propertyName ),
	};
};

workflows.editor.inspector.ActivityInspector.prototype.getPropertyLabel = function( propertyName ) {
	var msg = mw.message( 'workflows-activity-property-' + propertyName );
	if ( msg.exists() ) {
		return msg.text();
	}
	return propertyName;
};

workflows.editor.inspector.ActivityInspector.prototype.preprocessDataForModelUpdate = function( data ) {
	data.properties = data.properties || {};
	for ( var additionalProperty in data.additionalProperties ) {
		if ( !data.additionalProperties.hasOwnProperty( additionalProperty ) ) {
			continue;
		}
		if ( data.properties.hasOwnProperty( additionalProperty ) ) {
			continue;
		}
		data.properties[additionalProperty] = data.additionalProperties[additionalProperty].propertyValue;
	}

	return data;
};


workflows.editor.inspector.ActivityInspector.prototype.convertExtensionElements = function( extensionElements ) {
	return this._getObjectFromValues( extensionElements.values || [] );
};

workflows.editor.inspector.ActivityInspector.prototype._getObjectFromValues = function( values ) {
	var o = {};
	for ( var i = 0; i < values.length; i++ ) {
		if ( !values[i] ) {
			continue;
		}
		if ( values[i].$type === 'wf:Type' ) {
			continue;
		}
		if ( values[i].hasOwnProperty( 'text' ) ) {
			o[values[i].$type] = values[i].text;
			continue;
		}
		var subitems = [];
		for ( var valueKey in values[i] ) {
			if ( !values[i].hasOwnProperty( valueKey ) ) {
				continue;
			}
			// If key starts with $, its internal
			if ( valueKey[0] === '$' ) {
				continue;
			}
			subitems.push( values[i][valueKey] );
		}
		o[values[i].$type] = this._getObjectFromValues( subitems );
	}

	return o;
};

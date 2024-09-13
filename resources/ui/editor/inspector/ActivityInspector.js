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

	items.push( {
		type: 'multiplier',
		name: 'propertiesManual',
		noLayout: true,
		addNewLabel: mw.message( 'workflows-ui-editor-inspector-properties-additional' ).text(),

		wrapInHorizontal: true,
		style: 'padding-left: 40px',
		base: [{
			type: 'label',
			widget_label: mw.message( 'workflows-ui-editor-inspector-properties-additional-name' ).text(),
		},{
			type: 'text',
			name: 'propertyName',
			noLayout: true,
			style: 'width: 40%'
		}, {
			type: 'label',
			widget_label: mw.message( 'workflows-ui-editor-inspector-properties-additional-value' ).text(),
		}, {
			type: 'text',
			name: 'propertyValue',
			noLayout: true,
			style: 'width: 40%'
		} ],
		listeners: {
			change: function() {
				this.emit( 'layoutChange' );
			}
		}
	} );
	return items;
};

workflows.editor.inspector.ActivityInspector.prototype.getPropertyLabel = function( propertyName ) {
	var msg = mw.message( 'workflows-activity-property-' + propertyName );
	if ( msg.exists() ) {
		return msg.text();
	}
	return propertyName;
};

workflows.editor.inspector.ActivityInspector.prototype.convertDataForForm = function( data ) {
	data = workflows.editor.inspector.ActivityInspector.parent.prototype.convertDataForForm.call( this, data );
	var properties = [];
	for ( var propertyKey in data.properties ) {
		if ( !data.properties.hasOwnProperty( propertyKey ) ) {
			continue;
		}
		properties.push( {
			propertyName: propertyKey,
			propertyValue: data.properties[propertyKey]
		} );
	}
	data.propertiesManual = properties;

	return data;
};

workflows.editor.inspector.ActivityInspector.prototype.preprocessDataForModelUpdate = function( data ) {
	data = workflows.editor.inspector.ActivityInspector.parent.prototype.preprocessDataForModelUpdate.call( this, data );
	data.properties = data.properties || {};
	var processedProperties = {};
	if ( data.hasOwnProperty( 'propertiesManual' ) ) {
		for ( var i = 0; i < data.propertiesManual.length; i++ ) {
			processedProperties[data.propertiesManual[i].propertyName] = data.propertiesManual[i].propertyValue;
		}

	}
	data.properties = $.extend( data.properties, processedProperties );
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

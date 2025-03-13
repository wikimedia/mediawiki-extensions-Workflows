workflows.editor.inspector.ProcessInspector = function ( element, dialog ) {
	this.nonEditableItems = {};
	workflows.editor.inspector.ProcessInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.ProcessInspector, workflows.editor.inspector.Inspector );

workflows.editor.inspector.ProcessInspector.prototype.getDialogTitle = function () {
	return mw.message( 'workflows-ui-editor-inspector-process-title' ).text();
};

workflows.editor.inspector.ProcessInspector.prototype.getItems = function () {
	return [
		{
			type: 'checkbox',
			name: 'extensionElements.revision',
			label: mw.message( 'workflows-ui-editor-inspector-process-bind-to-revision' ).text()
		}
	];
};

workflows.editor.inspector.ProcessInspector.prototype.getDefaultItems = function () {
	return [
		{
			type: 'text',
			name: 'name',
			label: mw.message( 'workflows-ui-editor-inspector-process-name' ).text()
		}
	];
};

workflows.editor.inspector.ProcessInspector.prototype.convertExtensionElements = function ( extensionElements ) {
	let context = null;
	const o = {};

	for ( let i = 0; i < ( extensionElements.values || [] ).length; i++ ) {
		if ( !extensionElements.values[ i ] ) {
			continue;
		}
		if ( extensionElements.values[ i ].$type === 'wf:Context' ) {
			context = extensionElements.values[ i ];
			break;
		}
	}
	if ( !context ) {
		return {};
	}
	for ( let ii = 0; ii < ( context.items || [] ).length; ii++ ) {
		const item = context.items[ ii ];
		if ( item.$type === 'wf:ContextItem' ) {
			if ( item.name === 'revision' ) {
				o.revision = true;
				continue;
			}
			this.nonEditableItems[ item.name ] = item.value || '';
		}
	}

	return o;
};

workflows.editor.inspector.ProcessInspector.prototype.preprocessDataForModelUpdate = function ( data ) {
	const items = [];
	for ( const key in this.nonEditableItems ) {
		if ( !this.nonEditableItems.hasOwnProperty( key ) ) {
			continue;
		}
		items.push( { attributes: { name: key }, value: this.nonEditableItems[ key ] } );
	}
	if (
		data.extensionElements.revision
	) {
		items.push( {
			attributes: { name: 'revision' },
			value: ''
		} );
	}
	// Internal value, not to be saved
	delete ( data.extensionElements.revision );

	data.extensionElements = data.extensionElements || {};
	data.extensionElements[ 'wf:Context' ] = data.extensionElements[ 'wf:Context' ] || {};
	data.extensionElements[ 'wf:Context' ][ 'wf:ContextItem' ] = items;
	// Do not allow editing ID, user never needs to do it, and its dangerous
	data.id = this.initialId;
	return data;
};

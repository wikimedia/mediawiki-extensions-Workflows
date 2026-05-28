workflows.editor.inspector.SetTemplateParamInspector = function ( element, dialog ) {
	workflows.editor.inspector.UserVoteInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.SetTemplateParamInspector, workflows.editor.inspector.ActivityInspector );

workflows.editor.inspector.SetTemplateParamInspector.prototype.getDialogTitle = function () {
	return mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-title' ).text();
};

workflows.editor.inspector.SetTemplateParamInspector.prototype.parseTemplateParams = function ( value ) {
	if ( value && typeof value === 'object' && !Array.isArray( value ) ) {
		return value;
	}
	if ( typeof value !== 'string' ) {
		return {};
	}
	try {
		const parsed = JSON.parse( value );
		return parsed && typeof parsed === 'object' && !Array.isArray( parsed ) ? parsed : {};
	} catch ( e ) {
		return {};
	}
};

workflows.editor.inspector.SetTemplateParamInspector.prototype.convertDataForForm = function ( data ) {
	data = workflows.editor.inspector.SetTemplateParamInspector.parent.prototype.convertDataForForm.call(
		this,
		data
	);
	const properties = data.properties || {};
	const templateParams = this.parseTemplateParams( properties[ 'template-params' ] );

	// Backward compatibility for existing single-param format
	if (
		Object.keys( templateParams ).length === 0 &&
		data.properties[ 'template-param' ] !== undefined &&
		data.properties.value !== undefined
	) {
		templateParams[ data.properties[ 'template-param' ] ] = data.properties.value;
	}

	data.properties = properties;
	data.templateParamsManual = Object.keys( templateParams ).map( ( key ) => ( {
		templateParamName: key,
		templateParamValue: templateParams[ key ]
	} ) );
	return data;
};

workflows.editor.inspector.SetTemplateParamInspector.prototype.preprocessDataForModelUpdate = function ( data ) {
	let row,
		name,
		value,
		i;

	data = workflows.editor.inspector.SetTemplateParamInspector.parent.prototype.preprocessDataForModelUpdate.call(
		this,
		data
	);

	data.properties = data.properties || {};

	const params = {};
	const rows = data.templateParamsManual || [];

	for ( i = 0; i < rows.length; i++ ) {
		row = rows[ i ] || {};
		name = row.templateParamName || '';
		value = row.templateParamValue || '';

		name = name.trim();

		if ( name === '' ) {
			continue;
		}

		params[ name ] = value;
	}

	if ( Object.keys( params ).length === 0 ) {
		delete data.properties[ 'template-params' ];
	} else {
		data.properties[ 'template-params' ] = JSON.stringify( params );
	}

	delete data.properties[ 'template-param' ];
	delete data.properties.value;
	delete data.templateParamsManual;

	return data;
};

workflows.editor.inspector.SetTemplateParamInspector.prototype.getItems = function () {
	return [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'text',
			name: 'properties.user',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-user' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-user-help' ).text()
		},
		{
			type: 'text',
			name: 'properties.title',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-title' ).text()
		},
		{
			type: 'number',
			name: 'properties.template-index',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template_index' ).text(),
			help: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template-index-help' ).text(),
			min: 0
		},
		{
			type: 'multiplier',
			name: 'templateParamsManual',
			noLayout: true,
			addNewLabel: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-button-add' ).text(),
			wrapInHorizontal: true,
			style: 'padding-left: 40px',
			base: [
				{
					type: 'text',
					name: 'templateParamName',
					label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template_param' ).text(),
					help: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-template-param-help' ).text(),
					style: 'width: 100%; flex-basis: 100%;',
					required: true
				},
				{
					type: 'text',
					name: 'templateParamValue',
					label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-value' ).text(),
					style: 'width: 100%; flex-basis: 100%;'
				}
			],
			listeners: {
				change: function () {
					this.emit( 'layoutChange' );
				}
			}
		},
		{
			type: 'checkbox',
			name: 'properties.minor',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-minor' ).text()
		},
		{
			type: 'text',
			name: 'properties.comment',
			label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-property-comment' ).text()
		}
	];
};

workflows.editor.inspector.Registry.register( 'set_template_param', workflows.editor.inspector.SetTemplateParamInspector );

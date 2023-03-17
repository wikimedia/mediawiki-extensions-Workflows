workflows.ui.widget.BpmnEditor = function( config ) {
	config.items = this.initItems( config );
	config.action = mw.util.getUrl( mw.config.get( 'wgPageName' ), { action: 'submit' } );
	config.enctype = 'multipart/form-data';
	config.method = 'post';

	workflows.ui.widget.BpmnEditor.super.call( this, config );

	this.editor = null;
	this.$diagram = $( '<div>' ).addClass( 'diagram' ).css( 'height', '700px' );
	this.$diagram.insertBefore( this.fieldset.$element );

	var inspectProcessButton = new OO.ui.ButtonWidget( {
		label: mw.message( 'workflows-ui-editor-inspector-process-title' ).text(),
		classes: [ 'workflows-editor-inspect-process-button' ]
	} );
	this.$diagram.append( inspectProcessButton.$element );
	this.initDiagram( config );
	inspectProcessButton.connect( this, { click: 'inspectProcess' } );
};

OO.inheritClass( workflows.ui.widget.BpmnEditor, OO.ui.FormLayout );

workflows.ui.widget.BpmnEditor.prototype.initItems = function( config ) {
	this.text = new OO.ui.HiddenInputWidget( { name: 'wpTextbox1', id: 'wpTextbox1' } );
	this.fieldset = new OO.ui.FieldsetLayout( {
		classes: [ 'workflows-editor-save-layout' ],
		items: [
			new OO.ui.HiddenInputWidget( { name: 'format', value: 'application/xml' } ),
			new OO.ui.HiddenInputWidget( { name: 'parentRevId', value: config.revid } ),
			new OO.ui.HiddenInputWidget( { name: 'model', value: 'BPMN' } ),
			new OO.ui.HiddenInputWidget( { name: 'wpEditToken', value: config.token } ),
			new OO.ui.HiddenInputWidget( { name: 'wpUnicodeCheck', value: config.unicode_check } ),
			// whatever...
			new OO.ui.HiddenInputWidget( { name: 'wpUltimateParam', value: 1 } ),
			this.text,
			new OO.ui.FieldLayout( new OO.ui.TextInputWidget( { name: 'wpSummary', id: 'wpSummary'} ), {
				label: mw.message( 'workflows-editor-editor-field-summary' ).text(),
				align: 'top'
			} ),
			new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.ButtonInputWidget( {
						label: mw.message( 'workflows-editor-editor-button-save' ).text(),
						type: 'submit',
						flags: [ 'primary', 'progressive' ],
					} ),
					new OO.ui.ButtonWidget( {
						label: mw.message( 'workflows-editor-editor-button-cancel' ).text(),
						href: mw.util.getUrl( mw.config.get( 'wgPageName' ) ),
						flags: [ 'destructive' ],
						framed: false
					} )
				]
			} )
		]
	} );

	return [ this.fieldset ];
};

workflows.ui.widget.BpmnEditor.prototype.initDiagram = async function( config ) {
	config = $.extend( config, this.makeConfig() );
	this.editor = new BpmnJS( config );
	workflows.editor.modeler = this.editor;
	this.editor.get( 'eventBus' ).on( 'element.dblclick', function ( event ) {
		if ( event.element.type === 'bpmn:Process' ) {
			// Dedicated button for Process inspector
			return;
		}
		this.openInspector( event.element );
	}.bind( this ) );

	try {
		await this.editor.importXML( config.xml );
	} catch ( err ) {
		new OO.ui.MessageWidget( { type: 'error', label: err } ).$element.insertBefore( this.$element );
	}
};

workflows.ui.widget.BpmnEditor.prototype.onFormSubmit = function () {
	this.editor.saveXML( { format: true }, function ( err, xml ) {
		this.text.$element.val( xml );
		workflows.ui.widget.BpmnEditor.parent.prototype.onFormSubmit.call( this );
	}.bind( this ) );

};

workflows.ui.widget.BpmnEditor.prototype.makeConfig = function () {
	return {
		container: this.$diagram,
		additionalModules: [
			workflows.editor.customMenuModule
		],
		moddleExtensions: workflows.editor.Schema
	};
};

workflows.ui.widget.BpmnEditor.prototype.openInspector = function ( element ) {
	var inspector = workflows.editor.inspector.Registry.getInspectorForElement( element, this );
	if ( !inspector ) {
		return;
	}
	var windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	var dialog = new workflows.editor.inspector.InspectorDialog( element, { inspector: inspector } );
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog ).closed.then( function( data ) {
		windowManager.removeWindows( [ dialog ] );
		windowManager.destroy();
	}.bind( this ) );
};

workflows.ui.widget.BpmnEditor.prototype.inspectProcess = function () {
	this.openInspector( this.editor.get( 'canvas' ).getRootElement() );
};

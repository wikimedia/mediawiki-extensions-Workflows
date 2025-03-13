workflows.ui.widget.BpmnEditor = function ( config ) {
	config.items = this.initItems( config );
	config.action = mw.util.getUrl( mw.config.get( 'wgPageName' ), { action: 'submit' } );
	config.enctype = 'multipart/form-data';
	config.method = 'post';

	workflows.ui.widget.BpmnEditor.super.call( this, config );

	this.editor = null;
	this.$diagram = $( '<div>' ).addClass( 'diagram' ).css( 'height', '700px' );
	this.$diagram.insertBefore( this.fieldset.$element );

	this.initDiagram( config );
};

OO.inheritClass( workflows.ui.widget.BpmnEditor, OO.ui.FormLayout );

workflows.ui.widget.BpmnEditor.prototype.initItems = function ( config ) {
	this.text = new OO.ui.HiddenInputWidget( { name: 'wpTextbox1', id: 'wpTextbox1' } );
	this.summary = new OO.ui.HiddenInputWidget( { name: 'wpSummary', id: 'wpSummary' } );
	this.toolbar = new OO.ui.Toolbar( workflows.editor.toolFactory, workflows.editor.toolGroupFactory );
	this.toolbar.setup( [
		{
			type: 'bar',
			align: 'before',
			include: [ 'cancel' ]
		},
		{
			align: 'after',
			type: 'list',
			icon: 'menu',
			indicator: null,
			include: [ 'inspectProcess' ]
		},
		{
			align: 'after',
			type: 'bar',
			include: [ 'save' ]
		}
	] );
	this.toolbar.connect( this, {
		editProcessContext: 'inspectProcess',
		save: 'openSaveDialog'
	} );
	this.fieldset = new OO.ui.FieldsetLayout( {
		items: [
			new OO.ui.HiddenInputWidget( { name: 'format', value: 'application/xml' } ),
			new OO.ui.HiddenInputWidget( { name: 'parentRevId', value: config.revid } ),
			new OO.ui.HiddenInputWidget( { name: 'model', value: 'BPMN' } ),
			new OO.ui.HiddenInputWidget( { name: 'wpEditToken', value: config.token } ),
			new OO.ui.HiddenInputWidget( { name: 'wpUnicodeCheck', value: config.unicode_check } ),
			// whatever...
			new OO.ui.HiddenInputWidget( { name: 'wpUltimateParam', value: 1 } ),
			this.text,
			this.summary
		]
	} );

	return [ this.toolbar, this.fieldset ];
};

workflows.ui.widget.BpmnEditor.prototype.initDiagram = async function ( config ) {
	config = $.extend( config, this.makeConfig() );
	this.editor = new BpmnJS( config );
	workflows.editor.modeler = this.editor;
	this.editor.get( 'eventBus' ).on( 'element.dblclick', ( event ) => {
		if ( event.element.type === 'bpmn:Process' ) {
			// Dedicated button for Process inspector
			return;
		}
		this.openInspector( event.element );
	} );

	try {
		await this.editor.importXML( config.xml );
	} catch ( err ) {
		new OO.ui.MessageWidget( { type: 'error', label: err } ).$element.insertBefore( this.$element );
	}
};

workflows.ui.widget.BpmnEditor.prototype.prepForSubmit = function () {
	const dfd = $.Deferred();
	this.editor.saveXML( { format: true }, ( err, xml ) => {
		this.text.$element.val( xml );
		workflows.ui.widget.BpmnEditor.parent.prototype.onFormSubmit.call( this );
		dfd.resolve();
	} );

	return dfd.promise();

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
	const inspector = workflows.editor.inspector.Registry.getInspectorForElement( element, this );
	if ( !inspector ) {
		return;
	}
	this.openDialog( new workflows.editor.inspector.InspectorDialog( element, { inspector: inspector } ) ).then(
		( data ) => { // eslint-disable-line no-unused-vars
			windowManager.destroy(); // eslint-disable-line no-undef
		}
	);
};

workflows.ui.widget.BpmnEditor.prototype.openDialog = function ( dialog, then ) { // eslint-disable-line no-unused-vars
	const windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	windowManager.addWindows( [ dialog ] );
	return windowManager.openWindow( dialog ).closed;
};

workflows.ui.widget.BpmnEditor.prototype.inspectProcess = function () {
	this.openInspector( this.editor.get( 'canvas' ).getRootElement() );
};

workflows.ui.widget.BpmnEditor.prototype.openSaveDialog = function () {
	this.openDialog( new workflows.editor.dialog.SaveDialog( {} ) ).then(
		( data ) => {
			if ( data.action === 'save' ) {
				this.summary.$element.val( data.summary );
				this.prepForSubmit().done( () => {
					this.$element.trigger( 'submit' );
				} );
			}
		}
	);
};

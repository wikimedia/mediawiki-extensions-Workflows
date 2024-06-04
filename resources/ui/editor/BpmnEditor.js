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

		if ( config.xml === '' ) {
			config.xml = '<?xml version="1.0" encoding="UTF-8"?>\n' +
				'<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:wf="http://hallowelt.com/schema/bpmn/wf" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" id="Definitions_1vrglfw" targetNamespace="http://bpmn.io/schema/bpmn">\n' +
				'	<bpmn:process id="Process_1ifcgca" isExecutable="false">\n' +
				'		<bpmn:extensionElements>\n' +
				'			<wf:context>\n' +
				'				<wf:contextItem name="pageId"/>\n' +
				'				<wf:contextItem name="revision"/>\n' +
				'			</wf:context>\n' +
				'		</bpmn:extensionElements>\n' +
				'	</bpmn:process>\n' +
				'	<bpmndi:BPMNDiagram id="BPMNDiagram_1">\n' +
				'		<bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1ifcgca" />\n' +
				'	</bpmndi:BPMNDiagram>\n' +
				'</bpmn:definitions>';
		}

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

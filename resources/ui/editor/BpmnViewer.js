workflows.ui.widget.BpmnViewer = function( config ) {
	config.padded = false;
	config.expanded = false;
	workflows.ui.widget.BpmnViewer.super.call( this, config );

	this.makeDiagramPanel( config.xml );
	this.makeRawXmlPanel( config.xml );
};

OO.inheritClass( workflows.ui.widget.BpmnViewer, OO.ui.PanelLayout );

workflows.ui.widget.BpmnViewer.prototype.makeDiagramPanel = async function( xml ) {
	var layout = new OO.ui.FieldsetLayout( {
		label: mw.message( 'workflows-editor-viewer-section-diagram' ).text()
	} );
	this.$diagram = $( '<div>' ).addClass( 'diagram' ).css( 'height', '500px' );
	layout.$element.append( this.$diagram );
	this.$element.append( layout.$element );

	var viewer = new BpmnJS( {
		container: this.$diagram
	} );

	await viewer.importXML( xml );
	setTimeout( function() {
		// Run in the next loop run
		var canvas = viewer.get( 'canvas' );
		// zoom to fit full viewport
		canvas.zoom( 'fit-viewport', 'auto' );
	}, 1 );
};

workflows.ui.widget.BpmnViewer.prototype.makeRawXmlPanel = function( xml ) {
	var toggle = new OO.ui.ToggleButtonWidget( {
		label: mw.message( 'workflows-editor-viewer-section-xml' ).text()
	} );
	toggle.connect( this, {
		change: function( value ) {
			this.$raw.toggle( value );
		}
	} );
	this.$raw = $( '<pre>' ).text( xml );
	this.$element.append( toggle.$element );
	this.$element.append( this.$raw );
	this.$raw.hide();
};

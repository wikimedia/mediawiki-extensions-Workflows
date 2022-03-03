( function ( mw, $, wf ) {
	workflows.ui.TriggerEditorBooklet = function( cfg ) {
		workflows.ui.TriggerEditorBooklet.parent.call( this, cfg );
		this.triggerData = cfg || null;
		this.makePages();
	};

	OO.inheritClass( workflows.ui.TriggerEditorBooklet, OO.ui.BookletLayout );

	workflows.ui.TriggerEditorBooklet.prototype.makePages = function() {
		this.pages = {
			triggerTypeSelection: new workflows.ui.TriggerSelectionPage( 'triggerTypeSelection' ),
			triggerDetails: new workflows.ui.TriggerDetailsPage( 'triggerDetails', { expanded: false } )
		};

		this.addPages( Object.values( this.pages ) );
	};
} )( mediaWiki, jQuery, workflows );

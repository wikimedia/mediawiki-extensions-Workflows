( function ( mw, $, wf ) {
	workflows.ui.WorkflowStartBooklet = function( cfg ) {
		workflows.ui.WorkflowStartBooklet.parent.call( this, cfg );

		this.repos = cfg.repos;

		this.makePages();
	};

	OO.inheritClass( workflows.ui.WorkflowStartBooklet, OO.ui.BookletLayout );

	workflows.ui.WorkflowStartBooklet.prototype.makePages = function() {
		this.pages = {
			wfSelection: new workflows.ui.WorkflowSelectionPage( 'wfSelection',{
				repos: this.repos
			} ),
			init: new workflows.ui.WorkflowStartPage( 'init' )
		};

		this.addPages( Object.values( this.pages ) );
	};
} )( mediaWiki, jQuery, workflows );

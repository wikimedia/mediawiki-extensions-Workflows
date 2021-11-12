( function ( mw, $, wf ) {
	workflows.ui.WorkflowOverviewBooklet = function( cfg ) {
		workflows.ui.WorkflowOverviewBooklet.parent.call( this, cfg );
		this.overview = cfg.overview || null;
		this.makePages();
		this.$element.addClass( 'overview-booklet' );
	};

	OO.inheritClass( workflows.ui.WorkflowOverviewBooklet, OO.ui.BookletLayout );

	workflows.ui.WorkflowOverviewBooklet.prototype.makePages = function() {
		this.pages = {
			details: new workflows.ui.WorkflowDetailsPage( 'details', { expanded: false } ),
			abortRestore: new workflows.ui.WorkflowAbortRestorePage( 'abortRestore', { expanded: false } )
		};
		if ( this.overview !== null ) {
			this.pages.list = new workflows.ui.WorkflowListPage( 'list', { listType: this.overview, expanded: false } );
		}

		this.addPages( Object.values( this.pages ) );
	};
} )( mediaWiki, jQuery, workflows );

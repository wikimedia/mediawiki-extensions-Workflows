( function () {
	workflows.ui.WorkflowStartBooklet = function ( cfg ) {
		workflows.ui.WorkflowStartBooklet.parent.call( this, cfg );

		this.repos = cfg.repos;
		this.$overlay = cfg.$overlay;

		this.makePages();
	};

	OO.inheritClass( workflows.ui.WorkflowStartBooklet, OO.ui.BookletLayout );

	workflows.ui.WorkflowStartBooklet.prototype.makePages = function () {
		this.pages = {
			wfSelection: new workflows.ui.WorkflowSelectionPage( 'wfSelection', {
				repos: this.repos,
				$overlay: this.$overlay
			} ),
			init: new workflows.ui.WorkflowStartPage( 'init', {
				$overlay: this.$overlay
			} )
		};

		this.addPages( Object.values( this.pages ) );
	};
}() );

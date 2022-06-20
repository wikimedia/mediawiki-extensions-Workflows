( function ( mw, $ ) {
	workflows.ui.WorkflowListPage = function( name, cfg ) {
		workflows.ui.WorkflowListPage.parent.call( this, name, cfg );
		this.type = cfg.listType;

		var listCfg = {
			expanded: false,
			singleClickSelect: true
		};
		if ( this.type === 'page' ) {
			listCfg.filter = {
				context: {
					field: 'context',
					type: 'string',
					value: { pageId: workflows.context.getWorkflowContext().pageId }
				}
			};
			this.title = 'Workflows related to this page';
		}
		this.panel = new workflows.ui.panel.WorkflowList( listCfg );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.WorkflowListPage, OO.ui.PageLayout );

	workflows.ui.WorkflowListPage.prototype.getTitle = function() {
		if ( this.type === 'page' ) {
			return mw.message( 'workflows-ui-workflow-overview-dialog-title-list-page' ).text();
		}
		return mw.message( 'workflows-ui-workflow-overview-dialog-title-list' ).text();
	};

} )( mediaWiki, jQuery );

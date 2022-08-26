( function ( mw, $, wf ) {
	workflows.ui.panel.WorkflowList = function( cfg ) {
		cfg = $.extend( {
			padded: true,
			expanded: false
		}, cfg || {} );
		this.isLoading = false;

		this.singleClickSelect = cfg.singleClickSelect || false;
		this.defaultFilter = cfg.filter || {};
		workflows.ui.panel.WorkflowList.parent.call( this, cfg );
		this.data = [];
		this.filterData = $.extend(
			{ state: { type: 'list', operator: 'in', value: [ 'running' ] } }, this.defaultFilter
		);

		this.store = new workflows.store.Workflows( {
			pageSize: 10,
			filter: this.filterData
		} );
		this.store.connect( this, {
			loadFailed: function() {
				this.emit( 'loadFailed' );
			},
			loading: function() {
				if ( this.isLoading ) {
					return;
				}
				this.isLoading = true;
				this.emit( 'loadStarted' );
			}
		} );
		this.grid = this.makeGrid();
		this.grid.connect( this, {
			datasetChange: function() {
				this.isLoading = false;
				this.emit( 'loaded' );
			}
		} );

		this.$element.append( this.$grid );

	};

	OO.inheritClass( workflows.ui.panel.WorkflowList, OO.ui.PanelLayout );

	workflows.ui.panel.WorkflowList.prototype.makeGrid = function() {
		this.$grid = $( '<div>' );

		var gridCfg = {
			deletable: false,
			style: 'differentiate-rows',
			border: 'horizontal',
			columns: {
				has_notice: {
					type: "icon",
					width: 35
				},
				title: {
					headerText: mw.message( 'workflows-ui-overview-details-workflow-type-label' ).text(),
					type: "text",
					filter: {
						type: 'text'
					},
					sortable: true
				},
				page_prefixed_text: {
					headerText: mw.message( 'workflows-ui-overview-details-section-page' ).text(),
					type: "url",
					urlProperty: "page_link",
					valueParser: function( val ) {
						// Truncate long titles
						return val.length > 35 ? val.substr( 0, 34 ) + '...' : val;
					},
					sortable: true,
					filter: {
						type: 'text'
					}
				},
				assignee: {
					headerText: mw.message( 'workflows-ui-overview-details-section-assignee' ).text(),
					type: "text",
					valueParser: function( val, row ) {
						var $layout = $( '<div>' );
						for ( var i = 0; i < val.length; i++ ) {
							if ( i > 2 ) {
								$layout.append( '...' );
								return new OO.ui.HtmlSnippet( $layout );
							}
							$layout.append( $( val[i] ).css( { display: 'block' } ) );
						}
						return new OO.ui.HtmlSnippet( $layout );
					}
				},
				state: {
					headerText: mw.message( 'workflows-ui-overview-details-state-column' ).text(),
					valueParser: function( value, row ) {
						if ( typeof value !== 'string' ) {
							return value;
						}
						return new OO.ui.LabelWidget( {
							label: row.state_label,
							title: row.state_label,
							classes: [ 'workflow-state', 'workflow-state-icon-' + value ]
						} ).$element;
					},
					filter: {
						type: 'list',
						list: [
							{ data: 'running', label: mw.message( 'workflows-ui-overview-details-state-running' ).text() },
							{ data: 'aborted', label: mw.message( 'workflows-ui-overview-details-state-aborted' ).text() },
							{ data: 'finished', label: mw.message( 'workflows-ui-overview-details-state-finished' ).text() }
						],
						closePopupOnChange: true
					},
					width: 90,
					sortable: true
				},
				start_ts: {
					headerText: mw.message( 'workflows-ui-overview-details-start-time-column' ).text(),
					type: "date",
					display: "start_formatted",
					sortable: true
				},
				last_ts: {
					headerText: mw.message( 'workflows-ui-overview-details-last-time-column' ).text(),
					type: "date",
					display: "last_formatted",
					sortable: true
				},
				detailsAction: {
					type: "action",
					actionId: 'details',
					title: mw.message( 'workflows-ui-overview-details-action-details-column' ).text(),
					icon: 'infoFilled'
				}
			},
			store: this.store
		};

		var grid = new OOJSPlus.ui.data.GridWidget( gridCfg );
		grid.connect( this, {
			action: function( action, row ) {
				if ( action !== 'details' ) {
					return;
				}
				this.emit( 'selected', row.id );
			}
		} );
		this.$grid.html( grid.$element );

		this.emit( 'gridRendered' );
		return grid;
	};
} )( mediaWiki, jQuery, workflows );

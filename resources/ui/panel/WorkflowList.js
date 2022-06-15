( function ( mw, $, wf ) {
	workflows.ui.panel.WorkflowList = function( cfg ) {
		cfg = $.extend( {
			padded: true,
			expanded: false
		}, cfg || {} );

		this.singleClickSelect = cfg.singleClickSelect || false;
		this.defaultFilter = cfg.filter || {};
		workflows.ui.panel.WorkflowList.parent.call( this, cfg );
		this.data = [];
		this.filterData = $.extend(
			true, { active: 1 }, this.filterData, this.defaultFilter
		);

		this.store = new workflows.store.Workflows( {
			pageSize: 25,
			filterData: this.filterData
		} );
		this.store.connect( this, {
			loadFailed: function() {
				this.emit( 'loadFailed' );
			},
			loading: function() {
				this.emit( 'loadStarted' );
			}
		} );
		this.grid = this.makeGrid();
		this.grid.connect( this, {
			datasetChange: function() {
				this.emit( 'loaded' );
			}
		} );
		var headerLayout = new OO.ui.HorizontalLayout( {
			items: this.getFilterLayouts()
		} );

		this.$element.append( headerLayout.$element );
		this.$element.append( this.$grid );

	};

	OO.inheritClass( workflows.ui.panel.WorkflowList, OO.ui.PanelLayout );

	workflows.ui.panel.WorkflowList.prototype.getFilterLayouts = function() {
		this.activeFilter = new OO.ui.ButtonSelectWidget( {
			items: [
				new OO.ui.ButtonOptionWidget( {
					data: 0,
					label: mw.message( 'workflows-ui-overview-grid-filter-state-all' ).text(),
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 1,
					label: mw.message( 'workflows-ui-overview-grid-filter-state-active' ).text(),
				} )
			]
		} );

		this.activeFilter.selectItemByData( 1 );
		this.activeFilter.connect( this, {
			select: function( item ) {
				this.filterChanged( { active: item.getData() } );
			}
		} );

		return [
			this.activeFilter
		];
	};

	workflows.ui.panel.WorkflowList.prototype.setFiltersDisabled = function( disabled ) {
		this.activeFilter.setDisabled( disabled );
	};

	workflows.ui.panel.WorkflowList.prototype.filterChanged = function( data ) {
		this.filterData = $.extend(
			true, {}, this.filterData, data, this.defaultFilter
		);
		this.setFiltersDisabled( true );
		this.$element.find( '.oo-ui-messageWidget' ).remove();
		this.setFiltersDisabled( false );
		this.store.filter( this.filterData ).done( function() {
			this.setFiltersDisabled( false );
			this.grid.paginator.init();
		}.bind( this ) ).fail( function( error ) {
			this.setFiltersDisabled( false );
			this.$element.prepend( new OO.ui.MessageWidget( {
				type: 'error',
				label: error || mw.message( "workflows-error-generic" ).text()
			} ).$element.css( 'margin-bottom', '20px' ) );
		}.bind( this ) );
	};


	workflows.ui.panel.WorkflowList.prototype.makeGrid = function() {
		this.$grid = $( '<div>' );

		var gridCfg = {
			deletable: false,
			style: 'differentiate-rows',
			border: 'horizontal',
			columns: {
				notice: {
					type: "icon",
					width: 35
				},
				title: {
					headerText: mw.message( 'workflows-ui-overview-details-workflow-type-label' ).text(),
					type: "text"
				},
				page: {
					headerText: mw.message( 'workflows-ui-overview-details-section-page' ).text(),
					type: "url",
					urlProperty: 'page_link'
				},
				assignee: {
					headerText: mw.message( 'workflows-ui-overview-details-section-assignee' ).text(),
					type: "text",
					valueParser: function( value, row ) {
						if ( typeof value === 'string' ) {
							return value;
						}
						return value.join( ', ' );
					}
				},
				state: {
					headerText: mw.message( 'workflows-ui-overview-details-state-column' ).text()
				},
				start: {
					headerText: mw.message( 'workflows-ui-overview-details-start-time-column' ).text(),
					type: "date",
					display: "startDate"
				},
				last: {
					headerText: mw.message( 'workflows-ui-overview-details-last-time-column' ).text(),
					type: "date",
					display: "lastDate"
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

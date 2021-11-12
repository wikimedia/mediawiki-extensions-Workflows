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
		this.filterData = {};

		var headerLayout = new OO.ui.HorizontalLayout( {
			items: this.getFilterLayouts()
		} );

		this.$element.append( headerLayout.$element );
		this.$grid = $( '<div>' );
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
			],
			disabled: true
		} );

		this.activeFilter.connect( this, {
			select: function( item ) {
				this.filterChanged( { active: item.getData() } );
			}
		} );
		this.activeFilter.selectItemByData( 1 );

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
		this.emit( 'loadStarted' );
		this.load( this.filterData ).done( function() {
			this.setFiltersDisabled( false );
			this.drawGrid();
		}.bind( this ) ).fail( function( error ) {
			this.setFiltersDisabled( false );
			this.$element.prepend( new OO.ui.MessageWidget( {
				type: 'error',
				label: error || mw.message( "workflows-error-generic" ).text()
			} ).$element.css( 'margin-bottom', '20px' ) );
		}.bind( this ) );
	};

	workflows.ui.panel.WorkflowList.prototype.load = function( filter ) {
		this.data = [];
		var dfd = $.Deferred(),
			active = filter.active || false;
		delete( filter.active );
		wf.list.filtered( active, filter, true ).done( function( response ) {
			if ( !response.hasOwnProperty( 'workflows' ) ) {
				return;
			}
			var workflows = response.workflows;
			for ( var id in workflows ) {
				if ( !workflows.hasOwnProperty( id ) ) {
					continue;
				}
				var page = null;
				if ( workflows[id].contextPage ) {
					page = new mw.Title( workflows[id].contextPage );
				}
				this.data.push( {
					id: id,
					title: workflows[id].definition.title,
					page: page ? page.getPrefixedText() : '',
					page_link: page ? page.getUrl() : '',
					current: workflows[id].state === 'running' ? workflows[id].current[0] : '-',
					state: mw.message( 'workflows-ui-overview-details-state-' + workflows[id].state ).text(),
					notice: this.getNotice( workflows[id] ),
					start: workflows[id].timestamps.startFormatted,
					last: workflows[id].timestamps.lastFormatted,
				} );
			}
			this.emit( 'loaded', this.data );
			dfd.resolve( this.data );
		}.bind( this ) )
		.fail( function( response ) {
			this.emit( 'loadFailed', response );
			dfd.reject( response.error.message || false );
		}.bind( this ) );

		return dfd.promise();
	};

	workflows.ui.panel.WorkflowList.prototype.getNotice = function( workflow ) {
		// Return "warning" or "error"
		if ( this.isAutoAbort( workflow ) ) {
			return 'error';
		}
		return '';
	};

	workflows.ui.panel.WorkflowList.prototype.isAutoAbort = function( workflow ) {
		var message = workflow.stateMessage;
		if ( typeof message === 'object' ) {
			return message.isAuto;
		}

		return false;
	};

	workflows.ui.panel.WorkflowList.prototype.drawGrid = function() {
		var gridCfg = {
			pageSize: 10,
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
				current: {
					headerText: mw.message( 'workflows-ui-overview-details-section-activity' ).text(),
					type: "text"
				},
				state: {
					headerText: mw.message( 'workflows-ui-overview-details-state-column' ).text(),
					type: "text"
				},
				start: {
					headerText: mw.message( 'workflows-ui-overview-details-start-time-column' ).text(),
					type: "text"
				},
				last: {
					headerText: mw.message( 'workflows-ui-overview-details-last-time-column' ).text(),
					type: "text"
				}
			},
			data: this.data
		};

		var voGrid = new OOJSPlus.ui.data.GridWidget( gridCfg );
		voGrid.connect( this, {
			cellDblclick: function( e ) {
				if ( !this.singleClickSelect ) {
					this.emit( 'selected', e.data.item.id );
				}
			},
			cellClick: function( e ) {
				if ( this.singleClickSelect ) {
					this.emit( 'selected', e.data.item.id );
				}
			}
		} );
		this.$grid.html( voGrid.$element );

		this.emit( 'gridRendered' );
	};
} )( mediaWiki, jQuery, workflows );

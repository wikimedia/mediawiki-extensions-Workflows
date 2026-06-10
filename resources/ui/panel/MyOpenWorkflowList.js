( function ( mw, $ ) {
	workflows.ui.panel.MyOpenWorkflowList = function ( cfg ) {
		cfg = $.extend( {
			padded: true,
			expanded: false
		}, cfg || {} );
		this.stateId = cfg.stateId || 'workflows-myopen-grid';

		workflows.ui.panel.MyOpenWorkflowList.parent.call( this, cfg );

		this.store = new workflows.store.Workflows( {
			pageSize: 10,
			filter: {
				active: { type: 'boolean', operator: 'eq', value: true },
				assigned_to_me: { type: 'boolean', operator: 'eq', value: true }
			}
		} );

		this.grid = this.makeGrid();
		this.$element.append( this.$grid );
	};

	OO.inheritClass( workflows.ui.panel.MyOpenWorkflowList, OO.ui.PanelLayout );

	workflows.ui.panel.MyOpenWorkflowList.prototype.makeGrid = function () {
		this.$grid = $( '<div>' );

		const gridCfg = {
			deletable: false,
			style: 'differentiate-rows',
			stateId: this.stateId,
			exportable: true,
			columns: {
				has_notice: {
					headerText: mw.message( 'workflows-ui-overview-details-has-notice-label' ).text(),
					invisibleLabel: true,
					type: 'icon',
					width: 35,
					valueParser: function ( val ) {
						return val ? 'alert' : '';
					}
				},
				title: {
					headerText: mw.message( 'workflows-ui-overview-details-workflow-type-label' ).text(),
					type: 'text',
					filter: {
						type: 'text'
					},
					sortable: true,
					autoClosePopup: true
				},
				page_prefixed_text: {
					headerText: mw.message( 'workflows-ui-overview-details-section-page' ).text(),
					type: 'url',
					urlProperty: 'page_link',
					sortable: true,
					filter: {
						type: 'text'
					},
					autoClosePopup: true
				},
				initiator: {
					headerText: mw.message( 'workflows-ui-overview-details-initiator-column' ).text(),
					type: 'user',
					hidden: true
				},
				assignee: {
					headerText: mw.message( 'workflows-ui-overview-details-section-assignee' ).text(),
					type: 'user',
					hidden: true,
					valueParser: function ( val ) {
						const $layout = $( '<div>' );
						for ( let i = 0; i < val.length; i++ ) {
							if ( i > 2 ) {
								$layout.append( '...' );
								return new OO.ui.HtmlSnippet( $layout );
							}
							$layout.append( $( val[ i ] ).css( { display: 'block' } ) );
						}
						return new OO.ui.HtmlSnippet( $layout );
					}
				},
				state: {
					headerText: mw.message( 'workflows-ui-overview-details-state-column' ).text(),
					valueParser: function () {
						const stateLabel = mw.message( 'workflows-ui-overview-details-state-running' ).text();
						return new OO.ui.LabelWidget( {
							label: stateLabel,
							title: stateLabel,
							classes: [ 'workflow-state', 'workflow-state-icon-running' ]
						} ).$element;
					},
					width: 90,
					sortable: true,
					autoClosePopup: true
				},
				start_ts: {
					headerText: mw.message( 'workflows-ui-overview-details-start-time-column' ).text(),
					type: 'date',
					display: 'start_formatted',
					sortable: true
				},
				last_ts: {
					headerText: mw.message( 'workflows-ui-overview-details-last-time-column' ).text(),
					type: 'date',
					display: 'last_formatted',
					sortable: true
				}
			},
			store: this.store,
			provideExportData: function () {
				const dfd = $.Deferred();
				workflows.list.filtered( {
					filter: {
						active: { type: 'boolean', operator: 'eq', value: true },
						assigned_to_me: { type: 'boolean', operator: 'eq', value: true }
					},
					sort: {
						page_prefixed_text: {
							direction: 'ASC'
						}
					},
					limit: -1
				} ).done( ( response ) => {
					const $table = $( '<table>' );
					let $row = $( '<tr>' );
					let $cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-workflow-type-label' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-section-page' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-initiator-column' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-section-assignee' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-state-column' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-start-time-column' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-last-time-column' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-start-time-raw-column' ).text() );
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append( mw.message( 'workflows-ui-overview-details-last-time-raw-column' ).text() );
					$row.append( $cell );

					$table.append( $row );

					for ( const id in response.workflows || {} ) {
						if ( !Object.prototype.hasOwnProperty.call( response.workflows, id ) ) {
							continue;
						}
						const record = response.workflows[ id ];
						$row = $( '<tr>' );

						$cell = $( '<td>' );
						$cell.append( record.title );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.page_prefixed_text );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.initiator || '' );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( ( record.assignee || [] ).join( ',' ) );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.state );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.start_formatted );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.last_formatted );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.start_ts );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.last_ts );
						$row.append( $cell );

						$table.append( $row );
					}

					dfd.resolve( '<table>' + $table.html() + '</table>' );
				} ).fail( () => {
					dfd.reject( 'Failed to load data' );
				} );

				return dfd.promise();
			}
		};

		const grid = new OOJSPlus.ui.data.GridWidget( gridCfg );
		grid.setColumnsVisibility( grid.visibleColumns );
		this.$grid.html( grid.$element );
		return grid;
	};
}( mediaWiki, jQuery ) );

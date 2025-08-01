( function ( mw, $ ) {
	workflows.ui.panel.WorkflowList = function ( cfg ) {
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
			loadFailed: function () {
				this.emit( 'loadFailed' );
			},
			loading: function () {
				if ( this.isLoading ) {
					return;
				}
				this.isLoading = true;
				this.emit( 'loadStarted' );
			}
		} );
		this.grid = this.makeGrid();
		this.grid.connect( this, {
			datasetChange: function () {
				this.isLoading = false;
				this.emit( 'loaded' );
			}
		} );

		this.$element.append( this.$grid );

	};

	OO.inheritClass( workflows.ui.panel.WorkflowList, OO.ui.PanelLayout );

	workflows.ui.panel.WorkflowList.prototype.makeGrid = function () {
		this.$grid = $( '<div>' );

		const gridCfg = {
			deletable: false,
			style: 'differentiate-rows',
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
				assignee: {
					headerText: mw.message( 'workflows-ui-overview-details-section-assignee' ).text(),
					type: 'user',
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
					},
					filter: {
						type: 'user',
						closePopupOnChange: true
					}
				},
				state: {
					headerText: mw.message( 'workflows-ui-overview-details-state-column' ).text(),
					valueParser: function ( value, row ) {
						if ( typeof value !== 'string' ) {
							return value;
						}
						return new OO.ui.LabelWidget( { // eslint-disable-line mediawiki/class-doc
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
				},
				detailsAction: {
					type: 'action',
					actionId: 'details',
					headerText: mw.message( 'workflows-ui-overview-details-action-details-column' ).text(),
					title: mw.message( 'workflows-ui-overview-details-action-details-column' ).text(),
					invisibleHeader: true,
					icon: 'infoFilled'
				}
			},
			store: this.store,
			provideExportData: function () {
				const dfd = $.Deferred(),
					store = new workflows.store.Workflows( {
						pageSize: -1,
						sorter: {
							page_prefixed_text: {
								direction: 'ASC'
							}
						}
					} );
				store.load().done( ( response ) => {
					const $table = $( '<table>' );
					let $row = $( '<tr>' );
					let $cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-workflow-type-label' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-section-page' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-section-assignee' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-state-column' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-start-time-column' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-last-time-column' ).text()
					);
					$row.append( $cell );
					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-start-time-raw-column' ).text()
					);
					$row.append( $cell );

					$cell = $( '<td>' );
					$cell.append(
						mw.message( 'workflows-ui-overview-details-last-time-raw-column' ).text()
					);
					$row.append( $cell );

					$table.append( $row );

					for ( const id in response ) {
						if ( !response.hasOwnProperty( id ) ) {
							continue;
						}
						const record = response[ id ];
						$row = $( '<tr>' );

						$cell = $( '<td>' );
						$cell.append( record.title );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.page_prefixed_text );
						$row.append( $cell );

						$cell = $( '<td>' );
						$cell.append( record.assignee.join( ',' ) );
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
		grid.connect( this, {
			action: function ( action, row ) {
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
}( mediaWiki, jQuery ) );

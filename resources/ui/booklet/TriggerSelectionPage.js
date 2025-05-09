( function ( mw, $ ) {
	workflows.ui.TriggerSelectionPage = function ( name, cfg ) {
		workflows.ui.TriggerSelectionPage.parent.call( this, name, cfg );
		this.action = '';
		this.panel = new OO.ui.PanelLayout( {
			padded: false,
			expanded: false
		} );

		this.selectWidget = new OO.ui.DropdownWidget( {
			$overlay: cfg.$overlay
		} );
		this.panel.$element.append( new OO.ui.FieldLayout( this.selectWidget, {
			label: mw.msg( 'workflows-ui-trigger-type-label' ),
			align: 'top'
		} ).$element );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.TriggerSelectionPage, OO.ui.PageLayout );

	workflows.ui.TriggerSelectionPage.prototype.init = function () {
		this.selectWidget.getMenu().connect( this, {
			select: function ( item ) {
				this.value = null;
				if ( item ) {
					this.value = item.getData();
					this.emit( 'triggerSelected', this.value );
				}
			}
		} );
		workflows.triggers.getAvailableTypes().done( ( types ) => {
			const options = [];
			for ( const type in types ) {
				if ( !types.hasOwnProperty( type ) ) {
					continue;
				}
				if ( types[ type ].editor === null ) {
					// No UI
					continue;
				}
				const option = new OO.ui.MenuOptionWidget( {
					data: Object.assign( {}, {
						editor: types[ type ].editor,
						label: types[ type ].label,
						desc: types[ type ].desc || []
					}, { type: type } ),
					label: types[ type ].label
				} );
				$( '<span>' )
					.css( {
						'font-size': '0.9em',
						color: 'grey'
					} )
					.html( types[ type ].desc ).insertAfter( option.$label );
				options.push( option );
			}
			this.selectWidget.getMenu().addItems( options );
		} ).fail( () => {
			this.emit( 'error' );
		} );
	};

	workflows.ui.TriggerSelectionPage.prototype.getTitle = function () {
		return mw.message( 'workflows-ui-workflow-trigger-editor-booklet-page-selection-title' ).text();
	};

	workflows.ui.TriggerSelectionPage.prototype.reset = function () {
		this.selectWidget.getMenu().selectItem( null );
	};

	workflows.ui.TriggerSelectionPage.prototype.getTriggerKey = function () {
		return this.value.type;
	};

	workflows.ui.TriggerSelectionPage.prototype.getLabel = function () {
		return this.value.label;
	};

	workflows.ui.TriggerSelectionPage.prototype.getEditor = function () {
		return this.value.editor;
	};

	workflows.ui.TriggerSelectionPage.prototype.getDesc = function () {
		return this.value.desc;
	};

}( mediaWiki, jQuery ) );

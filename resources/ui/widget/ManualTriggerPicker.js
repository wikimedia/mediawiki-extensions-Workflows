( function ( mw, $, wf ) {
	workflows.ui.widget.ManualTriggerPicker = function( cfg ) {
		cfg.label = mw.message( 'workflows-ui-starter-select-workflow' ).text();
		cfg.$overlay = true;
		workflows.ui.widget.ManualTriggerPicker.parent.call( this, cfg );
		this.value = cfg.value || {};

		this.loadOptions();
	};

	OO.inheritClass( workflows.ui.widget.ManualTriggerPicker, OO.ui.DropdownWidget );

	workflows.ui.widget.ManualTriggerPicker.prototype.loadOptions = function() {
		wf.triggers.getManualTriggersForPage( mw.config.get( 'wgPageName' ) ).done( function( triggers ) {
			var menuItems = [];
			for ( var key in triggers ) {
				if ( !triggers.hasOwnProperty( key ) ) {
					continue;
				}
				if ( triggers[key].type !== 'manual' ) {
					continue;
				}
				var option = new OO.ui.MenuOptionWidget( {
					data: {
						workflow: {
							repository: triggers[key].repository,
							definition: triggers[key].definition
						},
						desc: triggers[key].description_parsed || '',
						contextData: triggers[key].contextData || {},
						initData: triggers[key].initData || {}
					},
					label: triggers[key].name_parsed
				} );
				if ( triggers[key].hasOwnProperty( 'description_parsed' ) ) {
					$( '<span>' )
					.css( {
						'font-size': '0.9em',
						'color': 'grey'
					} )
					.html( triggers[key].description_parsed ).insertAfter( option.$label );
				}
				menuItems.push( option );
			}
			this.menu.addItems( menuItems );
		}.bind( this ) ).fail( function() {
			this.emit( 'error' );
		} );
	};

	workflows.ui.widget.ManualTriggerPicker.prototype.setValidityFlag = function( valid ) {
		if ( valid ) {
			this.$element.removeClass( 'oo-ui-flaggedElement-invalid' );
		} else {
			this.$element.addClass( 'oo-ui-flaggedElement-invalid' );
		}
	};
} )( mediaWiki, jQuery, workflows );

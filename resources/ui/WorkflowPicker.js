( function ( mw, $, wf ) {
	workflows.ui.WorkflowPickerWidget = function( cfg ) {
		cfg.label = mw.message( 'workflows-ui-starter-select-workflow' ).text();
		cfg.$overlay = true;
		this.repos = cfg.repos || [];
		workflows.ui.WorkflowPickerWidget.parent.call( this, cfg );
		this.value = cfg.value || {};

		this.loadOptions();
	};

	OO.inheritClass( workflows.ui.WorkflowPickerWidget, OO.ui.DropdownWidget );

	workflows.ui.WorkflowPickerWidget.prototype.loadOptions = function() {
		wf.util.getAvailableWorkflowOptions( this.repos ).done( function( options ) {
			var menuItems = [], selectedOption;
			for ( var i = 0; i < options.length; i++ ) {
				var option = new OO.ui.MenuOptionWidget( options[i] );
				// Select option
				if (
					this.value && this.value.workflow === options[i].data.workflow.workflow &&
					this.value.repo === options[i].data.workflow.repo
				) {
					selectedOption = option;
				}
				$( '<span>' )
				.css( {
					'font-size': '0.9em',
					'color': 'grey'
				} )
				.html( options[i].desc ).insertAfter( option.$label );
				menuItems.push( option );
			}
			this.menu.addItems( menuItems );
			if ( selectedOption ) {
				this.menu.selectItemByData( selectedOption.getData() );
			}
		}.bind( this ) ).fail( function() {
			this.emit( 'error' );
		} );
	};

	workflows.ui.WorkflowPickerWidget.prototype.setValidityFlag = function( valid ) {
		if ( valid ) {
			this.$element.removeClass( 'oo-ui-flaggedElement-invalid' );
		} else {
			this.$element.addClass( 'oo-ui-flaggedElement-invalid' );
		}
	};
} )( mediaWiki, jQuery, workflows );

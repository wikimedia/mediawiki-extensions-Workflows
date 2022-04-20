( function ( mw, $, wf ) {
	workflows.ui.WorkflowMultiselect = function( cfg ) {
		cfg = cfg || {};
		cfg.$overlay = true;

		this.repos = cfg.repos || [];

		this.selectedValue = cfg.value || [];
		cfg.value = [];
		workflows.ui.WorkflowMultiselect.parent.call( this, cfg );

		this.loadOptions();
	};

	OO.inheritClass( workflows.ui.WorkflowMultiselect, OO.ui.MenuTagMultiselectWidget );

	workflows.ui.WorkflowMultiselect.prototype.loadOptions = function() {
		wf.util.getAvailableWorkflowOptions().done( function( options ) {
			var selected = [];
			this.menu.addItems( options.map( function ( data ) {
				if ( this.isSelected( data ) ) {
					selected.push( data );
				}
				return new OO.ui.MenuOptionWidget( data );
			}.bind( this ) ) );
			this.setValue( selected );
		}.bind( this ) ).fail( function() {
			this.emit( 'error' );
		}.bind( this ) );
	};

	workflows.ui.WorkflowMultiselect.prototype.setValidityFlag = function( valid ) {
		if ( valid ) {
			this.$element.removeClass( 'oo-ui-flaggedElement-invalid' );
		} else {
			this.$element.addClass( 'oo-ui-flaggedElement-invalid' );
		}
	};

	workflows.ui.WorkflowMultiselect.prototype.isSelected = function( data ) {
		if ( !this.selectedValue ) {
			return false;
		}
		for( var i = 0; i < this.selectedValue.length; i++ ) {
			var def = this.selectedValue[i];
			if ( data.data.workflow.repo === def.repo && data.data.workflow.workflow === def.workflow ) {
				return true;
			}
		}

		return false;
	};
} )( mediaWiki, jQuery, workflows );

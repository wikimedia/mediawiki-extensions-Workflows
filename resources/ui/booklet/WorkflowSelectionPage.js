( function ( mw, $, wf ) {
	workflows.ui.WorkflowSelectionPage = function( name, cfg ) {
		workflows.ui.WorkflowSelectionPage .parent.call( this, name, cfg );

		this.value = null;
		this.panel = new OO.ui.PanelLayout();
		this.picker = new workflows.ui.widget.ManualTriggerPicker( {} );
		this.picker.connect( this, {
			error: function( error ) {
				this.emit( 'error', error );
			}
		} );
		this.picker.getMenu().connect( this, {
			select: function( item ) {
				this.value = null;
				if ( item ) {
					this.value = item.getData();
					this.emit( 'workflowSelected', this.value );
				}
			}
		} );

		this.$element.append( new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-starter-select-workflow' ).text()
		} ).$element );
		this.$element.append( this.picker.$element );
	};

	OO.inheritClass( workflows.ui.WorkflowSelectionPage, OO.ui.PageLayout );

	workflows.ui.WorkflowSelectionPage.prototype.reset = function() {
		this.picker.getMenu().selectItem( null );
	};

	workflows.ui.WorkflowSelectionPage.prototype.getWorkflow = function() {
		return this.value.workflow;
	};

	workflows.ui.WorkflowSelectionPage.prototype.getDescription = function() {
		return this.value.desc || '';
	};

	workflows.ui.WorkflowSelectionPage.prototype.getInitialData = function() {
		return this.value.initData || {};
	};

} )( mediaWiki, jQuery, workflows );

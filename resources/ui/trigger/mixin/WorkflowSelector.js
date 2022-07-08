workflows.ui.trigger.mixin.WorkflowSelector = function( cfg ) {
	this.definition = null;
	this.repository = null;
	this.validateInitializer = true;
};

OO.initClass( workflows.ui.trigger.mixin.WorkflowSelector );

workflows.ui.trigger.mixin.WorkflowSelector.prototype.loadInitializer = function() {
	this.emit( 'loading' );
	workflows.initiate.dryStartWorkflowOfType( this.repository, this.definition, {
		pageId: -1,
		revision: -1
	} )
	.done( function( activity ) {
		this.pickerLayout.$element.find( '.oojsplus-ui-expandable-panel' ).remove();
		if ( activity ) {
			activity.getForm( {
				buttons: [], properties: this.value.initData || {}
			} ).done( function( formObject ) {
				if ( formObject ) {
					this.initializer = formObject;
					formObject.$element.css( { 'padding-top': '0' } );
					this.pickerLayout.$element.append(
						new OOJSPlus.ui.widget.ExpandablePanel( {
							$content: new OO.ui.PanelLayout( {
								$content: formObject.$element,
								framed: true,
								expanded: false,
								padded: true
							} ).$element,
							label: mw.message( 'workflows-ui-trigger-init-form' ).text(),
							expanded: false,
							padded: false
						} ).$element
					);
					if ( !this.validateInitializer ) {
						var inputs = formObject.form.getItems()['inputs'] || {};
						for ( var key in inputs ) {
							if ( !inputs.hasOwnProperty( key ) ) {
								continue;
							}
							inputs[key].setRequired( false );
						}
					}
					formObject.connect( this, {
						submit: 'storeFormValue'
					} );
				}
				this.emit( 'loaded' );
			}.bind( this ) );
		} else {
			this.initializer = null;
			this.emit( 'loaded' );
		}
	}.bind( this ) ).fail( function( error ) {
		console.error( error );
		this.emit( 'fail' );
	}.bind( this ) );
};

workflows.ui.trigger.mixin.WorkflowSelector.prototype.initWorkflowPicker = function() {
	this.picker = new workflows.ui.WorkflowPickerWidget( {
		required: true, $overlay: true, checkAllowed: false, value: {
			repo: this.value.repository || '',
			workflow: this.value.definition || ''
		}
	} );

	this.picker.connect( this, {
		error: function( error ) {
			this.emit( 'error', error );
		}
	} );

	this.picker.getMenu().connect( this, {
		select: function( item ) {
			if ( item ) {
				var data = item.getData();
				this.definition = data.workflow.workflow;
				this.repository = data.workflow.repo;
				this.loadInitializer();
			}
		}
	} );

	this.pickerLayout = new OO.ui.FieldLayout( this.picker, {
		align: 'top',
		label: mw.message( 'workflows-ui-trigger-field-workflow-picker' ).text()
	} );
};

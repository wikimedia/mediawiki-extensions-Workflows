workflows.ui.trigger.mixin.WorkflowSelector = function ( cfg ) {
	cfg = cfg || {};
	this.definition = null;
	this.repository = null;
	this.useRawProperties = cfg.useRawProperties || false;
	this.$overlay = cfg.$overlay || true;
};

OO.initClass( workflows.ui.trigger.mixin.WorkflowSelector );

workflows.ui.trigger.mixin.WorkflowSelector.prototype.loadInitializer = function () {
	this.emit( 'loading' );
	workflows.initiate.dryStartWorkflowOfType( this.repository, this.definition, {
		pageId: -1,
		revision: -1
	} )
		.done( ( activity ) => {
			this.pickerLayout.$element.find( '.oojsplus-ui-expandable-panel' ).remove();
			if ( activity ) {
				const properties = this.useRawProperties ? activity.getRawProperties() : activity.getProperties();
				const data = $.isEmptyObject( this.value.initData ) || this.value.initData.length === 0 ?
					properties : this.value.initData;
				activity.getForm( {
					buttons: [], properties: data
				} ).done( ( formObject ) => {
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
						const inputs = formObject.form.getItems().inputs || {};
						for ( const key in inputs ) {
							if ( !inputs.hasOwnProperty( key ) ) {
								continue;
							}
							if ( typeof inputs[ key ].setRequired === 'function' ) {
								inputs[ key ].setRequired( false );
							}
							if ( typeof inputs[ key ].setValidation === 'function' ) {
								inputs[ key ].setValidation( null );
							}
						}
						formObject.connect( this, {
							submit: 'storeFormValue'
						} );
					}
					this.emit( 'loaded' );
				} );
			} else {
				this.initializer = null;
				this.emit( 'loaded' );
			}
		} ).fail( ( error ) => {
			console.error( error ); // eslint-disable-line no-console
			this.emit( 'fail' );
		} );
};

workflows.ui.trigger.mixin.WorkflowSelector.prototype.initWorkflowPicker = function () {
	this.picker = new workflows.ui.WorkflowPickerWidget( {
		required: true, $overlay: this.$overlay, checkAllowed: false, value: {
			repo: this.value.repository || '',
			workflow: this.value.definition || ''
		}
	} );

	this.picker.connect( this, {
		error: function ( error ) {
			this.emit( 'error', error );
		}
	} );

	this.picker.getMenu().connect( this, {
		select: function ( item ) {
			if ( item ) {
				const data = item.getData();
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

( function ( mw ) {
	workflows.ui.WorkflowStartPage = function ( name, cfg ) {
		workflows.ui.WorkflowStartPage.parent.call( this, name, cfg );
		this.form = null;
		this.$overlay = cfg.$overlay || true;
	};

	OO.inheritClass( workflows.ui.WorkflowStartPage, OO.ui.PageLayout );

	workflows.ui.WorkflowStartPage.prototype.initWorkflow = function ( workflow, data, desc, initData ) {
		data = data || {};
		this.workflowSource = workflow;
		this.startData = data;
		this.workflowTitle = new OO.ui.LabelWidget( { label: '', classes: [ 'workflows-ui-starter-wf-title-label' ] } );
		this.workflowDesc = new OO.ui.LabelWidget( { label: '', classes: [ 'workflows-ui-starter-wf-title-desc' ] } );
		workflows.initiate.getDefinitionDetails( workflow.repository, workflow.definition ).done( ( details ) => {
			if ( details.hasOwnProperty( 'title' ) ) {
				this.workflowTitle.setLabel( details.title );
			}
			if ( details.hasOwnProperty( 'desc' ) ) {
				this.workflowDesc.setLabel( details.desc );
			}
		} );

		workflows.initiate.dryStartWorkflowOfType( this.workflowSource.repository, this.workflowSource.definition, this.startData, initData )
			.done( ( activity ) => {
				if ( activity ) {
					activity.getForm( { buttons: [], $overlay: this.$overlay } ).done( ( formObject ) => {
						this.$element.append( this.workflowTitle.$element );
						this.$element.append( this.workflowDesc.$element );
						this.$element.append(
							new OO.ui.LabelWidget( {
								label: mw.message( 'workflows-ui-starter-init-note' ).text()
							} ).$element
						);
						this.$element.append( formObject.$element );
						formObject.connect( this, {
							submit: 'startWorkflow',
							validationFailed: 'validationFailed',
							initComplete: function () {
								this.adjustSize( formObject );
							},
							layoutChange: function () {
								this.adjustSize( formObject );
							}
						} );

						// Form might load before we get the change to register "initComplete" handler
						this.adjustSize( formObject );
						this.emit( 'loaded', formObject );
						this.form = formObject;
					} );
				} else {
					this.form = null;
					this.$element.append( this.workflowTitle.$element );
					this.$element.append( this.workflowDesc.$element );
					this.$element.height(
						this.workflowTitle.$element.outerHeight() + this.workflowDesc.$element.outerHeight() + 40
					);
					this.emit( 'loaded' );
					this.emit( 'layoutChange' );
				}
			} ).fail( ( error ) => {
				const message = ( error.hasOwnProperty( 'error' ) && error.error.hasOwnProperty( 'message' ) ) ?
					error.error.message : mw.message( 'workflows-ui-starter-error-generic' ).text();
				this.$element.html( new OO.ui.MessageWidget( {
					type: 'error',
					label: message
				} ).$element );
				this.$element.height( 130 );
				this.emit( 'fail' );
			} );
	};

	workflows.ui.WorkflowStartPage.prototype.reset = function () {
		this.$element.children().remove();
		this.form = null;
	};

	workflows.ui.WorkflowStartPage.prototype.adjustSize = function ( formObject ) {
		// Force page size to form size
		this.$element.height(
			formObject.$element.outerHeight() + this.workflowTitle.$element.outerHeight() +
			this.workflowDesc.$element.outerHeight() + 40
		);
		this.emit( 'layoutChange' );
	};

	workflows.ui.WorkflowStartPage.prototype.getForm = function () {
		return this.form;
	};

	workflows.ui.WorkflowStartPage.prototype.validationFailed = function () {
		this.emit( 'validationFailed' );
	};

	workflows.ui.WorkflowStartPage.prototype.startWorkflow = function ( initData ) {
		initData = initData || null;
		const data = {
			startData: this.startData
		};

		if ( initData ) {
			data.initData = initData;
		}

		workflows.initiate.startWorkflowOfType(
			this.workflowSource.repository,
			this.workflowSource.definition,
			data
		)
			.done( ( workflow ) => {
				this.emit( 'initCompleted', workflow );
			} ).fail( ( error ) => {
				if ( error.hasOwnProperty( 'error' ) && error.error.hasOwnProperty( 'message' ) ) {
					this.emit( 'initFailed', error.error.message );
				} else {
					this.emit( 'initFailed' );
				}
			} );
	};
}( mediaWiki ) );

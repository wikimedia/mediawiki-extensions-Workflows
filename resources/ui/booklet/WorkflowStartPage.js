( function ( mw, $, wf ) {
	workflows.ui.WorkflowStartPage = function( name, cfg ) {
		workflows.ui.WorkflowStartPage.parent.call( this, name, cfg );
		this.form = null;
	};

	OO.inheritClass( workflows.ui.WorkflowStartPage, OO.ui.PageLayout );

	workflows.ui.WorkflowStartPage.prototype.initWorkflow = function( workflow, data, desc, initData ) {
		data = data || {};
		this.workflowSource = workflow;
		this.startData = data;
		this.workflowTitle = new OO.ui.LabelWidget( { label: '', classes: [ 'workflows-ui-starter-wf-title-label' ] } );
		this.workflowDesc = new OO.ui.LabelWidget( { label: '', classes: [ 'workflows-ui-starter-wf-title-desc' ] } );
		workflows.initiate.getDefinitionDetails( workflow.repository, workflow.definition ).done( function ( details ) {
			if ( details.hasOwnProperty( 'title' ) ) {
				this.workflowTitle.setLabel( details.title );
			}
			if ( details.hasOwnProperty( 'desc' ) ) {
				this.workflowDesc.setLabel( details.desc );
			}
		}.bind( this ) );

		workflows.initiate.dryStartWorkflowOfType( this.workflowSource.repository, this.workflowSource.definition, this.startData )
			.done( function( activity ) {
				if ( activity ) {
					activity.getForm( { buttons: [], properties: initData } ).done( function( formObject ) {
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
							initComplete: function() {
								this.adjustSize( formObject );
							}
						} );

						// Form might load before we get the change to register "initComplete" handler
						this.adjustSize( formObject );
						this.emit( 'loaded', formObject );
						this.form = formObject;
					}.bind( this ) );
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
			}.bind( this ) ).fail( function( error ) {
				var message = ( error.hasOwnProperty( 'error' ) && error.error.hasOwnProperty( 'message' ) ) ?
					error.error.message : mw.message( 'workflows-ui-starter-error-generic' ).text();
				this.$element.html( new OO.ui.MessageWidget( {
					type: 'error',
					label: message
				} ).$element );
				this.$element.height( 130 );
				this.emit( 'fail' );
			}.bind( this ) );
	};

	workflows.ui.WorkflowStartPage.prototype.reset = function() {
		this.$element.children().remove();
		this.form = null;
	};

	workflows.ui.WorkflowStartPage.prototype.adjustSize = function( formObject ) {
		// Force page size to form size
		this.$element.height(
			formObject.$element.outerHeight() + this.workflowTitle.$element.outerHeight() +
			this.workflowDesc.$element.outerHeight() + 40
		);
		this.emit( 'layoutChange' );
	};

	workflows.ui.WorkflowStartPage.prototype.getForm = function() {
		return this.form;
	};

	workflows.ui.WorkflowStartPage.prototype.validationFailed = function() {
		this.emit( 'validationFailed' );
	};

	workflows.ui.WorkflowStartPage.prototype.startWorkflow = function( initData ) {
		initData = initData || null;
		var data = {
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
		.done( function( workflow ) {
			this.emit( 'initCompleted', workflow );
		}.bind( this ) ).fail( function( error ) {
			if ( error.hasOwnProperty( 'error' ) && error.error.hasOwnProperty( 'message' ) ){
				this.emit( 'initFailed', error.error.message );
			} else {
				this.emit( 'initFailed' );
			}
		}.bind( this ) );
	};
} )( mediaWiki, jQuery, workflows );

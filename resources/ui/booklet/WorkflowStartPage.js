( function ( mw, $, wf ) {
	workflows.ui.WorkflowStartPage = function( name, cfg ) {
		workflows.ui.WorkflowStartPage.parent.call( this, name, cfg );
		this.form = null;
		this.hasForm = false;
	};

	OO.inheritClass( workflows.ui.WorkflowStartPage, OO.ui.PageLayout );

	workflows.ui.WorkflowStartPage.prototype.initWorkflow = function( workflow, data, desc ) {
		data = data || {};
		this.workflowSource = workflow;
		this.startData = data;
		this.descLabel = new OO.ui.LabelWidget( { label: desc } );

		workflows.initiate.dryStartWorkflowOfType( this.workflowSource.repo, this.workflowSource.workflow, this.startData )
			.done( function( activity ) {
				if ( activity ) {
					this.hasForm = true;
					activity.getForm( { buttons: [] } ).done( function( formObject ) {
						this.$element.append( this.descLabel.$element );
						this.$element.append(
							new OO.ui.LabelWidget( {
								label: mw.message( 'workflows-ui-starter-init-note' ).text()
							} ).$element
						);
						this.$element.append( formObject.$element );
						// Force page size to form size
						this.$element.height( formObject.$element.outerHeight() + this.descLabel.$element.outerHeight() + 30 );
						formObject.connect( this, {
							submit: 'startWorkflow',
							validationFailed: 'validationFailed'
						} );
						this.emit( 'loaded', formObject );
						this.form = formObject;
					}.bind( this ) );
				} else {
					this.hasForm = false;
					this.$element.height( 130 );
					this.$element.append( this.descLabel.$element );
					this.emit( 'loaded' );
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
		this.hasForm = false;
		this.form = null;
	};

	workflows.ui.WorkflowStartPage.prototype.hasForm = function() {
		return this.hasForm && this.form !== null;
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
			this.workflowSource.repo,
			this.workflowSource.workflow,
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

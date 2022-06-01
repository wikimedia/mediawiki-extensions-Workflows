( function ( mw, $, wf ) {
	workflows.ui.dialog.WorkflowStarter = function( cfg ) {
		workflows.ui.dialog.WorkflowStarter.super.call( this, cfg );

		this.repos = cfg.repos || [];
		this.contextData = cfg.contextData || {};
		this.lastError = null;
	};

	OO.inheritClass( workflows.ui.dialog.WorkflowStarter, OO.ui.ProcessDialog );

	workflows.ui.dialog.WorkflowStarter.static.name = 'workflowStarter';
	workflows.ui.dialog.WorkflowStarter.static.title = mw.message( 'workflows-ui-starter-dialog-title' ).text();
	workflows.ui.dialog.WorkflowStarter.static.actions = [
		{
			action: 'choose',
			label: mw.message( 'workflows-ui-starter-action-choose' ).text(),
			flags: [ 'primary', 'progressive' ], modes: [ 'wfSelection' ]
		},
		{
			action: 'start', disabled: true,
			label: mw.message( 'workflows-ui-starter-action-done' ).text(),
			flags: [ 'primary', 'progressive' ], modes: [ 'init' ]
		},
		{
			action: 'back', disabled: true,
			label: mw.message( 'workflows-ui-starter-action-back' ).text(),
			flags: 'primary', modes: [ 'init' ]
		},
		{
			label: mw.message( 'workflows-ui-starter-action-cancel' ).text(),
			flags: 'safe', modes: [ 'wfSelection', 'init' ]
		}
	];

	workflows.ui.dialog.WorkflowStarter.prototype.getReadyProcess = function( data ) {
		return workflows.ui.dialog.WorkflowStarter.parent.prototype.getReadyProcess.call( this, data )
			.next( function() {
				this.actions.setAbilities( { back: false, choose: false, start: false } );
				this.switchPanel( 'wfSelection' );
			}, this );
	};

	workflows.ui.dialog.WorkflowStarter.prototype.getSetupProcess = function( data ) {
		return workflows.ui.dialog.WorkflowStarter.parent.prototype.getSetupProcess.call( this, data )
		.next( function() {
			// Prevent flickering, disable all actions before init is done
			this.actions.setMode( 'INVALID' );
		}, this );
	};

	workflows.ui.dialog.WorkflowStarter.prototype.initialize = function () {
		workflows.ui.dialog.WorkflowStarter.super.prototype.initialize.apply( this, arguments );


		this.booklet = new workflows.ui.WorkflowStartBooklet( {
			repos: this.repos,
			outlined: false,
			showMenu: false,
			expanded: true
		} );
		this.switchPanel( 'wfSelection' );

		this.$body.append( this.booklet.$element );
	};

	workflows.ui.dialog.WorkflowStarter.prototype.switchPanel = function ( name, data ) {
		var page = this.booklet.getPage( name );
		if ( !page ) {
			return;
		}
		this.actions.setMode( name );
		this.booklet.setPage( name );
		this.popPending();
		page.reset();

		switch ( name ) {
			case 'wfSelection':
				this.setSize( 'medium' );
				this.actions.setAbilities( { choose: false, start: false } );
				page.connect( this, {
					workflowSelected: function ( val ) {
						if ( val ) {
							this.actions.setAbilities( { back: false, choose: true, start: false } );
						} else {
							this.actions.setAbilities( { back: false, choose: false, start: false } );
						}
						this.updateSize();
					}
				} );
				this.updateSize();
				break;
			case 'init':
				this.setSize( 'large' );
				page.initWorkflow( data.workflow, data.contextData, data.desc, data.initData );
				page.connect( this, {
				 	loaded: function( form ) {
						this.actions.setAbilities( { choose: false, start: true, back: true } );
					},
					fail: function() {
				 		this.popPending();
						this.updateSize();
						this.actions.setAbilities( { choose: false, start: false, back: true } );
					},
					layoutChange: function() {
						this.popPending();
						this.updateSize();
					}
				} );
				break;
		}

	};

	workflows.ui.dialog.WorkflowStarter.prototype.showErrors = function( errors ) {
		workflows.ui.dialog.WorkflowStarter.parent.prototype.showErrors.call( this, errors );
		this.updateSize();
	};

	workflows.ui.dialog.WorkflowStarter.prototype.getActionProcess = function ( action ) {
		return workflows.ui.dialog.WorkflowStarter.parent.prototype.getActionProcess.call( this, action ).next(
			function() {
				if ( action === 'choose' ) {
					if ( this.toRetry === action ) {
						this.switchPanel( 'wfSelection' );
					} else {
						var selectedWorkflow = this.booklet.getCurrentPage().getWorkflow();
						if ( !selectedWorkflow ) {
							this.toRetry = 'choose';
							return new OO.ui.Error( 'Workflow not selected' );
						}
						this.pushPending();
						this.switchPanel( 'init', {
							workflow: selectedWorkflow,
							contextData: this.contextData,
							desc: this.booklet.getCurrentPage().getDescription(),
							initData: this.booklet.getCurrentPage().getInitialData()
						} );
					}
				}
				if ( action === 'start' ) {
					var page = this.booklet.getCurrentPage();
					if ( page.getName() === 'init' ) {
						var dfd = $.Deferred();
						this.pushPending();

						page.connect( this, {
							initCompleted: function( workflow ) {
								return this.close( { result: true, workflow: workflow } );
							},
							initFailed: function( error ) {
								this.popPending();
								this.lastError = 'init-failed';
								dfd.reject( new OO.ui.Error( error, {
									recoverable: false
								} ) );
							},
							validationFailed: function() {
								this.popPending();
								dfd.resolve();
							}
						} );

						var form = page.getForm();
						if ( form ) {
							if ( !( form instanceof workflows.object.form.Form ) ) {
								this.lastError = 'no-form';
								return new OO.ui.Error(
									mw.message( 'workflows-ui-starter-init-form-fail' ).text(), {
										recoverable: false
									}
								);
							}
							form.submit();
						} else {
							page.startWorkflow();
						}
						return dfd.promise();
					}

				}
				if ( action === 'back' ) {
					this.switchPanel( 'wfSelection' );
				}
			}, this
		);
	};

	workflows.ui.dialog.WorkflowStarter.prototype.reloadPage = function () {
		location.reload();
	};

	workflows.ui.dialog.WorkflowStarter.prototype.onDismissErrorButtonClick = function () {
		this.hideErrors();
		this.lastError = null;
		if ( this.lastError === 'no-form' ) {
			this.switchPanel( 'choose' );
		} else if ( this.booklet.getCurrentPage().getName() === 'init' ) {
			this.actions.setAbilities( { choose: false, start: true, back: true } );
		}

		this.updateSize();
	};

	workflows.ui.dialog.WorkflowStarter.prototype.getBodyHeight = function () {
		if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
			return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
		}
		if ( this.booklet.getCurrentPageName() === 'wfSelection' ) {
			return 100;
		}
		return this.$element.find( '.oo-ui-window-body' )[0].scrollHeight;
	};

} )( mediaWiki, jQuery, workflows );

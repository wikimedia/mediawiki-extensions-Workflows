( function ( mw, $ ) {
	workflows.ui.dialog.WorkflowOverview = function( workflow, overview ) {
		workflows.ui.dialog.WorkflowOverview.super.call( this, {
			size: 'large'
		} );

		this.overview = overview || null;
		this.workflow = workflow || null;
		this.initialized = false;
		this.mode = null;
		this.abilities = {};
	};

	OO.inheritClass( workflows.ui.dialog.WorkflowOverview, OO.ui.ProcessDialog );

	workflows.ui.dialog.WorkflowOverview.static.name = 'workflowOverview';
	workflows.ui.dialog.WorkflowOverview.static.title = '';

	workflows.ui.dialog.WorkflowOverview.static.actions = [
		{
			title: mw.message( 'workflows-ui-workflow-overview-action-cancel' ).text(),
			icon: 'close',
			action: 'cancel',
			flags: [ 'primary', 'progressive' ],
			modes: [ 'running', 'aborted', 'list', 'inactive' ]
		},
		{
			title: mw.message( 'workflows-ui-workflow-overview-action-back' ).text(),
			icon: 'previous',
			action: 'back',
			flags: 'safe',
			modes: [ 'abort', 'restore', 'running', 'aborted', 'inactive' ]
		},
		{
			action: 'abort-arm',
			label: mw.message( 'workflows-ui-workflow-overview-action-abort' ).text(),
			flags: 'destructive',
			modes: [ 'running' ]
		},
		{
			action: 'restore-arm',
			label: mw.message( 'workflows-ui-workflow-overview-action-restore' ).text(),
			flags: 'progressive',
			modes: [ 'aborted' ]
		},
		{
			action: 'abort',
			label: mw.message( 'workflows-ui-workflow-overview-action-abort' ).text(),
			flags: [ 'destructive', 'primary' ],
			modes: [ 'abort' ]
		},
		{
			action: 'restore',
			label: mw.message( 'workflows-ui-workflow-overview-action-restore' ).text(),
			flags: [ 'progressive', 'primary' ],
			modes: [ 'restore' ]
		}
	];

	workflows.ui.dialog.WorkflowOverview.prototype.getSetupProcess = function( data ) {
		return workflows.ui.dialog.WorkflowOverview.parent.prototype.getSetupProcess.call( this, data )
		.next( function() {
			if ( !this.initialized ) {
				// Prevent flickering, disable all actions before init is done
				this.actions.setMode( 'INVALID' );
			}
			if ( this.abilities ) {
				this.actions.setAbilities( this.abilities );
			}
			if ( this.mode ) {
				this.actions.setMode( this.mode );
			}

			if ( this.booklet ) {
				var page = this.booklet.getCurrentPage();
				this.title.setLabel( page.getTitle() );
			}
		}, this );
	};

	workflows.ui.dialog.WorkflowOverview.prototype.initialize = function () {
		workflows.ui.dialog.WorkflowOverview.super.prototype.initialize.apply( this, arguments );
		this.pushPending();

		mw.loader.using( 'ext.workflows.dialog.overview', function() {
			this.booklet = new workflows.ui.WorkflowOverviewBooklet( {
				outlined: false,
				showMenu: false,
				expanded: true,
				overview: this.overview
			} );
			this.$body.append( this.booklet.$element );
			this.updateSize();
			this.popPending();

			if ( this.workflow ) {
				this.switchPanel( 'details' );
			} else if ( this.overview !== null ) {
				this.switchPanel( 'list' );
			}

			this.initialized = true;
		}.bind( this ) );

	};

	workflows.ui.dialog.WorkflowOverview.prototype.switchPanel = function ( name, data ) {
		var page = this.booklet.getPage( name );
		if ( !page ) {
			return;
		}

		this.booklet.setPage( name );
		this.title.setLabel( page.getTitle() );

		switch ( name ) {
			case 'list':
				page.panel.connect( this, {
					loadStarted: function() {
						this.pushPending();
					},
					loaded: function() {
						this.popPending();
						this.updateSize();
					},
					loadFailed: function() {
						this.popPending();
					},
					gridRendered: function() {
						this.popPending();
						this.updateSize();
					},
					selected: function( id ) {
						this.pushPending();
						workflows.getWorkflow( id ).done( function( workflow ) {
							this.workflow = workflow;
							this.popPending();
							this.switchPanel( 'details' );
						}.bind( this ) ).fail( function() {
							this.popPending();
							return new OO.Error();
						}.bind( this ) );
					}
				} );
				this.setSize( 'larger' );
				this.actions.setMode( 'list' );
				this.mode = 'mode';
				var abilities = { 'abort-arm': false, abort: false, restore: false, 'restore-arm': false, cancel: true, back: false  };
				this.actions.setAbilities( abilities );
				this.abilities = abilities;
				break;
			case 'details':
				page.init( this.workflow );
				this.title.setLabel( page.getTitle() );

				this.setSize( 'large' );
				var mode = this.workflow.getState();
				if ( mode !== 'running' && mode !== 'aborted' ) {
					mode = 'inactive';
				}
				this.actions.setMode( mode );
				var abilities = { 'abort-arm': false, abort: false, restore: false, 'restore-arm': false, cancel: true, back: true  };
				if ( mode === 'running' ) {
					this.abilities = $.extend( abilities, { 'abort-arm': true } );
				}
				if ( mode === 'aborted' ) {
					var restorable = true;
					if ( typeof this.workflow.getStateMessage() === 'object' && this.workflow.getStateMessage().isRestorable === false ) {
						restorable = false;
					}
					this.abilities = $.extend( abilities, { 'restore-arm': restorable } );
				}

				if ( this.overview === null ) {
					abilities.back = false;
				}
				this.abilities = abilities;
				this.mode = mode;
				this.actions.setAbilities( abilities );
				break;
			case 'abortRestore':
				this.setSize( 'medium' );
				page.init( this.workflow, data.action );
				this.title.setLabel( page.getTitle() );
				this.actions.setMode( data.action );
				if ( data.action === 'abort' ) {
					this.actions.setAbilities( { 'abort-arm': false, abort: true, restore: false, 'restore-arm': false, cancel: false, back: true } );
				}
				if ( data.action === 'restore' ) {
					this.actions.setAbilities( { 'abort-arm': false, abort: false, restore: true, 'restore-arm': false, cancel: false, back: true } );
				}
				break;
		}

		this.updateSize();
	};

	workflows.ui.dialog.WorkflowOverview.prototype.getActionProcess = function ( action ) {
		return workflows.ui.dialog.WorkflowOverview.parent.prototype.getActionProcess.call( this, action ).next(
			function() {
				if ( action === 'abort-arm' ) {
					this.switchPanel( 'abortRestore', {
						action: 'abort'
					} );
				}
				if ( action === 'restore-arm' ) {
					this.switchPanel( 'abortRestore', {
						action: 'restore'
					} );
				}
				if ( action === 'abort' ) {
					this.pushPending();
					this.workflow.abort( this.booklet.getCurrentPage().getReason() )
					.done( function() {
					 	this.close( { action: action, workflow: this.workflow } );
					}.bind( this ) )
					.fail( function() {
						this.popPending();
						return new OO.Error();
					}.bind( this ) );

				}
				if ( action === 'restore' ) {
					this.pushPending();
					this.workflow.restore( this.booklet.getCurrentPage().getReason() )
					.done( function( workflow ) {
						this.close( { action: action, workflow: workflow } );
					}.bind( this ) )
					.fail( function() {
						this.popPending();
						return new OO.Error();
					}.bind( this ) );
				}
				if ( action === 'cancel' ) {
					this.close();
				}
				if ( action === 'back' ) {
					if ( this.booklet.getCurrentPageName() === 'details' && this.overview !== null ) {
						this.switchPanel( 'list' );
					} else {
						this.switchPanel( 'details' );
					}
				}
			}, this
		);
	};

	workflows.ui.dialog.WorkflowOverview.prototype.getBodyHeight = function () {
		if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
			return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
		}
		return this.$element.find( '.oo-ui-window-body' )[0].scrollHeight;
	};

} )( mediaWiki, jQuery );

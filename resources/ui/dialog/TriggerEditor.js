( function ( mw, $, wf ) {
	workflows.ui.dialog.TriggerEditor = function( cfg ) {
		workflows.ui.dialog.TriggerEditor.super.call( this, cfg );
		this.triggerData = cfg.triggerData || null;
		this.allData = cfg.allData || {};
		this.adaptDataForEditor();
	};

	OO.inheritClass( workflows.ui.dialog.TriggerEditor, OO.ui.ProcessDialog );

	workflows.ui.dialog.TriggerEditor.static.name = 'triggerEditor';
	workflows.ui.dialog.TriggerEditor.static.title = mw.message( 'workflows-ui-trigger-editor-dialog-title' ).text();
	workflows.ui.dialog.TriggerEditor.static.actions = [
		{
			action: 'choose',
			label: mw.message( 'workflows-ui-trigger-action-choose' ).text(),
			flags: [ 'primary', 'progressive' ], modes: [ 'triggerTypeSelection' ]
		},
		{
			action: 'create', disabled: true,
			label: mw.message( 'workflows-ui-trigger-action-create' ).text(),
			flags: [ 'primary', 'progressive' ], modes: [ 'triggerDetails' ]
		},
		{
			label: mw.message( 'workflows-ui-action-cancel' ).text(),
			flags: 'safe', modes: [ 'triggerTypeSelection', 'triggerDetails' ]
		}
	];

	workflows.ui.dialog.TriggerEditor.prototype.adaptDataForEditor = function() {
		for ( var triggerId in this.allData ) {
			if ( !this.allData.hasOwnProperty( triggerId ) ) {
				continue;
			}
			delete( this.allData[triggerId].name_parsed );
			delete ( this.allData[triggerId].description_parsed );
		}
		if ( this.triggerData !== null ) {
			delete( this.triggerData.name_parsed );
			delete ( this.triggerData.description_parsed );
		}
	};

	workflows.ui.dialog.TriggerEditor.prototype.getReadyProcess = function( data ) {
		return workflows.ui.dialog.TriggerEditor.parent.prototype.getReadyProcess.call( this, data )
			.next( function() {
				this.actions.setAbilities( { back: false, choose: false, create: false } );
				if ( this.triggerData ) {
					this.switchPanel( 'triggerDetails', this.triggerData );
					this.updateSize();
				} else {
					this.switchPanel( 'triggerTypeSelection' );
				}

			}, this );
	};

	workflows.ui.dialog.TriggerEditor.prototype.getSetupProcess = function( data ) {
		return workflows.ui.dialog.TriggerEditor.parent.prototype.getSetupProcess.call( this, data )
		.next( function() {
			// Prevent flickering, disable all actions before init is done
			this.actions.setMode( 'INVALID' );

			this.updateSize();
		}, this );
	};

	workflows.ui.dialog.TriggerEditor.prototype.initialize = function () {
		workflows.ui.dialog.TriggerEditor.super.prototype.initialize.apply( this, arguments );


		this.booklet = new workflows.ui.TriggerEditorBooklet( {
			triggerData: this.triggerData,
			outlined: false,
			showMenu: false,
			expanded: true
		} );

		this.$body.append( this.booklet.$element );
	};

	workflows.ui.dialog.TriggerEditor.prototype.switchPanel = function ( name, data ) {
		var page = this.booklet.getPage( name );
		if ( !page ) {
			return;
		}
		this.actions.setMode( name );
		this.booklet.setPage( name );
		page.reset();

		switch ( name ) {
			case 'triggerTypeSelection':
				this.setSize( 'medium' );
				this.actions.setAbilities( { choose: false, create: false } );
				page.init();
				page.connect( this, {
					loaded: function() {
						this.popPending();
					},
					triggerSelected: function ( val ) {
						if ( val ) {
							this.actions.setAbilities( { choose: true, create: false } );
						} else {
							this.actions.setAbilities( { choose: false, create: false } );
						}
						this.updateSize();
					}
				} );
				this.updateSize();
				break;
			case 'triggerDetails':
				this.popPending();
				this.setSize( 'large' );
				this.actions.setAbilities( { choose: false, create: true } );
				page.init( data );
				page.connect( this, {
					loading: function() {
						this.actions.setAbilities( { choose: false, create: false } );
						this.pushPending();
					},
					loaded: function() {
						this.popPending();
						this.updateSize();
						this.actions.setAbilities( { choose: false, create: true } );
					},
					sizeChange: function() {
						this.updateSize();
					}
				} );
				break;
		}
		this.updateSize();

	};

	workflows.ui.dialog.TriggerEditor.prototype.showErrors = function( errors ) {
		workflows.ui.dialog.TriggerEditor.parent.prototype.showErrors.call( this, errors );
		this.updateSize();
	};

	workflows.ui.dialog.TriggerEditor.prototype.getActionProcess = function ( action ) {
		return workflows.ui.dialog.TriggerEditor.parent.prototype.getActionProcess.call( this, action ).next(
			function() {
				if ( action === 'choose' ) {
					if ( this.toRetry === action ) {
						this.switchPanel( 'triggerTypeSelection' );
					} else {
						var selectedTrigger = this.booklet.getCurrentPage().getTriggerKey();
						if ( !selectedTrigger ) {
							this.toRetry = 'choose';
							return new OO.ui.Error( mw.message( 'workflows-ui-trigger-choose-error' ).text() );
						}
						this.pushPending();
						this.switchPanel( 'triggerDetails', {
							type: this.booklet.getCurrentPage().getTriggerKey(),
							editor: this.booklet.getCurrentPage().getEditor(),
							label: this.booklet.getCurrentPage().getLabel(),
							desc: this.booklet.getCurrentPage().getDesc(),
							value: {}
						} );
					}
				}
				if ( action === 'create' ) {
					var dfd = $.Deferred();
					this.pushPending();
					this.booklet.getCurrentPage().getValidity().done( function() {
						this.booklet.getCurrentPage().getValue().done( function( data ) {
							this.persist( data ).done( function() {
								window.location.reload();
							} ).fail( function( error ) {
								dfd.reject( error );
							} );
						}.bind( this ) ).fail( function() {
							this.popPending();
							dfd.resolve();
						}.bind( this ) );
					}.bind( this ) ).fail( function() {
						this.popPending();
						dfd.resolve();
					}.bind( this ) );
					return dfd.promise();
				}
			}, this
		);
	};

	workflows.ui.dialog.TriggerEditor.prototype.persist = function ( data ) {
		var dfd = $.Deferred();
		var trigger = data;
		if ( !trigger ) {
			dfd.reject( mw.message( 'workflows-ui-triggers-error-persist-fail' ).text() );
			return dfd.promise();
		}

		var id = trigger.id;
		delete( trigger.id );
		// If we are editing an existing trigger, and remove its name, we need to remove all old data
		// since name is also the unique trigger key
		if ( this.triggerData && this.allData.hasOwnProperty( this.triggerData.value.id ) ) {
			delete( this.allData[this.triggerData.value.id] );
		}
		this.allData[id] = trigger;
		workflows.triggers.persist( this.allData ).done( function() {
			dfd.resolve();
		} ).fail( function() {
			dfd.reject( mw.message( 'workflows-ui-triggers-error-persist-fail' ).text() );
		} );

		return dfd.promise();
	};

	workflows.ui.dialog.TriggerEditor.prototype.onDismissErrorButtonClick = function () {
		this.hideErrors();
		this.updateSize();
	};

	workflows.ui.dialog.TriggerEditor.prototype.getBodyHeight = function () {
		if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
			return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
		}
		if ( this.booklet.getCurrentPageName() === 'triggerTypeSelection' ) {
			return 100;
		}
		return this.$element.find( '.oo-ui-window-body' )[0].scrollHeight;
	};

} )( mediaWiki, jQuery, workflows );


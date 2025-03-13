( function ( mw, $ ) {
	workflows.ui.dialog.TaskCompletion = function ( workflow, activity ) {
		workflows.ui.dialog.TaskCompletion.super.call( this, {} );

		this.workflow = workflow;
		this.activity = activity;
	};

	OO.inheritClass( workflows.ui.dialog.TaskCompletion, OO.ui.ProcessDialog );

	workflows.ui.dialog.TaskCompletion.static.name = 'taskCompletion';
	workflows.ui.dialog.TaskCompletion.static.title = mw.message( 'workflows-ui-task-completion-dialog-title' ).text();
	workflows.ui.dialog.TaskCompletion.static.actions = [
		{
			action: 'complete',
			label: mw.message( 'workflows-ui-task-completion-action-complete' ).text(),
			flags: [ 'primary', 'progressive' ]
		},
		{
			label: mw.message( 'workflows-ui-task-completion-action-cancel' ).text(),
			action: 'cancel',
			flags: 'safe'
		}
	];

	workflows.ui.dialog.TaskCompletion.prototype.initialize = function () {
		workflows.ui.dialog.TaskCompletion.static.title = this.activity.getName();
		workflows.ui.dialog.TaskCompletion.super.prototype.initialize.apply( this, arguments );

		this.panel = new OO.ui.PanelLayout( {
			expanded: true,
			padded: true
		} );
		this.activity.getForm( { buttons: [], $overlay: this.$overlay } ).done( ( form ) => {
			this.form = form;
			this.form.getForm().connect( this, {
				layoutChange: function () {
					this.updateSize();
				}
			} );
			this.form.$element.addClass( 'nopadding' );
			this.panel.$element.append( this.form.$element );
			this.updateSize();
		} );
		this.$body.append( this.panel.$element );
	};

	workflows.ui.dialog.TaskCompletion.prototype.getActionProcess = function ( action ) {
		return workflows.ui.dialog.TaskCompletion.parent.prototype.getActionProcess.call( this, action ).next(
			function () {
				if ( action === 'complete' ) {
					this.pushPending();
					const dfd = $.Deferred();
					this.form.connect( this, {
						submit: function ( data ) {
							this.activity.complete( data ).done( ( task ) => { // eslint-disable-line no-unused-vars
								this.close( { result: true } );
							} ).fail( ( error ) => {
								dfd.reject( new OO.ui.Error( error, { recoverable: false } ) );
							} );
						},
						validationFailed: function () {
							this.popPending();
							dfd.resolve();
						}
					} );
					this.form.submit();

					return dfd.promise();
				}
				if ( action === 'cancel' ) {
					this.close();
				}
			}, this
		);
	};

	workflows.ui.dialog.TaskCompletion.prototype.showErrors = function ( errors ) {
		workflows.ui.dialog.TaskCompletion.parent.prototype.showErrors.call( this, errors );
		this.updateSize();
	};

	workflows.ui.dialog.TaskCompletion.prototype.hideErrors = function () {
		workflows.ui.dialog.TaskCompletion.parent.prototype.hideErrors.call( this );
		this.close( { result: false } );
	};

	workflows.ui.dialog.TaskCompletion.prototype.getBodyHeight = function () {
		if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
			return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
		}

		return this.$element.find( '.oo-ui-window-body' )[ 0 ].scrollHeight + 10;
	};

}( mediaWiki, jQuery ) );

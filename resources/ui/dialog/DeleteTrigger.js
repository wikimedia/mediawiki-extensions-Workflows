( function ( mw, $, wf ) {
	workflows.ui.dialog.DeleteTrigger = function( cfg ) {
		workflows.ui.dialog.DeleteTrigger.super.call( this, cfg );
		this.key = cfg.key;
	};

	OO.inheritClass( workflows.ui.dialog.DeleteTrigger, OO.ui.ProcessDialog );

	workflows.ui.dialog.DeleteTrigger.static.name = 'deleteTrigger';
	workflows.ui.dialog.DeleteTrigger.static.title = mw.message( 'workflows-ui-trigger-delete-dialog-title' ).text();
	workflows.ui.dialog.DeleteTrigger.static.actions = [
		{
			action: 'delete',
			label: mw.message( 'workflows-ui-trigger-action-delete' ).text(),
			flags: [ 'primary', 'destructive' ]
		},
		{
			label: mw.message( 'workflows-ui-action-cancel' ).text(),
			flags: 'safe'
		}
	];

	workflows.ui.dialog.DeleteTrigger.prototype.initialize = function () {
		workflows.ui.dialog.DeleteTrigger.super.prototype.initialize.apply( this, arguments );

		var notice = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-trigger-delete-prompt', this.key ).text()
		} );
		var panel = new OO.ui.PanelLayout( {
			$content: notice.$element,
			padded: true
		} );

		this.$body.append( panel.$element );
	};

	workflows.ui.dialog.DeleteTrigger.prototype.showErrors = function( errors ) {
		workflows.ui.dialog.DeleteTrigger.parent.prototype.showErrors.call( this, errors );
		this.updateSize();
	};

	workflows.ui.dialog.DeleteTrigger.prototype.getActionProcess = function ( action ) {
		return workflows.ui.dialog.DeleteTrigger.parent.prototype.getActionProcess.call( this, action ).next(
			function() {
				if ( action === 'delete' ) {
					var dfd = $.Deferred();
					this.pushPending();
					workflows.triggers.delete( this.key ).done( function() {
						window.location.reload();
					} ).fail( function() {
						this.popPending();
						dfd.reject( mw.message( 'workflows-ui-triggers-error-delete-fail' ).text() );
					}.bind( this ) );
					return dfd.promise();
				}
			}, this
		);
	};

	workflows.ui.dialog.DeleteTrigger.prototype.onDismissErrorButtonClick = function () {
		this.hideErrors();
		this.updateSize();
	};

	workflows.ui.dialog.DeleteTrigger.prototype.getBodyHeight = function () {
		if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
			return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
		}
		return 80;
	};

} )( mediaWiki, jQuery, workflows );

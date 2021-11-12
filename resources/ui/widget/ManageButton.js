( function( mw, $ ) {
	workflows.ui.widget.ManageButton = function( forInitiator ) {
		workflows.ui.widget.ManageButton.parent.call( this );

		this.tryAddButton( forInitiator );
		this.$element.addClass( 'workflows-manage-button' );
	};

	OO.inheritClass( workflows.ui.widget.ManageButton, OO.ui.Widget );

	workflows.ui.widget.ManageButton.prototype.tryAddButton = function( forInitiator ) {
		workflows.userCan( 'workflows-admin' ).done( function() {
			this.appendButton( 'admin' );
		}.bind( this ) ).fail( function() {
			if ( forInitiator ) {
				this.appendButton( 'initiator' );
			}
		}.bind( this ) );
	};

	workflows.ui.widget.ManageButton.prototype.appendButton = function( role ) {
		var button = new OO.ui.ButtonWidget( {
			icon: 'settings',
			framed: false,
			data: {
				role: role
			}
		} );
		button.connect( this, {
			click: function() {
				this.emit( 'click', button.getData() );
			}
		} );
		this.$element.append( button.$element );
	};
} )( mediaWiki, jQuery );

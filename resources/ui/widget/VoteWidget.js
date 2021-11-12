( function( mw, $ ) {
	workflows.ui.widget.Vote = function( cfg ) {
		cfg = cfg || {};
		workflows.ui.widget.Vote.parent.call( this, cfg );
		this.$input.remove();

		this.required = cfg.required || false;
		this.makeVoteLayout();
		this.value = '';
		this.$element.addClass( 'workflows-widget-vote' );
	};

	OO.inheritClass( workflows.ui.widget.Vote, OO.ui.InputWidget );

	workflows.ui.widget.Vote.static.icons = {
		yes: 'accept-icon',
		no: 'decline-icon'
	};

	workflows.ui.widget.Vote.prototype.makeVoteLayout = function() {
		this.icon = new OO.ui.IconWidget( {
			classes: [ 'vote-icon' ],
			icon: 'circleHelp'
		} );
		this.voteButtons = new OO.ui.ButtonSelectWidget( {
			classes: [ 'vote-button-group' ],
			items: [
				new OO.ui.ButtonOptionWidget( {
					data: 'yes',
					label: mw.message( 'workflows-form-label-vote-widget-approve' ).text(),
					classes: [ 'vote-yes' ]
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'no',
					label: mw.message( 'workflows-form-label-vote-widget-decline' ).text(),
					classes: [ 'vote-no' ]
				} ),
			]
		} );

		this.voteButtons.connect( this, {
			select: 'onVote'
		} );

		this.voteLayout = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false
		} );

		this.voteLayout.$element.append(
			this.icon.$element,
			this.voteButtons.$element
		);
		this.$element.append( this.voteLayout.$element );
	};

	workflows.ui.widget.Vote.prototype.onVote = function( item ) {
		if ( !item ) {
			return;
		}
		this.setValidityFlag( true );
		this.icon.setIcon( workflows.ui.widget.Vote.static.icons[item.getData()] );
		this.value = item.getData();
	};

	workflows.ui.widget.Vote.prototype.setRequired = function( required ) {
		this.required = required;
	};

	workflows.ui.widget.Vote.prototype.getValue = function() {
		return this.value;
	};

	workflows.ui.widget.Vote.prototype.getValidity = function() {
		var dfd = $.Deferred();
		if ( !this.required ) {
			dfd.resolve();
		}
		if ( this.value === 'yes' || this.value === 'no' ) {
			this.setValidityFlag( true );
			dfd.resolve();
		} else {
			this.setValidityFlag( false );
			dfd.reject();
		}
		return dfd.promise();
	};

	workflows.ui.widget.Vote.prototype.setValidityFlag = function( valid ) {
		if ( !valid ) {
			this.$element.addClass( 'invalid' );
		} else {
			this.$element.removeClass( 'invalid' );
		}
	};

	workflows.ui.widget.Vote.prototype.setValue = function( value ) {
		if ( !value ) {
			if ( this.voteButtons && this.icon ) {
				// Deselects everything
				this.voteButtons.selectItemByData();
				this.icon.setIcon( 'circleHelp' );
			}
			return;
		}

		if ( value === 'yes' || value === 'no' ) {
			this.value = value;
		}
	};
} )( mediaWiki, jQuery );

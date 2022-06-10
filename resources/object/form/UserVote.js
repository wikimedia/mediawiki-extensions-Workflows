( function ( mw, $ ) {

	workflows.object.form.UserVote = function( cfg, activity ) {
		var properties = activity.getProperties();
		this.allowDelegation = properties.hasOwnProperty( 'allow_delegation' ) ? !!properties.allow_delegation : true;
		workflows.object.form.UserVote.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.UserVote, workflows.object.form.Form );

	workflows.object.form.UserVote.prototype.getDefinitionItems = function() {
		return [
			{
				name: 'instructions',
				noLayout: true,
				type: 'static_wikitext',
				widget_loadingText: mw.message( 'workflows-form-instructions-loading-text' ).text()
			},
			{
				name: 'vote',
				noLayout: true,
				type: 'vote_widget',
				required: true
			},
			{
				name: 'comment',
				type: 'textarea',
				noLayout: true,
				placeholder: mw.message( 'workflows-form-placeholder-comment' ).text()
			},
			{
				name: 'delegate_button',
				type: 'button',
				noLayout: true,
				widget_label: mw.message( 'workflows-form-label-delegate' ).text(),
				widget_framed: false,
				widget_flags: 'progressive',
				hidden: !this.allowDelegation,
				style: 'float: right;',
				listeners: {
					click: function() {
						this.showDelegate();
					}.bind( this )
				}
			},
			{
				name: 'delegate_to',
				type: 'user_picker',
				label: mw.message( 'workflows-form-label-delegate-to' ).text(),
				hidden: true,
			},
			{
				name: 'delegate_comment',
				type: 'textarea',
				label: mw.message( 'workflows-form-label-delegate-comment' ).text(),
				hidden: true
			},
			{
				name: 'cancel_delegate',
				type: 'button',
				hidden: true,
				noLayout: true,
				widget_label: mw.message( 'workflows-form-label-delegate-cancel' ).text(),
				widget_framed: false,
				widget_flags: 'progressive',
				style: 'float: right;',
				listeners: {
					click: function() {
						this.showVote();
					}.bind( this )
				}
			},
			{
				name: 'action',
				type: 'text',
				value: 'vote',
				hidden: true,
			},
			{
				name: 'assigned_user',
				hidden: true,
				type: 'text'
			}
		];
	};

	workflows.object.form.UserVote.prototype.showDelegate = function() {
		this.form.getItem( 'action' ).setValue( 'delegate' );
		this.form.getItem( 'delegate_to' ).setRequired( true );
		this.form.getItem( 'vote' ).setRequired( false );

		this.form.hideItem( 'vote' );
		this.form.hideItem( 'comment' );
		this.form.hideItem( 'delegate_button' );
		this.form.hideItem( 'instructions' );

		this.form.showItem( 'delegate_to' );
		this.form.showItem( 'cancel_delegate' );
		this.form.showItem( 'delegate_comment' );
		this.form.emit( 'layoutChange' );
	};

	workflows.object.form.UserVote.prototype.showVote = function() {
		this.form.getItem( 'action' ).setValue( 'vote' );
		this.form.getItem( 'delegate_to').setRequired( false );
		this.form.getItem( 'vote' ).setRequired( true );

		this.form.showItem( 'vote' );
		this.form.showItem( 'comment' );
		this.form.showItem( 'delegate_button' );
		this.form.showItem( 'instructions' );

		this.form.hideItem( 'cancel_delegate' );
		this.form.hideItem( 'delegate_to' );
		this.form.hideItem( 'delegate_comment' );
		this.form.emit( 'layoutChange' );
	};

	workflows.object.form.UserVote.prototype.onInitComplete = function( form ) {
		// Update size of the window once wikitext is parsed
		form.getItem( 'instructions' ).connect( this, {
			parseComplete: function() {
				form.emit( 'layoutChange' );
			}
		} );

		var delegateTo = form.getItem( 'delegate_to' ).getValue();
		if ( !delegateTo ) {
			return;
		}

		if ( delegateTo && delegateTo === form.getItem( 'assigned_user' ).getValue() ) {
			// Re-delegation happened
			form.getItem( 'delegate_to' ).setValue( '' );
		} else if ( delegateTo ) {
			// Force re-delegation only
			form.getItem( 'delegate_to' ).setValue( form.getItem( 'assigned_user' ).getValue() );
			form.getItem( 'delegate_to' ).setDisabled( true );
		}

		var delegateComment = form.getItem( 'delegate_comment' ).getValue(),
			instructions = form.getItem( 'instructions' ).getWikitext();
		// If delegate comment is set, remove it, so that this user has empty comment field
		// and add this comment to the instructions set
		form.getItem( 'delegate_comment' ).setValue( '' );
		var newText = ( instructions ? instructions + "\n\n" : '' ) + this.getDelegateHeader( form, delegateComment );
		form.getItem( 'instructions' ).setValue( newText );
	};

	workflows.object.form.UserVote.prototype.getDelegateHeader = function( form, comment ) {
		var assignedUser = form.getItem( 'assigned_user' ).getValue();
		if ( comment ) {
			return mw.message( 'workflows-form-delegate-header-comment', assignedUser, comment ).text();
		}
		return mw.message( 'workflows-form-delegate-header', assignedUser ).text();
	};

	workflows.object.form.UserVote.prototype.onBeforeSubmitData = function( form, data ) {
		var dfd = $.Deferred();
		//Clean up data
		if ( !data.hasOwnProperty( 'vote' ) || ( data.action !== 'vote' && data.action !== 'delegate' ) ) {
			// In case of some invalid action set it to 'vote'
			data.action = 'vote';
		}
		if ( data.action === 'delegate' ) {
			delete( data.vote );
			delete( data.comment );
		}
		if ( data.action === 'vote' ) {
			delete( data.delegate_to );
			delete( data.delegate_comment );
		}

		dfd.resolve( data );
		return dfd.promise();
	};
} )( mediaWiki, jQuery );

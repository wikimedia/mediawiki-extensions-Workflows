( function ( mw, $ ) {
	workflows.object.UserInteractiveActivity = function( cfg, workflow ) {
		workflows.object.UserInteractiveActivity.parent.call( this, cfg, workflow );

		this.initializer = cfg.isInitializer || false;
		this.targetUsers = cfg.targetUsers || [];
		this.description = cfg.description || {};
		this.name = cfg.description.name || this.id;
		this.history = cfg.history || {};
		this.alertMessage = this.description.alertMessage || '';
		this.completeButtonMessage = this.description.completeButtonMessage || '';
		this.userInteractionModule = new workflows.object.UserInteractionModule( cfg.userInteractionModule );
		this.displayData = cfg.displayData || {};
	};

	OO.inheritClass( workflows.object.UserInteractiveActivity, workflows.object.Activity );

	workflows.object.UserInteractiveActivity.prototype.getForm = function( formCfg ) {
		formCfg = formCfg || {};
		formCfg = $.extend( { properties: this.getProperties() }, formCfg  );
		var dfd = $.Deferred(),
			modules = [];

		if ( Array.isArray( this.userInteractionModule.getModules() ) ) {
			modules = this.userInteractionModule.getModules();
		}

		// Two-step loading, otherwise it does not work
		mw.loader.using( [ "ext.workflows.form" ], function() {
			mw.loader.using( modules, function() {
					var cls = this.userInteractionModule.getClass(),
						cb = this.userInteractionModule.getCallback(),
						data = this.userInteractionModule.getData(),
						form;

					if ( data ) {
						formCfg.moduleData = data;
					}
					if ( cls ) {
						cls = this.callbackFromString( cls );
						form = new cls( formCfg, this );
					} else if ( cb ) {
						cb = this.callbackFromString( cb );
						form = cb( formCfg, this );
					} else {
						form = new workflows.object.form.Form( formCfg, this );
					}

					if ( form instanceof workflows.object.form.Form ) {
						dfd.resolve( form );
						return;
					}

					console.error( "Could not create Form object from UI module" );
					dfd.reject();
				}.bind( this ),
				function ( e ) {
					dfd.reject( e );
				}
			);
		}.bind( this ) );

		return dfd.promise();
	};

	workflows.object.UserInteractiveActivity.prototype.isInitializer = function() {
		return this.initializer;
	};

	workflows.object.UserInteractiveActivity.prototype.callbackFromString = function( callback ) {
		var parts = callback.split( '.' );
		var func = window[parts[0]];
		for( var i = 1; i < parts.length; i++ ) {
			func = func[parts[i]];
		}

		return func;
	};

	workflows.object.UserInteractiveActivity.prototype.getDescription = function() {
		return this.description;
	};


	workflows.object.UserInteractiveActivity.prototype.isUserTargeted = function( username ) {
		return this.targetUsers.length === 0 || this.targetUsers.indexOf( username ) !== -1;
	};

	workflows.object.UserInteractiveActivity.prototype.getAlertMessage = function() {
		return this.alertMessage;
	};

	workflows.object.UserInteractiveActivity.prototype.getCompleteButtonMessage = function() {
		return this.completeButtonMessage;
	};

	workflows.object.UserInteractiveActivity.prototype.getHistory = function() {
		return this.history;
	};

	workflows.object.UserInteractiveActivity.prototype.getDisplayData = function() {
		return this.displayData;
	};

	workflows.object.UserInteractiveActivity.prototype.complete = function( data ) {
		if ( this.getState() === workflows.state.activity.NOT_STARTED ) {
			throw Error( 'Cannot complete activity that is not started!' );
		}
		if ( this.getState() === workflows.state.activity.EXECUTING || this.getState() === workflows.state.activity.COMPLETE ) {
			throw Error( 'Completion of activity already called!' );
		}

		return this.getWorkflow().completeTask( this.getId(), data );
	};

} )( mediaWiki, jQuery );

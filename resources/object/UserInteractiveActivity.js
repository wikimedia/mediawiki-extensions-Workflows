( function ( mw, $ ) {
	workflows.object.UserInteractiveActivity = function ( cfg, workflow ) {
		workflows.object.UserInteractiveActivity.parent.call( this, cfg, workflow );

		this.initializer = cfg.isInitializer || false;
		this.targetUsers = cfg.targetUsers || [];
		this.completeButtonMessage = this.description.completeButtonMessage || '';
		this.alertMessage = this.description.alertMessage || '';
		this.userInteractionModule = new workflows.object.UserInteractionModule( cfg.userInteractionModule );
	};

	OO.inheritClass( workflows.object.UserInteractiveActivity, workflows.object.DescribedActivity );

	workflows.object.UserInteractiveActivity.prototype.getForm = function ( formCfg ) {
		formCfg = formCfg || {};
		formCfg = Object.assign( { properties: this.getProperties() }, formCfg );
		const dfd = $.Deferred();
		let modules = [];

		if ( Array.isArray( this.userInteractionModule.getModules() ) ) {
			modules = this.userInteractionModule.getModules();
		}

		// Two-step loading, otherwise it does not work
		mw.loader.using( [ 'ext.workflows.form' ], () => {
			mw.loader.using( modules, () => {
				let cls = this.userInteractionModule.getClass();
				let cb = this.userInteractionModule.getCallback();
				const data = this.userInteractionModule.getData();
				let form;

				if ( data ) {
					formCfg.moduleData = data;
				}
				if ( cls ) {
					cls = this.callbackFromString( cls );
					form = new cls( formCfg, this ); // eslint-disable-line new-cap
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

				console.error( 'Could not create Form object from UI module' ); // eslint-disable-line no-console
				dfd.reject();
			},
			( e ) => {
				dfd.reject( e );
			}
			);
		} );

		return dfd.promise();
	};

	workflows.object.UserInteractiveActivity.prototype.isInitializer = function () {
		return this.initializer;
	};

	workflows.object.UserInteractiveActivity.prototype.getAlertMessage = function () {
		return this.alertMessage;
	};

	workflows.object.UserInteractiveActivity.prototype.callbackFromString = function ( callback ) {
		const parts = callback.split( '.' );
		let func = window[ parts[ 0 ] ];
		for ( let i = 1; i < parts.length; i++ ) {
			func = func[ parts[ i ] ];
		}

		return func;
	};

	workflows.object.UserInteractiveActivity.prototype.isUserTargeted = function ( username ) {
		return this.targetUsers.length === 0 || this.targetUsers.indexOf( username ) !== -1;
	};

	workflows.object.UserInteractiveActivity.prototype.getCompleteButtonMessage = function () {
		return this.completeButtonMessage;
	};

	workflows.object.UserInteractiveActivity.prototype.complete = function ( data ) {
		if ( this.getState() === workflows.state.activity.NOT_STARTED ) {
			throw new Error( 'Cannot complete activity that is not started!' );
		}
		if ( this.getState() === workflows.state.activity.EXECUTING || this.getState() === workflows.state.activity.COMPLETE ) {
			throw new Error( 'Completion of activity already called!' );
		}

		return this.getWorkflow().completeTask( this.getId(), data );
	};

}( mediaWiki, jQuery ) );

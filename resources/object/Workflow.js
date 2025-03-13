( function ( mw, $ ) {
	workflows.object.Workflow = function ( id, api ) {
		this.id = id;
		this.api = api;

		this.loaded = false;
	};

	OO.initClass( workflows.object.Workflow );

	workflows.object.Workflow.prototype.load = function () {
		const dfd = $.Deferred();

		this.getRemoteData().done( ( data ) => {
			this.definition = data.definition;
			this.state = data.state;
			this.stateMessage = data.stateMessage;
			this.timestamps = data.timestamps;
			this.context = data.context || null;
			this.contextPage = data.contextPage || null;
			this.initiator = data.initiator;
			this.tasks = {};
			data.tasks = data.tasks || {};
			for ( const id in data.tasks ) {
				if ( !data.tasks.hasOwnProperty( id ) ) {
					continue;
				}
				this.tasks[ id ] = workflows.object.ElementFactory.make( data.tasks[ id ], this );
			}
			if ( data.current ) {
				this.current = {};
				for ( let i = 0; i < data.current.length; i++ ) {
					this.current[ data.current[ i ] ] = this.tasks[ data.current[ i ] ];
				}
			} else {
				this.current = {};
			}
			this.loaded = true;
			dfd.resolve( this );
		} ).fail( ( error ) => {
			dfd.reject( error );
		} );

		return dfd.promise();
	};

	workflows.object.Workflow.prototype.getRemoteData = function () {
		const dfd = $.Deferred();

		this.api.getWorkflow( this.id ).done( ( response ) => {
			if ( !response.hasOwnProperty( this.id ) ) {
				return dfd.reject( { error: 'Could not retrieve workflow' } );
			}
			dfd.resolve( response[ this.id ] );
		} ).fail( ( error ) => {
			dfd.reject( error );
		} );

		return dfd.promise();
	};

	workflows.object.Workflow.prototype.getId = function () {
		this.assertLoaded();
		return this.id;
	};

	workflows.object.Workflow.prototype.getState = function () {
		this.assertLoaded();
		return this.state;
	};

	workflows.object.Workflow.prototype.getStateMessage = function () {
		this.assertLoaded();
		return this.stateMessage;
	};

	workflows.object.Workflow.prototype.getTimestamps = function () {
		this.assertLoaded();
		return this.timestamps;
	};

	workflows.object.Workflow.prototype.getCurrent = function () {
		this.assertLoaded();
		return this.current;
	};

	workflows.object.Workflow.prototype.getContext = function () {
		this.assertLoaded();
		return this.context;
	};

	workflows.object.Workflow.prototype.getContextPage = function () {
		this.assertLoaded();
		return this.contextPage;
	};

	workflows.object.Workflow.prototype.getInitiator = function () {
		this.assertLoaded();
		return this.initiator;
	};

	workflows.object.Workflow.prototype.isCurrentUserInitiator = function () {
		this.assertLoaded();
		return this.initiator === mw.config.get( 'wgUserName' );
	};

	workflows.object.Workflow.prototype.getDefinition = function () {
		this.assertLoaded();
		return this.definition;
	};

	workflows.object.Workflow.prototype.getTaskKeys = function () {
		this.assertLoaded();
		return Object.keys( this.tasks );
	};

	workflows.object.Workflow.prototype.getTask = function ( key ) {
		this.assertLoaded();
		return this.tasks[ key ] || null;
	};

	workflows.object.Workflow.prototype.completeTask = function ( taskId, data ) {
		this.assertLoaded();
		this.assertCurrent( taskId );
		const dfd = $.Deferred();
		this.api.completeTask( this.id, taskId, data ).done( ( response ) => {
			if ( response.ack ) {
				this.load().done( () => {
					dfd.resolve();
				} ).fail( ( error ) => {
					dfd.reject( error.error.message );
				} );
			}

		} ).fail( ( error ) => {
			dfd.reject( error.error.message );
		} );

		return dfd.promise();
	};

	workflows.object.Workflow.prototype.abort = function ( reason ) {
		this.assertLoaded();
		const dfd = $.Deferred();
		this.api.abort( this.id, reason ).done( ( response ) => {
			if ( response.ack ) {
				this.load().done( () => {
					dfd.resolve();
				} ).fail( ( error ) => {
					dfd.reject( error.error.message );
				} );
			}
		} ).fail( ( error ) => {
			dfd.reject( error.error.message );
		} );

		return dfd.promise();
	};

	workflows.object.Workflow.prototype.restore = function ( reason ) {
		this.assertLoaded();
		const dfd = $.Deferred();
		this.api.restore( this.id, reason ).done( ( response ) => {
			if ( response.ack ) {
				this.load().done( ( wf ) => {
					dfd.resolve( wf );
				} ).fail( ( error ) => {
					dfd.reject( error.error.message );
				} );
			}
		} ).fail( ( error ) => {
			dfd.reject( error.error.message );
		} );

		return dfd.promise();
	};

	workflows.object.Workflow.prototype.assertLoaded = function () {
		if ( !this.loaded ) {
			throw new Error( 'Workflow is not loaded. Please call Workflow.load()' );
		}
	};
	workflows.object.Workflow.prototype.assertCurrent = function ( id ) {
		if ( !this.current.hasOwnProperty( id ) || !( this.current[ id ] instanceof workflows.object.UserInteractiveActivity ) ) {
			throw new Error( 'Current step is not set or not a completable task' );
		}
	};
}( mediaWiki, jQuery ) );

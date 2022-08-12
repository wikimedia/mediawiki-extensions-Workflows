( function( mw, $ ) {
	workflows.api.Api = function() {
		this.currentRequests = {};
	};

	OO.initClass( workflows.api.Api );

	workflows.api.Api.prototype.getWorkflows = function( params ) {
		if ( params.hasOwnProperty( 'filter' ) ) {
			params.filter = JSON.stringify( params.filter );
		}
		if ( params.hasOwnProperty( 'sort' ) ) {
			params.sort = JSON.stringify( params.sort );
		}
		return this.get( 'list', params );
	};

	workflows.api.Api.prototype.getWorkflow = function( id ) {
		return this.get( 'retrieve/' + id );
	};

	workflows.api.Api.prototype.get = function( path, params ) {
		params = params || {};
		return this.ajax( path, params, 'GET' );
	};

	workflows.api.Api.prototype.post = function( path, params ) {
		params = params || {};
		return this.ajax( path, JSON.stringify( { data: params } ), 'POST' );
	};

	workflows.api.Api.prototype.put = function( path, params ) {
		params = params || {};
		return this.ajax( path, JSON.stringify( { data: params } ), 'PUT' );
	};

	workflows.api.Api.prototype.delete = function( path, params ) {
		params = params || {};
		return this.ajax( path, JSON.stringify( { data: params } ), 'DELETE' );
	};


	workflows.api.Api.prototype.ajax = function( path, data, method ) {
		data = data || {};
		var dfd = $.Deferred();

		this.currentRequests[path] = $.ajax( {
			method: method,
			url: this.makeUrl( path ),
			data: data,
			contentType: "application/json",
			dataType: 'json',
			beforeSend: function() {
				if ( this.currentRequests.hasOwnProperty( path ) ) {
					this.currentRequests[path].abort();
				}
			}.bind( this )
		} ).done( function( response ) {
			delete( this.currentRequests[path] );
			if ( response.success === false ) {
				dfd.reject();
				return;
			}
			dfd.resolve( response );
		}.bind( this ) ).fail( function( jgXHR, type, status ) {
			delete( this.currentRequests[path] );
			if ( type === 'error' ) {
				dfd.reject( {
					error: jgXHR.responseJSON || jgXHR.responseText
				} );
			}
			dfd.reject( { type: type, status: status } );
		}.bind( this ) );

		return dfd.promise();
	};

	workflows.api.Api.prototype.makeUrl = function ( path ) {
		if ( path.charAt( 0 )  === '/' ) {
			path = path.substring( 1 );
		}
		return mw.util.wikiScript( 'rest' ) + '/workflow/' + path;
	};

	workflows.api.Api.prototype.completeTask = function ( id, taskId, data ) {
		return this.post( 'complete_task/' + id + '/' + taskId, data );
	};

	workflows.api.Api.prototype.getDefinitions = function () {
		return this.get( 'definition/list' );
	};

	workflows.api.Api.prototype.getDefinitionDetails = function ( repo, definition ) {
		return this.get( 'definition/details/' + repo + '/' + definition );
	};

	workflows.api.Api.prototype.startWorkflow = function ( repository, type, data ) {
		return this.post( 'start/' + repository + '/' + type, data );
	};

	workflows.api.Api.prototype.dryStartWorkflow = function ( repository, type, data ) {
		return this.post( 'dry_start/' +  repository + '/' + type, data );
	};

	workflows.api.Api.prototype.abort = function ( id, reason ) {
		return this.post( 'abort/' + id, { reason: reason } );
	};

	workflows.api.Api.prototype.restore = function ( id, reason ) {
		return this.post( 'restore/'  + id, { reason: reason } );
	};

	workflows.api.Api.prototype.getTriggers = function ( key ) {
		key = key || '*';
		return this.get( 'triggers/' + key );
	};

	workflows.api.Api.prototype.persistTriggers = function ( data ) {
		return this.put( 'triggers', data );
	};

	workflows.api.Api.prototype.deleteTrigger = function ( key ) {
		return this.delete( 'triggers/' + key );
	};

	workflows.api.Api.prototype.getTriggerTypes = function () {
		return this.get( 'trigger_types' );
	};

	workflows.api.Api.prototype.getManualTriggersForPage = function ( page ) {
		return this.get( 'triggers/of_type/manual', { page: page } );
	};
} )( mediaWiki, jQuery );

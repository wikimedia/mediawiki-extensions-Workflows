( function( mw, $ ) {
	workflows.api.Api = function() {
	};

	OO.initClass( workflows.api.Api );

	workflows.api.Api.prototype.getWorkflows = function( active, filterData, fullDetails, offset, limit ) {
		filterData = filterData || {};
		if ( typeof active !== 'undefined' ) {
			active = active ? 1 : 0;
		}
		return this.get( 'list', {
			active: active,
			filterData: JSON.stringify( filterData ),
			fullDetails: fullDetails ? 1 : 0,
			offset: offset,
			limit: limit
		} );
	};

	workflows.api.Api.prototype.getWorkflow = function( id ) {
		return this.get( 'retrieve/{0}'.format( id ) );
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

		$.ajax( {
			method: method,
			url: this.makeUrl( path ),
			data: data,
			contentType: "application/json",
			dataType: 'json'
		} ).done( function( response ) {
			if ( response.success === false ) {
				dfd.reject();
				return;
			}
			dfd.resolve( response );
		} ).fail( function( jgXHR, type, status ) {
			if ( type === 'error' ) {
				dfd.reject( {
					error: jgXHR.responseJSON || jgXHR.responseText
				} );
			}
			dfd.reject( { type: type, status: status } );
		} );

		return dfd.promise();
	};

	workflows.api.Api.prototype.makeUrl = function ( path ) {
		if ( path.charAt( 0 )  === '/' ) {
			path = path.substring( 1 );
		}
		return mw.util.wikiScript( 'rest' ) + '/workflow/{0}'.format( path );
	};

	workflows.api.Api.prototype.completeTask = function ( id, taskId, data ) {
		return this.post( 'complete_task/{0}/{1}'.format( id, taskId ), data );
	};

	workflows.api.Api.prototype.getDefinitions = function () {
		return this.get( 'definition/list' );
	};

	workflows.api.Api.prototype.getDefinitionDetails = function ( repo, definition ) {
		return this.get( 'definition/details/{0}/{1}'.format( repo, definition ) );
	};

	workflows.api.Api.prototype.startWorkflow = function ( repository, type, data ) {
		return this.post( 'start/{0}/{1}'.format( repository, type ), data );
	};

	workflows.api.Api.prototype.dryStartWorkflow = function ( repository, type, data ) {
		return this.post( 'dry_start/{0}/{1}'.format( repository, type ), data );
	};

	workflows.api.Api.prototype.abort = function ( id, reason ) {
		return this.post( 'abort/{0}'.format( id ), { reason: reason } );
	};

	workflows.api.Api.prototype.restore = function ( id, reason ) {
		return this.post( 'restore/{0}'.format( id ), { reason: reason } );
	};

	workflows.api.Api.prototype.getTriggers = function ( key ) {
		key = key || '*';
		return this.get( 'triggers/{0}'.format( key ) );
	};

	workflows.api.Api.prototype.persistTriggers = function ( data ) {
		return this.put( 'triggers', data );
	};

	workflows.api.Api.prototype.deleteTrigger = function ( key ) {
		return this.delete( 'triggers/{0}'.format( key ) );
	};

	workflows.api.Api.prototype.getTriggerTypes = function () {
		return this.get( 'trigger_types' );
	};

	workflows.api.Api.prototype.getManualTriggersForPage = function ( page ) {
		return this.get( 'triggers/manual/{0}'.format( page ) );
	};
} )( mediaWiki, jQuery );

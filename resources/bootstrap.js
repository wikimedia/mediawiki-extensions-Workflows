window.workflows = {
	api: {},
	store: {},
	ui: {
		openWorkflowStarter: function( repos ) {
			repos = repos || [];
			mw.loader.using( [ "ext.workflows.ui.starter" ], function() {
				var windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				var dialog = new workflows.ui.dialog.WorkflowStarter( {
					repos: repos,
					contextData: workflows.context.getWorkflowContext()
				} );
				windowManager.addWindows( [ dialog ] );
				windowManager.openWindow( dialog ).closed.then( function( data ) {
					if ( !data ) {
						return;
					}
					window.location.reload();
				} );
			} );

		},
		openWorkflowManager: function( workflow, overviewType ) {
			var windowManager = new OO.ui.WindowManager();
			$( document.body ).append( windowManager.$element );

			var dialog = new workflows.ui.dialog.WorkflowOverview( workflow, overviewType );
			windowManager.addWindows( [ dialog ] );
			windowManager.openWindow( dialog ).closed.then( function( data ) {
				if ( data && data.action === 'abort' ) {
					workflows.ui.alert.manager.removeForWorkflow( data.workflow );
				}
				if ( data && data.action === 'restore' ) {
					workflows.ui.alert.manager.addFromWorkflow( data.workflow );
				}
				windowManager.removeWindows( [ dialog ] );
				windowManager.destroy();
			}.bind( this ) );

		},
		openActivityCompletionDialog: function( workflow, activity ) {
			activity = activity || workflow.getCurrent();
			var dfd = $.Deferred();

			if ( !activity instanceof workflows.object.UserInteractiveActivity ) {
				console.error( 'Trying to open dialog for completion of non-user activity' );
				dfd.reject();
			}
			mw.loader.using( [ "ext.workflows.ui.task.complete" ], function() {
				var windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				var dialog = new workflows.ui.dialog.TaskCompletion( workflow, activity );
				windowManager.addWindows( [ dialog ] );
				dfd.resolve( windowManager.openWindow( dialog ) );
			} );

			return dfd.promise();
		},
		addRunningWorkflowAlerts: async function() {
			var response = await workflows.list.filtered( {
				filter: {
					context: {
						field: 'context',
						type: 'string',
						value: workflows.context.getWorkflowContext()
					},
					active: { type: 'boolean', operator: 'eq', value: true }
				}
			} );
			if ( !response.hasOwnProperty( 'workflows' ) ) {
				console.error( 'Cannot load running workflows' );
				return;
			}

			for ( var i = 0; i < response.workflows.length; i++ ) {
				var workflow = await workflows.getWorkflow( response.workflows[i].id );
				workflows.ui.alert.manager.addFromWorkflow( workflow );
			}
		},
		alert: {},
		dialog: {},
		panel: {},
		widget: {},
		trigger: {
			mixin: {}
		}
	},
	object: {
		form: {}
	},
	_internal: {
		_withStartContext: function ( data, callback ) {
			if ( !data.hasOwnProperty( 'startData' ) ) {
				data = {
					startData: data
				};
			}
			var dfd = $.Deferred();
			mw.loader.using( [ "ext.workflows.api", "ext.workflows.objects" ], function() {
				workflows._internal._getApi().done( function( api ) {
					callback.call( this, api, data, dfd );
				} );
			} );

			return dfd.promise();
		},
		userCan: {},
		_api: {
			promise: null,
			api: null
		},
		_getApi: function() {
			// Get API Singleton
			if ( workflows._internal._api.promise ) {
				return workflows._internal._api.promise;
			}

			var dfd = $.Deferred();
			if ( !workflows._internal._api.api ) {
				mw.loader.using( [ "ext.workflows.api" ], function() {
					workflows._internal._api.api = new workflows.api.Api();
					workflows._internal._api.promise = null;
					dfd.resolve( workflows._internal._api.api );
				} );
				workflows._internal._api.promise = dfd.promise();
				return workflows._internal._api.promise;
			} else {
				dfd.resolve( workflows._internal._api.api );
			}
			return dfd.promise();
		}
	},
	context: {
		/* Basic context to be passed to the workflow when starting */
		getWorkflowContext: function() {
			return {
				pageId: mediaWiki.config.get( 'wgArticleId' ),
				revision: mediaWiki.config.get( 'wgRevisionId' )
			};
		}
	},
	state: {
		NOT_STARTED: 'not_started',
		RUNNING: 'running',
		FINISHED: 'finished',
		ABORTED: 'aborted',
		activity: {
			NOT_STARTED: 0,
			STARTED: 1,
			EXECUTING: 2,
			LOOP_COMPLETE: 4,
			COMPLETE: 3
		}
	},
	initiate: {
		listAvailableTypes: function() {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getDefinitions().done( function( definitions ) {
					dfd.resolve( definitions );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );

			return dfd.promise();
		},
		getDefinitionDetails: function( repo, definition ) {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getDefinitionDetails( repo, definition ).done( function( data ) {
					dfd.resolve( data );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		startWorkflowOfType: function( repository, type, data ) {
			return workflows._internal._withStartContext( data, function( api, data, dfd ) {
				api.startWorkflow( repository, type, data ).done( function( response ) {
					var id = response.id || null;
					workflows.getWorkflow( id ).done( function( workflow ) {
						dfd.resolve( workflow );
					} ).fail( function ( error ) {
						dfd.reject( error );
					} );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
		},
		dryStartWorkflowOfType: function( repository, type, data ) {
			return workflows._internal._withStartContext( data, function( api, data, dfd ) {
				api.dryStartWorkflow( repository, type, data ).done( function( response ) {
					if ( response.initializer !== null ) {
						var activity = workflows.object.ElementFactory.make(
							response.initializer, new workflows.object.NullWorkflow()
						);
						dfd.resolve( activity );
					} else {
						dfd.resolve( null );
					}
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
		}
	},
	list: {
		all: function( params ) {
			return workflows.list.filtered(params );
		},
		active: function( params ) {
			params.filter = params.filter || {};
			params.filter.state = { type: 'list', operator: 'in', value: [ 'running' ] };
			return workflows.list.filtered( params );
		},
		filtered: function( params ) {
			function serialize( data, fieldProperty ) {
				fieldProperty = fieldProperty || 'field';
				var res = [];
				for ( var key in data ) {
					if ( !data.hasOwnProperty( key ) ) {
						continue;
					}
					if ( data[key] ) {
						var objectData = typeof data[key].getValue === 'function' ? data[key].getValue() : data[key];
						var serialized = {};
						serialized[fieldProperty] = key;
						res.push( $.extend( serialized, objectData ) );
					}
				}

				return res;
			}
			params.filter = serialize( params.filter );
			params.sort = serialize( params.sort, 'property' );
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getWorkflows( params ).done(
					function( workflows ) {
						dfd.resolve( workflows );
					}
				).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		}
	},
	getWorkflow: function ( id ) {
		var dfd = $.Deferred();
		mw.loader.using( [ "ext.workflows.objects" ], function() {
			if ( !id ) {
				dfd.reject( 'Invalid ID' );
			} else {
				workflows._internal._getApi().done( function( api ) {
					var workflow = new workflows.object.Workflow( id, api );
					workflow.load()
					.done( function () {
						dfd.resolve( workflow );
					} )
					.fail( function ( error ) {
						dfd.reject( error );
					} );
				} );
			}
		} );

		return dfd.promise();
	},
	userCan: function( right ) {
		var dfd = $.Deferred();
		if ( workflows._internal.userCan.hasOwnProperty( right ) ) {
			if ( workflows._internal.userCan[right] ) {
				dfd.resolve();
			} else {
				dfd.reject();
			}
		} else {
			mw.user.getRights( function( rights ) {
				if ( rights.indexOf( right ) !== -1 ) {
					workflows._internal.userCan[right] = true;
					dfd.resolve();
				} else {
					workflows._internal.userCan[right] = false;
					dfd.reject();
				}
			} );
		}

		return dfd.promise();
	},
	triggers: {
		getAvailableTypes: function() {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getTriggerTypes().done( function( data ) {
					dfd.resolve( data );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		getAll: function() {
			return workflows.triggers.get( null );
		},
		get: function( key ) {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getTriggers( key ).done( function( data ) {
					dfd.resolve( data );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		persist: function( triggers ) {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.persistTriggers( triggers ).done( function() {
					dfd.resolve();
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		delete: function( key ) {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.deleteTrigger( key ).done( function() {
					dfd.resolve();
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		getManualTriggersForPage: function( page ) {
			var dfd = $.Deferred();
			workflows._internal._getApi().done( function( api ) {
				api.getManualTriggersForPage( page ).done( function( data ) {
					dfd.resolve( data );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		}
	},
	util: {
		callbackFromString: function( str ) {
			var parts = str.split( '.' );
			var func = window[parts[0]];
			for( var i = 1; i < parts.length; i++ ) {
				func = func[parts[i]];
			}

			return func;
		},
		getDeepValue: function( obj, path ) {
			if ( !obj ) {
				return undefined;
			}
			var parts = path.split( "." );
			if ( parts.length === 1 ) {
				return obj[parts[0]];
			}

 			return workflows.util.getDeepValue( obj[parts[0]], parts.slice( 1 ).join( "." ) );
		},
		getAvailableWorkflowOptions: function( availableRepos ) {
			availableRepos = availableRepos || [];
			var dfd = $.Deferred();
			workflows.initiate.listAvailableTypes().done( function ( types ) {
				var options = [], definitions, repo, i;
				for ( repo in types ) {
					if ( !types.hasOwnProperty( repo ) ) {
						continue;
					}
					if ( availableRepos.length > 0 && availableRepos.indexOf( repo ) === -1 ) {
						continue;
					}
					definitions = types[repo].definitions;
					for ( i = 0; i < definitions.length; i++ ) {
						var option = {
							data: {
								workflow: {
									repo: repo,
									workflow: definitions[i].key,
								},
								desc: definitions[i].desc || ''
							},
							label: definitions[i].title,
							desc: definitions[i].desc
						};
						options.push(  option );
					}
				}
				dfd.resolve( options );
			}  ).fail( function() {
				dfd.reject( arguments );
			}  );

			return dfd.promise();
		}
	}
};

$( function() {
	if (
		mw.config.get( 'wgNamespaceNumber' ) < 0 ||
		!mw.config.get( 'wgRevisionId' ) ||
		mw.config.get( 'wgPageContentModel' ) !== 'wikitext'
	) {
		return;
	}

	$( document ).on( 'click', '#ca-wf_start', function() {
		workflows.ui.openWorkflowStarter();
	} );

	$( document ).on( 'click', '#ca-wf_view_for_page,#ca-varlang-wf_view_for_page', function() {
		workflows.ui.openWorkflowManager( null, 'page' );
	} );

	mw.loader.using( [ 'ext.workflows.alert' ], function() {
		workflows.ui.alert.manager = new workflows.ui.alert.Manager();
		workflows.ui.addRunningWorkflowAlerts();
	} );
} );

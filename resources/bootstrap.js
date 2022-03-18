window.workflows = {
	api: {},
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
					if ( data.result === true && data.workflow instanceof workflows.object.Workflow ) {
						workflows.ui.alert.manager.addFromWorkflow( data.workflow );
					}
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
			var response = await workflows.list.filtered( true, {
				context: workflows.context.getWorkflowContext()
			} );
			if ( !response.hasOwnProperty( 'workflows' ) ) {
				console.error( 'Cannot load running workflows' );
				return;
			}

			for ( var i = 0; i < response.workflows.length; i++ ) {
				var workflow = await workflows.getWorkflow( response.workflows[i] );
				workflows.ui.alert.manager.addFromWorkflow( workflow );
			}
		},
		alert: {},
		dialog: {},
		panel: {},
		widget: {}
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
				var api = new workflows.api.Api();
				callback.call( this, api, data, dfd );
			} );

			return dfd.promise();
		},
		userCan: {}
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
			mw.loader.using( [ "ext.workflows.api" ], function() {
				var api = new workflows.api.Api();

				api.getDefinitions().done( function( definitions ) {
					dfd.resolve( definitions );
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
		all: function( fullDetail ) {
			return workflows.list.filtered( undefined, {}, fullDetail );
		},
		active: function( active, fullDetail ) {
			active = typeof active === 'undefined' ? true : active;
			return workflows.list.filtered( active, {}, fullDetail );
		},
		filtered: function( active, filterData, fullDetail ) {
			var dfd = $.Deferred();
			mw.loader.using( [ "ext.workflows.api" ], function() {
				var api = new workflows.api.Api();

				api.getWorkflows( active, filterData, fullDetail ).done( function( workflows ) {
					dfd.resolve( workflows );
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		}
	},
	getWorkflow: function ( id ) {
		var dfd = $.Deferred();
		mw.loader.using( [ "ext.workflows.api", "ext.workflows.objects" ], function() {
			var api = new workflows.api.Api();
			if ( !id ) {
				dfd.reject( 'Invalid ID' );
			}
			var workflow = new workflows.object.Workflow( id, api );
			workflow.load()
				.done( function() {
					dfd.resolve( workflow );
				} )
				.fail( function ( error ) {
					dfd.reject( error );
				} );
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
		getAll: function() {
			return workflows.triggers.get( null );
		},
		get: function( key ) {
			var dfd = $.Deferred();
			mw.loader.using( [ "ext.workflows.api" ], function() {
				var api = new workflows.api.Api();

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
			mw.loader.using( [ "ext.workflows.api" ], function() {
				var api = new workflows.api.Api();

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
			mw.loader.using( [ "ext.workflows.api" ], function() {
				var api = new workflows.api.Api();

				api.deleteTrigger( key ).done( function() {
					dfd.resolve();
				} ).fail( function ( error ) {
					dfd.reject( error );
				} );
			} );
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

	$( document ).on( 'click', '#ca-wf_manage', function() {
		workflows.ui.openWorkflowManager();
	} );

	mw.loader.using( [ 'ext.workflows.alert' ], function() {
		workflows.ui.alert.manager = new workflows.ui.alert.Manager();
		workflows.ui.addRunningWorkflowAlerts();
	} );
} );

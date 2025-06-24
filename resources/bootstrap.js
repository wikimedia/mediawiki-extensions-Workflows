window.workflows = {
	api: {},
	store: {},
	editor: {
		property: {},
		inspector: {},
		element: {},
		tool: {},
		dialog: {}
	},
	ui: {
		openWorkflowStarter: function ( repos ) {
			repos = repos || [];
			mw.loader.using( [ 'ext.workflows.ui.starter' ], () => {
				const windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				const dialog = new workflows.ui.dialog.WorkflowStarter( {
					repos: repos,
					contextData: workflows.context.getWorkflowContext()
				} );
				windowManager.addWindows( [ dialog ] );
				windowManager.openWindow( dialog ).closed.then( ( data ) => {
					if ( !data ) {
						return;
					}
					window.location.reload();
				} );
			} );

		},
		openWorkflowManager: function ( workflow, overviewType ) {
			const windowManager = new OO.ui.WindowManager();
			$( document.body ).append( windowManager.$element );

			const dialog = new workflows.ui.dialog.WorkflowOverview( workflow, overviewType );
			windowManager.addWindows( [ dialog ] );
			windowManager.openWindow( dialog ).closed.then( ( data ) => {
				if ( data && data.action === 'abort' ) {
					workflows.ui.alert.manager.removeForWorkflow( data.workflow );
				}
				if ( data && data.action === 'restore' ) {
					workflows.ui.alert.manager.addFromWorkflow( data.workflow );
				}
				windowManager.removeWindows( [ dialog ] );
				windowManager.destroy();
			} );

		},
		openActivityCompletionDialog: function ( workflow, activity ) {
			activity = activity || workflow.getCurrent();
			const dfd = $.Deferred();

			if ( !( activity instanceof workflows.object.UserInteractiveActivity ) ) {
				console.error( 'Trying to open dialog for completion of non-user activity' ); // eslint-disable-line no-console
				dfd.reject();
			}
			mw.loader.using( [ 'ext.workflows.ui.task.complete' ], () => {
				const windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				const dialog = new workflows.ui.dialog.TaskCompletion( workflow, activity );
				windowManager.addWindows( [ dialog ] );
				dfd.resolve( windowManager.openWindow( dialog ) );
			} );

			return dfd.promise();
		},
		addRunningWorkflowAlerts: async function () {
			const response = await workflows.list.filtered( {
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
				console.error( 'Cannot load running workflows' ); // eslint-disable-line no-console
				return;
			}

			for ( let i = 0; i < response.workflows.length; i++ ) {
				const workflow = await workflows.getWorkflow( response.workflows[ i ].id );
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
		_withStartContext: function ( data, callback, initData ) {
			if ( !data.hasOwnProperty( 'startData' ) ) {
				data = {
					startData: data
				};
			}
			if ( initData && !data.hasOwnProperty( 'initData' ) ) {
				data.initData = initData;
			}
			const dfd = $.Deferred();
			mw.loader.using( [ 'ext.workflows.api', 'ext.workflows.objects' ], () => {
				workflows._internal._getApi().done( function ( api ) {
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
		_getApi: function () {
			// Get API Singleton
			if ( workflows._internal._api.promise ) {
				return workflows._internal._api.promise;
			}

			const dfd = $.Deferred();
			if ( !workflows._internal._api.api ) {
				mw.loader.using( [ 'ext.workflows.api' ], () => {
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
		getWorkflowContext: function () {
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
		listAvailableTypes: function () {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getDefinitions().done( ( definitions ) => {
					dfd.resolve( definitions );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );

			return dfd.promise();
		},
		getDefinitionDetails: function ( repo, definition ) {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getDefinitionDetails( repo, definition ).done( ( data ) => {
					dfd.resolve( data );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		startWorkflowOfType: function ( repository, type, data ) {
			return workflows._internal._withStartContext( data, ( api, data, dfd ) => { // eslint-disable-line no-shadow
				api.startWorkflow( repository, type, data ).done( ( response ) => {
					const id = response.id || null;
					workflows.getWorkflow( id ).done( ( workflow ) => {
						dfd.resolve( workflow );
					} ).fail( ( error ) => {
						dfd.reject( error );
					} );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
		},
		dryStartWorkflowOfType: function ( repository, type, data, initData ) {
			return workflows._internal._withStartContext( data, ( api, data, dfd ) => { // eslint-disable-line no-shadow
				api.dryStartWorkflow( repository, type, data ).done( ( response ) => {
					if ( response.initializer !== null ) {
						const activity = workflows.object.ElementFactory.make(
							response.initializer, new workflows.object.NullWorkflow()
						);
						dfd.resolve( activity );
					} else {
						dfd.resolve( null );
					}
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			}, initData );
		}
	},
	list: {
		all: function ( params ) {
			return workflows.list.filtered( params );
		},
		active: function ( params ) {
			params.filter = params.filter || {};
			params.filter.state = { type: 'list', operator: 'in', value: [ 'running' ] };
			return workflows.list.filtered( params );
		},
		filtered: function ( params ) {
			function serialize( data, fieldProperty ) {
				fieldProperty = fieldProperty || 'field';
				const res = [];
				for ( const key in data ) {
					if ( !data.hasOwnProperty( key ) ) {
						continue;
					}
					if ( data[ key ] ) {
						const objectData = typeof data[ key ].getValue === 'function' ? data[ key ].getValue() : data[ key ];
						const serialized = {};
						serialized[ fieldProperty ] = key;
						res.push( $.extend( serialized, objectData ) );
					}
				}

				return res;
			}
			params.filter = serialize( params.filter );
			params.sort = serialize( params.sort, 'property' );
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getWorkflows( params ).done(
					( workflows ) => {
						dfd.resolve( workflows );
					}
				).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		}
	},
	getWorkflow: function ( id ) {
		const dfd = $.Deferred();
		mw.loader.using( [ 'ext.workflows.objects' ], () => {
			if ( !id ) {
				dfd.reject( 'Invalid ID' );
			} else {
				workflows._internal._getApi().done( ( api ) => {
					const workflow = new workflows.object.Workflow( id, api );
					workflow.load()
						.done( () => {
							dfd.resolve( workflow );
						} )
						.fail( ( error ) => {
							dfd.reject( error );
						} );
				} );
			}
		} );

		return dfd.promise();
	},
	userCan: function ( right ) {
		const dfd = $.Deferred();
		if ( workflows._internal.userCan.hasOwnProperty( right ) ) {
			if ( workflows._internal.userCan[ right ] ) {
				dfd.resolve();
			} else {
				dfd.reject();
			}
		} else {
			mw.user.getRights( ( rights ) => {
				if ( rights.indexOf( right ) !== -1 ) {
					workflows._internal.userCan[ right ] = true;
					dfd.resolve();
				} else {
					workflows._internal.userCan[ right ] = false;
					dfd.reject();
				}
			} );
		}

		return dfd.promise();
	},
	triggers: {
		getAvailableTypes: function () {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getTriggerTypes().done( ( data ) => {
					dfd.resolve( data );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		getAll: function () {
			return workflows.triggers.get( null );
		},
		get: function ( key ) {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getTriggers( key ).done( ( data ) => {
					dfd.resolve( data );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		persist: function ( triggers ) {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.persistTriggers( triggers ).done( () => {
					dfd.resolve();
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		delete: function ( key ) {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.deleteTrigger( key ).done( () => {
					dfd.resolve();
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		},
		getManualTriggersForPage: function ( page ) {
			const dfd = $.Deferred();
			workflows._internal._getApi().done( ( api ) => {
				api.getManualTriggersForPage( page ).done( ( data ) => {
					dfd.resolve( data );
				} ).fail( ( error ) => {
					dfd.reject( error );
				} );
			} );
			return dfd.promise();
		}
	},
	util: {
		callbackFromString: function ( str ) {
			const parts = str.split( '.' );
			let func = window[ parts[ 0 ] ];
			for ( let i = 1; i < parts.length; i++ ) {
				func = func[ parts[ i ] ];
			}

			return func;
		},
		getDeepValue: function ( obj, path ) {
			if ( !obj ) {
				return undefined;
			}
			const parts = path.split( '.' );
			if ( parts.length === 1 ) {
				return obj[ parts[ 0 ] ];
			}

			return workflows.util.getDeepValue( obj[ parts[ 0 ] ], parts.slice( 1 ).join( '.' ) );
		},
		getAvailableWorkflowOptions: function ( availableRepos ) {
			availableRepos = availableRepos || [];
			const dfd = $.Deferred();
			workflows.initiate.listAvailableTypes().done( ( types ) => {
				const options = [];
				let definitions, repo, i;
				for ( repo in types ) {
					if ( !types.hasOwnProperty( repo ) ) {
						continue;
					}
					if ( availableRepos.length > 0 && availableRepos.indexOf( repo ) === -1 ) {
						continue;
					}
					definitions = types[ repo ].definitions;
					for ( i = 0; i < definitions.length; i++ ) {
						const option = {
							data: {
								workflow: {
									repo: repo,
									workflow: definitions[ i ].key
								},
								desc: definitions[ i ].desc || ''
							},
							label: definitions[ i ].title,
							desc: definitions[ i ].desc
						};
						options.push( option );
					}
				}
				dfd.resolve( options );
			} ).fail( function () {
				dfd.reject( arguments );
			} );

			return dfd.promise();
		}
	}
};

function maybeAddAlerts() { // eslint-disable-line no-implicit-globals
	if (
		mw.config.get( 'wgNamespaceNumber' ) < 0 ||
		!mw.config.get( 'wgRevisionId' )
	) {
		return;
	}

	$( document ).on( 'click', '#ca-wf_start', () => {
		workflows.ui.openWorkflowStarter();
	} );

	$( document ).on( 'click', '#ca-wf_view_for_page,#ca-varlang-wf_view_for_page', () => {
		workflows.ui.openWorkflowManager( null, 'page' );
	} );

	mw.loader.using( [ 'ext.workflows.alert' ], () => {
		workflows.ui.alert.manager = new workflows.ui.alert.Manager();
		workflows.ui.addRunningWorkflowAlerts();
	} );
}

function maybeAddEditor() { // eslint-disable-line no-implicit-globals
	const $c = $( '#workflows-editor-panel' );
	if ( $c.length === 0 ) {
		return;
	}

	const action = $c.data( 'action' );
	if ( action === 'edit' || action === 'create' ) {
		mw.loader.using( mw.config.get( 'workflowPluginModules' ) ).done( () => {
			mw.loader.using( [ 'ext.workflows.editor' ] ).done( () => {
				const editor = new workflows.ui.widget.BpmnEditor( $c.data() );
				$c.html( editor.$element );
			} );
		} );

	} else {
		mw.loader.using( [ 'ext.workflows.viewer' ] ).done( () => {
			const viewer = new workflows.ui.widget.BpmnViewer( $c.data() );
			$c.html( viewer.$element );
		} );
	}
}

$( () => {
	maybeAddAlerts();
	maybeAddEditor();
} );

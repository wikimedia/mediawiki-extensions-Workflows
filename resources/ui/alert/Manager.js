( function ( mw, $ ) {
	workflows.ui.alert.Manager = function () {
		this.alerts = {};
	};

	OO.initClass( workflows.ui.alert.Manager );

	workflows.ui.alert.Manager.prototype.has = function ( id ) {
		return this.alerts.hasOwnProperty( id );
	};

	workflows.ui.alert.Manager.prototype.addFromWorkflow = function ( workflow ) {
		if ( workflow.getState() !== workflows.state.RUNNING ) {
			return;
		}
		const activities = workflow.getCurrent();
		if ( !$.isEmptyObject( activities ) ) {
			const selected = this.selectActivity( activities );
			if ( selected === null ) {
				this.add( new workflows.ui.alert.Alert( workflow.id, workflow ) );
				return;
			}
			const alert = new workflows.ui.alert.ActivityAlert(
				workflow.id + '_' + selected.getId(), selected, workflow
			);
			alert.connect( this, {
				completeActivity: 'completeActivity'
			} );
			this.add( alert );
		} else {
			// Add generic "workflow running" alert
			this.add( new workflow.ui.alert.Alert( workflow ) );
		}
	};

	workflows.ui.alert.Manager.prototype.add = function ( alert ) {
		if ( this.has( alert.getId() ) ) {
			return;
		}
		this.alerts[ alert.getId() ] = alert;
		alert.connect( this, {
			manage: 'openWorkflowOverview'
		} );
		mwstake.alerts.add(
			alert.getId(),
			alert.getContent(),
			alert.getType()
		);
	};

	workflows.ui.alert.Manager.prototype.remove = function ( id ) {
		if ( this.has( id ) ) {
			delete ( this.alerts[ id ] );
		}
		mwstake.alerts.remove( id );
	};

	workflows.ui.alert.Manager.prototype.openWorkflowOverview = function ( id, role ) {
		if ( !this.has( id ) ) {
			console.error( 'Trying to manage non-existing workflows ' + id ); // eslint-disable-line no-console
		}

		workflows.ui.openWorkflowManager( this.alerts[ id ].getWorkflow(), role === 'admin' ? 'page' : null );
	};

	workflows.ui.alert.Manager.prototype.removeForWorkflow = function ( workflow ) {
		for ( const id in this.alerts ) {
			if ( !this.alerts.hasOwnProperty( id ) ) {
				continue;
			}
			if ( this.alerts[ id ].getWorkflow().getId() === workflow.getId() ) {
				this.remove( id );
			}
		}
	};

	workflows.ui.alert.Manager.prototype.completeActivity = function ( workflow, activity ) {
		if ( activity instanceof workflows.object.UserInteractiveActivity ) {
			workflows.ui.openActivityCompletionDialog( workflow, activity )
				.done( ( dialog ) => {
					dialog.closed.then( ( data ) => {
						if ( !data || !data.result ) {
						// Will never happen
							return;
						}
						window.location.reload();
					} );
				} );
		}
	};

	workflows.ui.alert.Manager.prototype.selectActivity = function ( activities ) {
		let activity = null;
		for ( const id in activities ) {
			if ( !activities.hasOwnProperty( id ) ) {
				continue;
			}

			if (
				!( activities[ id ] instanceof workflows.object.UserInteractiveActivity ) ||
				activities[ id ].getState() !== workflows.state.activity.STARTED
			) {
				continue;
			}
			activity = activities[ id ];
			if ( activity.isUserTargeted( mw.config.get( 'wgUserName' ) ) ) {
				return activity;
			}
		}

		return null;
	};
}( mediaWiki, jQuery ) );

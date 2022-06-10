workflows.store.Workflows = function ( cfg ) {
	this.filterData = cfg.filterData || {};
	this.total = 0;
	cfg.remoteSort = false;

	workflows.store.Workflows.parent.call( this, cfg );
};

OO.inheritClass( workflows.store.Workflows, OOJSPlus.ui.data.store.Store );

workflows.store.Workflows.prototype.doLoadData = function() {
	var dfd = $.Deferred(),
		data = [],
		active = ( this.filterData.hasOwnProperty( 'active' ) && this.filterData.active ) || 0;

	workflows.list.filtered( active, this.filterData, true, this.offset, this.limit ).done(
		function( response ) {
			if ( !response.hasOwnProperty( 'workflows' ) ) {
				return;
			}
			var states = [], types = [],workflows = response.workflows;
			for ( var id in workflows ) {
				if ( !workflows.hasOwnProperty( id ) ) {
					continue;
				}
				var page = null;
				if ( workflows[id].contextPage ) {
					page = new mw.Title( workflows[id].contextPage );
				}

				var current = '-';
				if ( workflows[id].state === 'running' ) {
					for( var task in workflows[id].tasks ) {
						if ( task === workflows[id].current[0] ) {
							current = workflows[id].tasks[task].description.taskName;
						}
					}
				}

				var state = mw.message( 'workflows-ui-overview-details-state-' + workflows[id].state ).text();
				if ( states.indexOf( state ) === -1 ) {
					states.push( state );
				}
				var title = workflows[id].definition.title;
				if ( types.indexOf( title ) === -1 ) {
					types.push( title );
				}
				data.push( {
					id: id,
					title: workflows[id].definition.title,
					page: page ? page.getPrefixedText() : '',
					page_link: page ? page.getUrl() : '',
					assignee: this.getAssignee( workflows[id] ),
					state: this.getState( workflows[id].state ),
					notice: this.getNotice( workflows[id] ),
					start: workflows[id].timestamps.start,
					startDate: workflows[id].timestamps.startDate,
					last: workflows[id].timestamps.last,
					lastDate: workflows[id].timestamps.lastDate,
				} );
			}
			this.total = response.total;
			dfd.resolve( this.indexData( data ) );
		}.bind( this )
	)
	.fail( function( response ) {
		dfd.reject( response.error.message || false );
	}.bind( this ) );

	return dfd.promise();
};

workflows.store.Workflows.prototype.getNotice = function( workflow ) {
	// Return "warning" or "error"
	if ( this.isAutoAbort( workflow ) ) {
		return 'error';
	}
	return '';
};

workflows.store.Workflows.prototype.isAutoAbort = function( workflow ) {
	var message = workflow.stateMessage;
	if ( typeof message === 'object' ) {
		return message.isAuto;
	}

	return false;
};

workflows.store.Workflows.prototype.getFiltersForRemote = function() {
	var filters = [];
	for ( var field in this.filters ) {
		if ( !this.filters.hasOwnProperty( field ) ) {
			continue;
		}
		filters.push(
			$.extend( {}, this.filters[field].getValue(), { property: field } )
		);
	}

	return JSON.stringify( filters );
};

workflows.store.Workflows.prototype.filter = function( data ) {
	this.filterData = data;
	return this.reload();
};

workflows.store.Workflows.prototype.getAssignee = function( workflow ) {
	var assigned = this.getAssignedUsers( workflow );
	if ( assigned.length > 0 ) {
		return assigned;
	}
	return '-';
};

workflows.store.Workflows.prototype.getAssignedUsers = function( workflow ) {
	var assigned = [];
	for ( var i = 0; i < workflow.current.length; i++ ) {
		if ( !workflow.tasks.hasOwnProperty( workflow.current[i] ) ) {
			continue;
		}
		var activity = workflow.tasks[workflow.current[i]];
		if ( activity.elementName !== 'userTask' ) {
			continue;
		}
		if ( typeof activity.properties.assigned_user === 'object' ) {
			assigned = assigned.concat( activity.properties.assigned_user );
		} else {
			assigned.push( activity.properties.assigned_user );
		}
	}

	return assigned;
};

workflows.store.Workflows.prototype.getState = function( state ) {
	return new OO.ui.LabelWidget( {
		title: mw.message( 'workflows-ui-overview-details-state-' + state ).text(),
		classes: [ 'workflow-state', 'workflow-state-icon-' + state ]
	} ).$element;
};

workflows.store.Workflows.prototype.getSortForRemote = function() {
	var sorters = [];
	for ( var field in this.sorters ) {
		if ( !this.sorters.hasOwnProperty( field ) ) {
			continue;
		}
		sorters.push(
			$.extend( {}, this.sorters[field].getValue(), { property: field } )
		);
	}

	return JSON.stringify( sorters );
};

workflows.store.Workflows.prototype.getTotal = function() {
	return this.total;
};

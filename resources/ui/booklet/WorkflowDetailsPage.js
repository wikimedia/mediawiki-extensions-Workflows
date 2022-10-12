( function ( mw, $, wf ) {
	workflows.ui.WorkflowDetailsPage = function( name, cfg ) {
		workflows.ui.WorkflowDetailsPage.parent.call( this, name, cfg );
		this.panel = new OO.ui.PanelLayout( {
			padded: false,
			expanded: false
		} );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.WorkflowDetailsPage, OO.ui.PageLayout );

	workflows.ui.WorkflowDetailsPage.prototype.init = function( workflow ) {
		this.panel.$element.children().remove();

		this.workflow = workflow;
		this.addDetailsSection();

		this.addActivities();
	};

	workflows.ui.WorkflowDetailsPage.prototype.addDetailsSection = function() {
		this.addSection( 'details', 'article' );
		this.detailsPanel = new OO.ui.PanelLayout( {
			expanded: false,
			padded: true,
			classes: [ 'details-panel' ]
		} );
		this.$detailsPanelTable = $( '<table>' ).append( $('<colgroup>')
		.append( $('<col span="1" style="width: 30%;">')
		.append( $('<col span="1" style="width: 70%;">'))) );;
		this.detailsPanel.$element.append( this.$detailsPanelTable );
		this.panel.$element.append( this.detailsPanel.$element );
		this.addContextPage();
		this.addInitiator();
		this.addTimestamps();

		this.addState();
	};

	workflows.ui.WorkflowDetailsPage.prototype.addDefinition = function() {
		var definition = this.workflow.getDefinition();
		var title = new OO.ui.LabelWidget( {
			label: definition.title,
			classes: [ 'overview-title' ]
		} );
		this.headerPanel.addItems( [ title ] );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addTimestamps = function() {
		var timestamps = this.workflow.getTimestamps();

		this.$detailsPanelTable.append( $('<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-start-time', '' ).text() ),
			$( '<td>' ).append( timestamps.startDateAndTime  )
		));

		var messageKey = '';
		if ( this.workflow.getState() !== 'finished' ) {
			messageKey = 'workflows-ui-overview-details-last-time';
		} else {
			messageKey = 'workflows-ui-overview-details-end-time';
		}
		this.$detailsPanelTable.append( $('<tr>' ).append(
			$( '<th>' ).text( mw.message(
				messageKey,
				''
			) ),
			$( '<td>' ).append( timestamps.lastDateAndTime  )
		));
	};

	workflows.ui.WorkflowDetailsPage.prototype.addInitiator = function() {
		var initiator = this.workflow.getInitiator();
		if ( !initiator ) {
			return;
		}
		var userWidget = new OOJSPlus.ui.widget.UserWidget( {
			user_name: initiator,  showLink: true
		} );

		this.$detailsPanelTable.append( $('<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-initiator' ).text() ),
			$( '<td>' ).append( userWidget.$element )
		));
	};

	workflows.ui.WorkflowDetailsPage.prototype.addState = function() {
		var state = this.workflow.getState();
		var label = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-state-label' ).text()
		} );

		var stateClass = 'workflow-state-inactive';
		if ( state === 'finished' ) {
			stateClass = 'workflow-state-finished';
		} else if ( state === 'running' ) {
			stateClass = 'workflow-state-active';
		}
		var stateLabel = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-state-' + state ).text(),
			classes: [ stateClass ]
		} );

		var layout = new OO.ui.HorizontalLayout( {
			items: [ label, stateLabel ],
			classes: [ 'overview-state-layout' ]
		} );

		var stateMessage = this.workflow.getStateMessage();
		if ( stateMessage ) {
			if ( typeof stateMessage === 'string' ) {
				var stateComment = new OO.ui.PopupButtonWidget( {
					icon: 'info',
					framed: false,
					label: mw.message( 'workflows-ui-overview-details-state-comment' ).text(),
					invisibleLabel: true,
					popup: {
						head: true,
						label: mw.message( 'workflows-ui-overview-details-state-comment' ).text(),
						$content: $( '<span>' ).text( '"' + stateMessage + '"' ),
						padded: true
					}
				} );
				layout.$element.append( stateComment.$element );
			}
			if( state === 'aborted' && typeof stateMessage === 'object' ) {
				if ( stateMessage.isAuto ) {
					var autoAbort = new OO.ui.PopupButtonWidget( {
						icon: 'error',
						framed: false,
						label: mw.message( 'workflows-ui-overview-details-state-comment' ).text(),
						invisibleLabel: true,
						popup: {
							head: true,
							label: mw.message( 'workflows-ui-overview-details-state-autoabort-comment' ).text(),
							$content: $( '<span>' ).text( stateMessage.message ),
							padded: true
						}
					} );
					layout.$element.append( autoAbort.$element );
				}
			}
		}
		this.sectionLayout.addItems( [ layout ] );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addActivities = function() {
		if ( this.workflow.getState() === 'running' ) {
			this.addSection( 'activity', 'userContributions' );
			this.addCurrentActivities();
		}
		if ( this.isExpired() ) {
			this.addSection( 'expired', 'clock' );
			this.addCurrentActivities();
		}

		var pastActivities = this.getPastActivities();
		if ( pastActivities.length > 0 ) {
			this.addSection( 'past', 'clock' );
			this.addPastActivities( pastActivities );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addCurrentActivities = function() {
		var current = this.workflow.getCurrent();
		if ( !current ) {
			this.noCurrentActivity();
			return;
		}
		for ( var name in current ) {
			if ( !current.hasOwnProperty( name ) ) {
				continue;
			}
			this.addActivity( current[name] );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addPastActivities = function( activities ) {
		for ( var i = 0; i < activities.length; i++ ) {
			var activity = activities[i];

			if ( activity.initializer ) {
				continue;
			}

			var localizedProperties = activity.getDisplayData().localizedProperties || {};

			var name = new OO.ui.LabelWidget( {
					label: activity.description.taskName,
					classes: [ 'name' ]
				} ),
				rawData = new workflows.ui.widget.ActivityRawDataPopup( localizedProperties ),
				layout = new OO.ui.PanelLayout( {
					expanded: false,
					padded: true,
					classes: [ 'overview-activity-layout' ]
				} );

			layout.$element.append( name.$element, rawData.$element );

			this.panel.$element.append(
				layout.$element
			);
			var historyWidget = this.getActivityHistory( activity, false );
			if ( historyWidget ) {
				layout.$element.append( historyWidget.$element );
			}
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.getPastActivities = function() {
		var taskKeys = this.workflow.getTaskKeys(),
			activities = [];
		for ( var i = 0; i < taskKeys.length; i ++ ) {
			var task = this.workflow.getTask( taskKeys[i] );
			if ( task.state !== workflows.state.activity.COMPLETE ) {
				continue;
			}
			if ( !task instanceof workflows.object.UserInteractiveActivity )  {
				continue;
			}
			if ( typeof task.getHistory !== 'function' ) {
				continue;
			}
			activities.push( task );
		}

		return activities;
	};

	workflows.ui.WorkflowDetailsPage.prototype.getActivityHistory = function( activity, includeHeader ) {
		includeHeader = includeHeader || false;
		var history = activity.getHistory() || {};

		if (
			$.isEmptyObject( history ) ||
			( Array.isArray( history ) && history.length === 0 )
		) {
			return null;
		}
		var historyPanel = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false,
			classes: [ 'workflow-details-history' ]
		} );
		if ( includeHeader ) {
			historyPanel.$element.append(
				new OO.ui.LabelWidget( {
					label: 'History'
				} ).$element
			);
		}
		for ( var key in history ) {
			if ( !history.hasOwnProperty( key ) ) {
				continue;
			}
			historyPanel.$element.append(
				new OO.ui.HorizontalLayout( {
					items: [
						new OO.ui.LabelWidget( {
							label: key,
							classes: [ 'history-item' ]
						} ),
						new OO.ui.LabelWidget( {
							label: history[key],
							classes: [ 'history-value' ]
						} )
					]
				} ).$element
			);
		}

		return historyPanel;
	};

	workflows.ui.WorkflowDetailsPage.prototype.addSection = function( name, icon ) {
		var iconWidget = new OO.ui.IconWidget( {
			icon: icon
		} );
		// workflows-ui-overview-details-section-page
		// workflows-ui-overview-details-section-details
		// workflows-ui-overview-details-section-expired
		// workflows-ui-overview-details-section-activity
		// workflows-ui-overview-details-section-past
		var label = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-section-' + name ).text()
		} );

		this.sectionLayout = new OO.ui.HorizontalLayout( {
			items: [ iconWidget, label ],
			classes: [ 'overview-section-label' ]
		} );

		this.panel.$element.append( this.sectionLayout.$element );
	};

	workflows.ui.WorkflowDetailsPage.prototype.noCurrentActivity = function() {
		this.panel.$element.append(
			new OO.ui.LabelWidget( {
				label: mw.message( 'workflows-ui-overview-details-no-current-activity' ).text()
			} ).$element
		);
	};

	workflows.ui.WorkflowDetailsPage.prototype.addContextPage = function() {
		var context = this.workflow.getContext();
		if ( !context || !context.hasOwnProperty( 'pageId' ) || !this.workflow.getContextPage() ) {
			return;
		}

		var title = new mw.Title( this.workflow.getContextPage() );
		var titleButton = new OO.ui.ButtonWidget( {
			framed: false,
			label: title.getMainText(),
			href: title.getUrl(),
			flags: 'progressive',
			target: '_new'
		} );

		var initialData = this.getInitialData();
		var initialRawDataPopup = new workflows.ui.widget.InitialRawDataPopup( initialData );

		this.$detailsPanelTable.append( $('<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-page-context-page' ).text() ),
			$( '<td>' ).append( titleButton.$element ),
			$( '<td>' ).append( initialRawDataPopup.$element )
		) );

		if ( context.hasOwnProperty( 'revision' ) ) {
			var revisionButton = new OO.ui.ButtonWidget( {
				framed: false,
				label: context.revision.toString(),
				href: title.getUrl( { oldid: context.revision } ),
				target: '_new'
			} );
			this.$detailsPanelTable.append( $('<tr>' ).append(
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-page-context-revision' ).text() ),
				$( '<td>' ).append( revisionButton.$element )
			) );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addActivity = function( activity ) {
		if ( !activity instanceof workflows.object.Activity ) {
			return;
		}
		var name = new OO.ui.LabelWidget( {
				label: activity.description.taskName,
				classes: [ 'name' ]
			} ),
			layout = new OO.ui.PanelLayout( {
				expanded: false,
				padded: true,
				classes: [ 'overview-activity-layout' ]
			} );

		layout.$element.append( name.$element );

		if ( activity instanceof workflows.object.UserInteractiveActivity ) {
			var assignedUsersLayout = new OO.ui.HorizontalLayout();
			var targetUsers = activity.targetUsers;
			if ( !targetUsers ) {
				assignedUsersLayout.$element.append( new OO.ui.LabelWidget( {
					label: mw.message( 'workflows-ui-overview-details-activity-assigned-users-none' ).text()
				} ).$element );
			} else {
				for ( var i = 0; i < targetUsers.length; i++ ) {
					var userWidget = new OOJSPlus.ui.widget.UserWidget( {
						user_name: targetUsers[i],  showLink: true
					} );
					assignedUsersLayout.$element.append( userWidget.$element );
				}
			}
			var $table = $('<table>').append( $('<colgroup>')
				.append( $('<col span="1" style="width: 30%;">')
				.append( $('<col span="1" style="width: 70%;">'))) );
			layout.$element.append(  $table.append( $('<tr>' ).append(
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-activity-assigned-users' ).text() ),
				$( '<td>' ).append( assignedUsersLayout.$element )
			) ) );

			var dueDate = activity.getDescription().dueDate;
			if ( dueDate ) {
				var proximity = activity.getDescription().dueDateProximity;
				var labelDue = new OO.ui.LabelWidget( {
					label: mw.message( "workflows-ui-overview-details-due-date-label" ).text()
				} );

				var label = new OO.ui.LabelWidget( {
					label: dueDate,
					classes: [ 'proximity' ]
				} );

				var dueDateLayout = new OO.ui.HorizontalLayout( {
					items: [ labelDue, label ],
					classes: [ 'proximity-layout' ]
				} );
				if ( typeof proximity === 'number' && proximity < 3 && proximity >= 0 ) {
					label.$element.addClass( 'proximity-close' );
				}
				if ( typeof proximity === 'number' && proximity < 0 ) {
					label.$element.addClass( 'proximity-overdue' );
				}
				this.sectionLayout.addItems( dueDateLayout );
			}

			if ( !$.isEmptyObject( activity.getHistory() ) ) {
				var historyWidget = this.getActivityHistory( activity, true );
				if ( historyWidget ) {
					layout.$element.append( historyWidget.$element );
				}
			}
		} else {
			layout.$element.append( new OO.ui.LabelWidget( {
					label: mw.message( 'workflows-ui-overview-details-activity-automatic' ).text()
				} ).$element
			);
		}

		this.panel.$element.append(
			layout.$element
		);
	};

	workflows.ui.WorkflowDetailsPage.prototype.getInitialData = function() {
		var activities = this.getPastActivities();
		for ( var i = 0; i < activities.length; i++ ) {
			var activity = activities[i];

			if ( activity.initializer ) {
				return activity.getDisplayData().localizedProperties || {};
			}
		}

		return {};
	};

	workflows.ui.WorkflowDetailsPage.prototype.getTitle = function() {
		if ( this.workflow ) {
			var definition = this.workflow.getDefinition();
			return definition.title;
		}
		return mw.message( 'workflows-ui-workflow-overview-dialog-title' ).text();
	};

	workflows.ui.WorkflowDetailsPage.prototype.isExpired = function() {
		return this.workflow.getState() === 'aborted' &&
			typeof this.workflow.getStateMessage() === 'object' &&
			this.workflow.getStateMessage().type === 'duedate';
	};
} )( mediaWiki, jQuery, workflows );

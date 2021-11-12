( function ( mw, $, wf ) {
	workflows.ui.WorkflowDetailsPage = function( name, cfg ) {
		workflows.ui.WorkflowDetailsPage .parent.call( this, name, cfg );
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
		this.addDefinition();
		this.addTimestamps();
		this.addInitiator();
		this.addState();
		this.addContextPage();
		this.addActivities();
	};

	workflows.ui.WorkflowDetailsPage.prototype.addDefinition = function() {
		var definition = this.workflow.getDefinition();
		var title = new OO.ui.LabelWidget( {
			label: definition.title,
			classes: [ 'overview-title' ]
		} );
		this.panel.$element.append( title.$element );
		if ( definition.desc ) {
			var desc = new OO.ui.LabelWidget( {
				label: definition.desc,
				classes: [ 'overview-desc' ]
			} );
			this.panel.$element.append( desc.$element );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addTimestamps = function() {
		var timestamps = this.workflow.getTimestamps();
		var start = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-start-time', timestamps.startFormatted ).text()
		} );

		var last = new OO.ui.LabelWidget( {
			label: mw.message(
				this.workflow.getState() !== 'finished' ? 'workflows-ui-overview-details-last-time' : 'workflows-ui-overview-details-end-time',
				timestamps.lastFormatted
			).text()
		} );

		this.panel.$element.append(
			new OO.ui.HorizontalLayout( {
				items: [ start, last ]
			} ).$element
		);
	};

	workflows.ui.WorkflowDetailsPage.prototype.addInitiator = function() {
		var initiator = this.workflow.getInitiator();
		if ( !initiator ) {
			return;
		}
		var title = mw.Title.makeTitle( 2, initiator );
		if ( !title ) {
			return;
		}
		this.panel.$element.append(
			new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.LabelWidget( {
						label: mw.message( "workflows-ui-overview-details-initiator" ).text()
					} ),
					new OO.ui.ButtonWidget( {
						label: initiator,
						href: title.getUrl(),
						framed: false,
						flags: 'progressive',
						target: '_new'
					} )
				]
			} ).$element
		);
	};

	workflows.ui.WorkflowDetailsPage.prototype.addState = function() {
		var state = this.workflow.getState();
		var label = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-state-label' ).text()
		} );
		var stateLabel = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-state-' + state ).text(),
			classes: state === 'running' ? [ 'workflow-state-active' ] : [ 'workflow-state-inactive' ]
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
		this.panel.$element.append( layout.$element );
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

	workflows.ui.WorkflowDetailsPage.prototype.addSection = function( name, icon ) {
		var iconWidget = new OO.ui.IconWidget( {
			icon: icon
		} );
		var label = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-section-' + name ).text()
		} );

		this.panel.$element.append(
			new OO.ui.HorizontalLayout( {
				items: [ iconWidget, label ],
				classes: [ 'overview-section-label' ]
			} ).$element
		);
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
		this.addSection( 'page', 'article' );
		var panel = new OO.ui.PanelLayout( {
			padded: true, expanded: false,
			classes: [ 'overview-activity-layout' ]
		} );

		var title = new mw.Title( this.workflow.getContextPage() );
		var titleButton = new OO.ui.ButtonWidget( {
			framed: false,
			label: title.getMainText(),
			href: title.getUrl(),
			flags: 'progressive',
			target: '_new'
		} );
		panel.$element.append( new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.LabelWidget( {
						label: mw.message( 'workflows-ui-overview-details-page-context-page' ).text()
					} ),
					titleButton
				]
			} ).$element
		);

		if ( context.hasOwnProperty( 'revision' ) ) {
			var revisionButton = new OO.ui.ButtonWidget( {
				framed: false,
				label: context.revision.toString(),
				href: title.getUrl( { oldid: context.revision } ),
				target: '_new'
			} );
			panel.$element.append( new OO.ui.HorizontalLayout( {
					items: [
						new OO.ui.LabelWidget( {
							label: mw.message( 'workflows-ui-overview-details-page-context-revision' ).text()
						} ),
						revisionButton
					]
				} ).$element
			);
		}

		this.panel.$element.append( panel.$element );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addActivity = function( activity ) {
		if ( !activity instanceof workflows.object.Activity ) {
			return;
		}
		var name = new OO.ui.LabelWidget( {
			label: activity.name,
			classes: [ 'name' ]
		} ),
			layout = new OO.ui.PanelLayout( {
				expanded: false,
				padded: true,
				classes: [ 'overview-activity-layout' ]
			} );

		layout.$element.append( name.$element );

		if ( activity instanceof workflows.object.UserInteractiveActivity ) {
			var assignedUsersLayout = new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.LabelWidget( {
						label: mw.message( 'workflows-ui-overview-details-activity-assigned-users' ).text()
					} )
				]
			} );
			var targetUsers = activity.targetUsers;
			if ( !targetUsers ) {
				assignedUsersLayout.$element.append( new OO.ui.LabelWidget( {
					label: mw.message( 'workflows-ui-overview-details-activity-assigned-users-none' ).text()
				} ).$element );
			} else {
				for ( var i = 0; i < targetUsers.length; i++ ) {
					var userPage = new mw.Title( 'User:' +  targetUsers[i].charAt(0).toUpperCase() + targetUsers[i].slice(1) );
					assignedUsersLayout.$element.append(
						new OO.ui.ButtonWidget( {
							framed: false,
							label: userPage.getMainText(),
							href: userPage.getUrl(),
							flags: 'progressive',
							target: '_new'
						} ).$element
					);
				}
			}
			layout.$element.append( assignedUsersLayout.$element );

			var dueDate = activity.getDescription().dueDate;
			if ( dueDate ) {
				var proximity = activity.getDescription().dueDateProximity;
				var icon = new OO.ui.IconWidget( { icon: 'clock' } );
				var label = new OO.ui.LabelWidget( {
					title: mw.message( 'workflows-ui-overview-details-activity-due-date' ).text(),
					label: dueDate
				} );

				var dueDateLayout = new OO.ui.HorizontalLayout( {
					items: [ icon, label ],
					classes: [ 'proximity-layout' ]
				} );
				if ( proximity && proximity < 3 && proximity >= 0 ) {
					dueDateLayout.$element.addClass( 'proximity-close' );
				}
				if ( proximity && proximity < 0 ) {
					dueDateLayout.$element.addClass( 'proximity-overdue' );
				}
				layout.$element.append( dueDateLayout.$element );
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

	workflows.ui.WorkflowDetailsPage.prototype.getTitle = function() {
		return mw.message( 'workflows-ui-workflow-overview-dialog-title' ).text();
	};

	workflows.ui.WorkflowDetailsPage.prototype.isExpired = function() {
		return this.workflow.getState() === 'aborted' &&
			typeof this.workflow.getStateMessage() === 'object' &&
			this.workflow.getStateMessage().type === 'duedate';
	};
} )( mediaWiki, jQuery, workflows );

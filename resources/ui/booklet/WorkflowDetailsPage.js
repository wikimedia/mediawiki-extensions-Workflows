( function ( mw, $ ) {
	workflows.ui.WorkflowDetailsPage = function ( name, cfg ) {
		workflows.ui.WorkflowDetailsPage.parent.call( this, name, cfg );
		this.panel = new OO.ui.PanelLayout( {
			padded: false,
			expanded: false
		} );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.WorkflowDetailsPage, OO.ui.PageLayout );

	workflows.ui.WorkflowDetailsPage.prototype.init = function ( workflow ) {
		this.panel.$element.children().remove();

		this.workflow = workflow;
		this.addDetailsSection();

		this.addActivities();
	};

	workflows.ui.WorkflowDetailsPage.prototype.addDetailsSection = function () {
		this.addSection( 'details', 'article' );
		this.detailsPanel = new OO.ui.PanelLayout( {
			expanded: false,
			padded: true,
			classes: [ 'details-panel' ]
		} );
		this.$detailsPanelTable = $( '<table>' ).append( $( '<colgroup>' )
			.append( $( '<col span="1" style="width: 30%;">' ) // eslint-disable-line no-jquery/no-parse-html-literal
				.append( $( '<col span="1" style="width: 70%;">' ) ) ) ); // eslint-disable-line no-jquery/no-parse-html-literal
		this.detailsPanel.$element.append( this.$detailsPanelTable );
		this.panel.$element.append( this.detailsPanel.$element );
		this.addContextPage();
		this.addInitiator();
		this.addTimestamps();

		this.addState();
	};

	workflows.ui.WorkflowDetailsPage.prototype.addDefinition = function () {
		const definition = this.workflow.getDefinition();
		const title = new OO.ui.LabelWidget( {
			label: definition.title,
			classes: [ 'overview-title' ]
		} );
		this.headerPanel.addItems( [ title ] );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addTimestamps = function () {
		const timestamps = this.workflow.getTimestamps();

		this.$detailsPanelTable.append( $( '<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-start-time', '' ).text() ),
			$( '<td>' ).append( timestamps.startDateAndTime )
		) );

		let messageKey = '';
		if ( this.workflow.getState() !== 'finished' ) {
			messageKey = 'workflows-ui-overview-details-last-time';
		} else {
			messageKey = 'workflows-ui-overview-details-end-time';
		}
		this.$detailsPanelTable.append( $( '<tr>' ).append(
			$( '<th>' ).text( mw.message( // eslint-disable-line mediawiki/msg-doc
				messageKey,
				''
			) ),
			$( '<td>' ).append( timestamps.lastDateAndTime )
		) );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addInitiator = function () {
		const initiator = this.workflow.getInitiator();
		if ( !initiator ) {
			return;
		}
		const userWidget = new OOJSPlus.ui.widget.UserWidget( {
			user_name: initiator, showLink: true, showRawUsername: false
		} );

		this.$detailsPanelTable.append( $( '<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-initiator' ).text() ),
			$( '<td>' ).append( userWidget.$element )
		) );
	};

	workflows.ui.WorkflowDetailsPage.prototype.addState = function () {
		const state = this.workflow.getState();
		const label = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-state-label' ).text()
		} );

		let stateClass = 'workflow-state-inactive';
		if ( state === 'finished' ) {
			stateClass = 'workflow-state-finished';
		} else if ( state === 'running' ) {
			stateClass = 'workflow-state-active';
		}
		const stateLabel = new OO.ui.LabelWidget( { // eslint-disable-line mediawiki/class-doc
			label: mw.message( 'workflows-ui-overview-details-state-' + state ).text(), // eslint-disable-line mediawiki/msg-doc
			classes: [ stateClass ]
		} );

		const layout = new OO.ui.HorizontalLayout( {
			items: [ label, stateLabel ],
			classes: [ 'overview-state-layout' ]
		} );

		const stateMessage = this.workflow.getStateMessage();
		if ( stateMessage ) {
			if ( typeof stateMessage === 'string' ) {
				const stateComment = new OO.ui.PopupButtonWidget( {
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
			if ( state === 'aborted' && typeof stateMessage === 'object' ) {
				if ( stateMessage.isAuto ) {
					const autoAbort = new OO.ui.PopupButtonWidget( {
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

	workflows.ui.WorkflowDetailsPage.prototype.addActivities = function () {
		if ( this.workflow.getState() === 'running' ) {
			this.addSection( 'activity', 'userContributions' );
			this.addCurrentActivities();
		}
		if ( this.isExpired() ) {
			this.addSection( 'expired', 'clock' );
			this.addCurrentActivities();
		}

		const pastActivities = this.getPastActivities();
		if ( pastActivities.length > 0 ) {
			this.addSection( 'past', 'clock' );
			this.addPastActivities( pastActivities );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addCurrentActivities = function () {
		const current = this.workflow.getCurrent();
		if ( !current ) {
			this.noCurrentActivity();
			return;
		}
		for ( const name in current ) {
			if ( !current.hasOwnProperty( name ) ) {
				continue;
			}
			this.addActivity( current[ name ], name );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addPastActivities = function ( activities ) {
		for ( let i = 0; i < activities.length; i++ ) {
			const activity = activities[ i ];

			if ( activity.initializer ) {
				continue;
			}

			const localizedProperties = activity.getDisplayData().localizedProperties || {};

			const name = new OO.ui.LabelWidget( {
					label: activity.getDescription().taskName,
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
			const historyWidget = this.getActivityHistory( activity );
			if ( historyWidget ) {
				layout.$element.append( historyWidget.$element );
			}
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.getPastActivities = function () {
		const taskKeys = this.workflow.getTaskKeys(),
			activities = [];
		for ( let i = 0; i < taskKeys.length; i++ ) {
			const task = this.workflow.getTask( taskKeys[ i ] );
			if ( task.state !== workflows.state.activity.COMPLETE ) {
				continue;
			}
			if ( !( task instanceof workflows.object.DescribedActivity ) ) {
				continue;
			}
			if ( typeof task.getHistory !== 'function' ) {
				continue;
			}
			activities.push( task );
		}

		return activities;
	};

	workflows.ui.WorkflowDetailsPage.prototype.getActivityHistory = function ( activity ) {
		const history = activity.getHistory() || {};

		if (
			$.isEmptyObject( history ) ||
			( Array.isArray( history ) && history.length === 0 )
		) {
			return null;
		}
		const historyPanel = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false,
			classes: [ 'workflow-details-history' ]
		} );
		for ( const key in history ) {
			if ( !history.hasOwnProperty( key ) ) {
				continue;
			}
			historyPanel.$element.append(
				new OO.ui.HorizontalLayout( {
					classes: [ 'history-item-wrapper' ],
					items: [
						new OO.ui.LabelWidget( {
							label: key,
							classes: [ 'history-item' ]
						} ),
						new OO.ui.LabelWidget( {
							label: history[ key ],
							classes: [ 'history-value' ]
						} )
					]
				} ).$element
			);
		}

		return historyPanel;
	};

	workflows.ui.WorkflowDetailsPage.prototype.addSection = function ( name, icon ) {
		const iconWidget = new OO.ui.IconWidget( {
			icon: icon
		} );
		const label = new OO.ui.LabelWidget( {
			// The following messages are used here:
			// * workflows-ui-overview-details-section-page
			// * workflows-ui-overview-details-section-details
			// * workflows-ui-overview-details-section-expired
			// * workflows-ui-overview-details-section-activity
			// * workflows-ui-overview-details-section-past
			label: mw.message( 'workflows-ui-overview-details-section-' + name ).text()
		} );

		this.sectionLayout = new OO.ui.HorizontalLayout( {
			items: [ iconWidget, label ],
			classes: [ 'overview-section-label' ]
		} );

		this.panel.$element.append( this.sectionLayout.$element );
	};

	workflows.ui.WorkflowDetailsPage.prototype.noCurrentActivity = function () {
		this.panel.$element.append(
			new OO.ui.LabelWidget( {
				label: mw.message( 'workflows-ui-overview-details-no-current-activity' ).text()
			} ).$element
		);
	};

	workflows.ui.WorkflowDetailsPage.prototype.addContextPage = function () {
		const context = this.workflow.getContext();
		if ( !context || !context.hasOwnProperty( 'pageId' ) || !this.workflow.getContextPage() ) {
			return;
		}

		const title = new mw.Title( this.workflow.getContextPage() );
		const titleButton = new OO.ui.ButtonWidget( {
			framed: false,
			label: title.getMainText(),
			href: title.getUrl(),
			flags: 'progressive',
			target: '_new'
		} );

		const initialData = this.getInitialData();
		const initialRawDataPopup = new workflows.ui.widget.InitialRawDataPopup( initialData );

		this.$detailsPanelTable.append( $( '<tr>' ).append(
			$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-page-context-page' ).text() ),
			$( '<td>' ).append( titleButton.$element ),
			$( '<td>' ).append( initialRawDataPopup.$element )
		) );

		if ( context.hasOwnProperty( 'revision' ) ) {
			const revisionButton = new OO.ui.ButtonWidget( {
				framed: false,
				label: context.revision.toString(),
				href: title.getUrl( { oldid: context.revision } ),
				target: '_new'
			} );
			this.$detailsPanelTable.append( $( '<tr>' ).append(
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-page-context-revision' ).text() ),
				$( '<td>' ).append( revisionButton.$element )
			) );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.addActivity = function ( activity, rawName ) {
		if ( !( activity instanceof workflows.object.Activity ) ) {
			return;
		}
		const isDescribed = activity instanceof workflows.object.DescribedActivity;
		const name = new OO.ui.LabelWidget( {
				label: isDescribed ? activity.getDescription().taskName : rawName,
				classes: [ 'name' ]
			} ),
			layout = new OO.ui.PanelLayout( {
				expanded: false,
				padded: true,
				classes: [ 'overview-activity-layout' ]
			} );

		layout.$element.append( name.$element );

		if ( isDescribed ) {
			if ( activity instanceof workflows.object.UserInteractiveActivity ) {
				const assignedUsersLayout = new OO.ui.HorizontalLayout();
				const targetUsers = activity.targetUsers;
				if ( !targetUsers ) {
					assignedUsersLayout.$element.append( new OO.ui.LabelWidget( {
						label: mw.message( 'workflows-ui-overview-details-activity-assigned-users-none' ).text()
					} ).$element );
				} else {
					this.appendTargetUsers( targetUsers, assignedUsersLayout );
				}
				const $table = $( '<table>' ).append( $( '<colgroup>' )
					.append( $( '<col span="1" style="width: 30%;">' ) // eslint-disable-line no-jquery/no-parse-html-literal
						.append( $( '<col span="1" style="width: 70%;">' ) ) ) ); // eslint-disable-line no-jquery/no-parse-html-literal
				layout.$element.append( $table.append( $( '<tr>' ).append(
					$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-activity-assigned-users' ).text() ),
					$( '<td>' ).append( assignedUsersLayout.$element )
				) ) );

				const dueDate = activity.getDescription().dueDate;
				if ( dueDate ) {
					const proximity = activity.getDescription().dueDateProximity;
					const labelDue = new OO.ui.LabelWidget( {
						label: mw.message( 'workflows-ui-overview-details-due-date-label' ).text()
					} );

					const label = new OO.ui.LabelWidget( {
						label: dueDate,
						classes: [ 'proximity' ]
					} );

					const dueDateLayout = new OO.ui.HorizontalLayout( {
						items: [ labelDue, label ],
						classes: [ 'proximity-layout' ]
					} );
					if ( typeof proximity === 'number' && proximity < 3 && proximity >= 0 ) {
						label.$element.addClass( 'proximity-close' );
					}
					if ( typeof proximity === 'number' && proximity < 0 ) {
						label.$element.addClass( 'proximity-overdue' );
					}
					this.sectionLayout.addItems( [ dueDateLayout ] );
				}
			}

			if ( !$.isEmptyObject( activity.getHistory() ) ) {
				const historyWidget = this.getActivityHistory( activity );
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

	workflows.ui.WorkflowDetailsPage.prototype.getInitialData = function () {
		const activities = this.getPastActivities();
		for ( let i = 0; i < activities.length; i++ ) {
			const activity = activities[ i ];

			if ( activity.initializer ) {
				return activity.getDisplayData().localizedProperties || {};
			}
		}

		return {};
	};

	workflows.ui.WorkflowDetailsPage.prototype.getTitle = function () {
		if ( this.workflow ) {
			const definition = this.workflow.getDefinition();
			return definition.title;
		}
		return mw.message( 'workflows-ui-workflow-overview-dialog-title' ).text();
	};

	workflows.ui.WorkflowDetailsPage.prototype.isExpired = function () {
		return this.workflow.getState() === 'aborted' &&
			typeof this.workflow.getStateMessage() === 'object' &&
			this.workflow.getStateMessage().type === 'duedate';
	};

	workflows.ui.WorkflowDetailsPage.prototype.appendTargetUsers = function ( users, layout ) {
		let displayUsers = users,
			moreUsers = [];
		if ( users.length > 3 ) {
			displayUsers = users.slice( 0, 3 );
			moreUsers = users.slice( 3 );
		}
		for ( let i = 0; i < displayUsers.length; i++ ) {
			const userWidget = new OOJSPlus.ui.widget.UserWidget( {
				user_name: displayUsers[ i ], showLink: true, showRawUsername: false,
				classes: [ 'workflow-details-user-widget' ]
			} );
			userWidget.$element.css( 'display', 'block' );
			layout.$element.append( userWidget.$element );
		}
		if ( moreUsers.length > 0 ) {
			const $popupContent = this.getMoreUsersPopup( moreUsers ),
				popup = new OO.ui.PopupButtonWidget( {
					framed: false,
					label: mw.message( 'workflows-ui-overview-details-activity-assigned-users-more', moreUsers.length ).text(),
					popup: {
						$content: $popupContent,
						height: this.getTrueDimensions( $popupContent ).height + 20,
						padded: true,
						align: 'forwards',
						autoFlip: true
					}
				} );
			popup.popup.connect( this, {
				ready: function () {
					popup.popup.$body.css( 'height', '100%' );
				}
			} );
			layout.$element.append( popup.$element );
		}
	};

	workflows.ui.WorkflowDetailsPage.prototype.getMoreUsersPopup = function ( users ) {
		const $panel = $( '<div>' ).addClass( 'workflow-details-more-users-popup' );
		for ( let i = 0; i < users.length; i++ ) {
			const userWidget = new OOJSPlus.ui.widget.UserWidget( {
				user_name: users[ i ], showLink: true, showRawUsername: false,
				classes: [ 'workflow-details-user-widget' ]
			} );
			$panel.append( userWidget.$element );
		}
		$panel.css( 'height', '100%' );
		return $panel;
	};

	workflows.ui.WorkflowDetailsPage.prototype.getTrueDimensions = function ( $item ) {
		$( 'body' ).append( $item );
		const height = $item.outerHeight(),
			width = $item.outerWidth();
		$item.remove();

		return { height: height, width: width };
	};
}( mediaWiki, jQuery ) );

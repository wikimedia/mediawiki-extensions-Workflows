( function ( mw, $ ) {
	workflows.ui.alert.Alert = function( id, workflow ) {
		this.id = id;
		this.workflow = workflow;

		OO.EventEmitter.call( this );
	};

	OO.initClass( workflows.ui.alert.Alert );
	OO.mixinClass( workflows.ui.alert.Alert, OO.EventEmitter );

	workflows.ui.alert.Alert.prototype.getId = function() {
		return this.id;
	};

	workflows.ui.alert.Alert.prototype.getType = function() {
		return mwstake.alerts.TYPE_INFO;
	};

	workflows.ui.alert.Alert.prototype.getWorkflow = function() {
		return this.workflow;
	};

	workflows.ui.alert.Alert.prototype.getContent = function() {
		var definition = this.workflow.getDefinition();
		return new OO.ui.HorizontalLayout( {
			items: [
				new OO.ui.LabelWidget( {
					label: mw.message(
						'workflows-ui-alert-running-workflow',
						definition.title || definition.id
					).text()
				} ),
				this.getManageButton()
			]
		} ).$element;
	};

	workflows.ui.alert.Alert.prototype.getManageButton = function() {
		var button = new workflows.ui.widget.ManageButton( this.workflow.isCurrentUserInitiator() );
		button.connect( this, {
			click: function( data ) {
				this.emit( 'manage', this.getId(), data.role );
			}
		} );

		return button;
	};

} )( mediaWiki, jQuery );

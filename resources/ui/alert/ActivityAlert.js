workflows.ui.alert.ActivityAlert = function( id, activity, workflow ) {
	workflows.ui.alert.ActivityAlert.parent.call( this, id, workflow );

	OO.EventEmitter.call( this );
	this.activity = activity;
};

OO.inheritClass( workflows.ui.alert.ActivityAlert, workflows.ui.alert.Alert );
OO.mixinClass( workflows.ui.alert.ActivityAlert, OO.EventEmitter );

workflows.ui.alert.ActivityAlert.prototype.getContent = function() {
	this.completeButton = new OO.ui.ButtonWidget( {
		framed: false,
		flags: [ 'progressive', 'primary' ],
		label: this.activity.getCompleteButtonMessage()
	} );
	this.completeButton.connect( this, {
		click: 'onComplete'
	} );

	return new OO.ui.HorizontalLayout( {
		items: [
			new OO.ui.LabelWidget( {
				label: this.activity.getAlertMessage()
			} ),
			this.completeButton,
			this.getManageButton()
		]
	} ).$element;
};

workflows.ui.alert.ActivityAlert.prototype.onComplete = function() {
	this.emit( 'completeActivity', this.workflow, this.activity );
};

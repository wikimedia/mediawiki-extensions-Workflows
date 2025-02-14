workflows.ui.panel.TriggerOverview = function( cfg ) {
	cfg = $.extend( {
		padded: true
	}, cfg || {} );

	workflows.ui.panel.TriggerOverview.parent.call( this, cfg );

	this.triggerData = {};

	this.load();
};

OO.inheritClass( workflows.ui.panel.TriggerOverview, OO.ui.PanelLayout );

workflows.ui.panel.TriggerOverview.prototype.load = function() {
	workflows.triggers.getAvailableTypes().done( function( types ) {
		workflows.triggers.getAll().done( function( data ) {
			this.render( data, types );
		}.bind( this ) ).fail( function() {
			this.showError();
		}.bind( this ) );
	}.bind( this ) ).fail( function() {
		this.showError();
	}.bind( this ) );
};

workflows.ui.panel.TriggerOverview.prototype.render = function( data, types ) {
	this.$triggerCnt = $( '<div>' ).addClass( 'workflows-ui-trigger-cnt' );
	this.appendTriggers( data, types );
	this.$element.append( this.$triggerCnt );
	this.emit( 'loaded' );
};

workflows.ui.panel.TriggerOverview.prototype.appendTriggers = function( data, types ) {
	for ( var triggerId in data ) {
		if ( !data.hasOwnProperty( triggerId ) ) {
			continue;
		}
		var triggerData = data[triggerId];
		this.triggerData[triggerId] = triggerData;

		if ( !types.hasOwnProperty( triggerData.type ) ) {
			console.warn( 'Type of trigger ' + triggerId + ' is not supported' );
			continue;
		}
		var widget = new workflows.ui.widget.TriggerEntity(
			triggerId, triggerData, types[triggerData.type], {
				editMode: false
			}
		);
		this.$triggerCnt.append( widget.$element );
	}
};

workflows.ui.panel.TriggerOverview.prototype.showError = function() {
	//TODO: I18n
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: mw.message( 'workflows-error-generic' ).text()
	} ).$element );
	this.emit( 'loaded' );
};

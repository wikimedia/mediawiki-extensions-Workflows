workflows.ui.panel.TriggerOverview = function ( cfg ) {
	cfg = Object.assign( {
		padded: true
	}, cfg || {} );

	workflows.ui.panel.TriggerOverview.parent.call( this, cfg );

	this.triggerData = {};

	this.load();
};

OO.inheritClass( workflows.ui.panel.TriggerOverview, OO.ui.PanelLayout );

workflows.ui.panel.TriggerOverview.prototype.load = function () {
	workflows.triggers.getAvailableTypes().done( ( types ) => {
		workflows.triggers.getAll().done( ( data ) => {
			this.render( data, types );
		} ).fail( () => {
			this.showError();
		} );
	} ).fail( () => {
		this.showError();
	} );
};

workflows.ui.panel.TriggerOverview.prototype.render = function ( data, types ) {
	this.$triggerCnt = $( '<div>' ).addClass( 'workflows-ui-trigger-cnt' );
	this.appendTriggers( data, types );
	this.$element.append( this.$triggerCnt );
	this.emit( 'loaded' );
};

workflows.ui.panel.TriggerOverview.prototype.appendTriggers = function ( data, types ) {
	for ( const triggerId in data ) {
		if ( !data.hasOwnProperty( triggerId ) ) {
			continue;
		}
		const triggerData = data[ triggerId ];
		this.triggerData[ triggerId ] = triggerData;

		if ( !types.hasOwnProperty( triggerData.type ) ) {
			console.warn( 'Type of trigger ' + triggerId + ' is not supported' ); // eslint-disable-line no-console
			continue;
		}
		const widget = new workflows.ui.widget.TriggerEntity(
			triggerId, triggerData, types[ triggerData.type ], {
				editMode: false
			}
		);
		this.$triggerCnt.append( widget.$element );
	}
};

workflows.ui.panel.TriggerOverview.prototype.showError = function () {
	// TODO: I18n
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: mw.message( 'workflows-error-generic' ).text()
	} ).$element );
	this.emit( 'loaded' );
};

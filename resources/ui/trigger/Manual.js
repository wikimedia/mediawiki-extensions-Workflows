( function ( mw, $ ) {
	workflows.ui.trigger.Manual = function( data ) {
		workflows.ui.trigger.Manual.parent.call( this, data );
		this.validateInitializer = false;
	};

	OO.inheritClass( workflows.ui.trigger.Manual, workflows.ui.trigger.PageRelated );


	workflows.ui.trigger.Manual.prototype.getConditionPanelConfig = function() {
		return {
			include: { namespace: true }
		};
	};
} )( mediaWiki, jQuery );

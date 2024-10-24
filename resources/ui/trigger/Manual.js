( function ( mw, $ ) {
	workflows.ui.trigger.Manual = function( data, cfg ) {
		workflows.ui.trigger.Manual.parent.call( this, data, cfg );
	};

	OO.inheritClass( workflows.ui.trigger.Manual, workflows.ui.trigger.PageRelated );


	workflows.ui.trigger.Manual.prototype.getConditionPanelConfig = function() {
		return {
			include: { namespace: true }
		};
	};
} )( mediaWiki, jQuery );

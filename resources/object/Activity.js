( function ( mw, $ ) {
	workflows.object.Activity = function( cfg, workflow ) {
		workflows.object.Activity.parent.call( this, cfg, workflow );

		this.properties = cfg.properties || {};
		this.state = cfg.status;
	};

	OO.inheritClass( workflows.object.Activity, workflows.object.Element );

	workflows.object.Activity.prototype.getProperties = function() {
		return this.properties;
	};

	/**
	 * One of the workflows.state.activity states
	 * @returns {int}
	 */
	workflows.object.Activity.prototype.getState = function() {
		return this.state;
	};

} )( mediaWiki, jQuery );

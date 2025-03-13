( function () {
	workflows.object.Activity = function ( cfg, workflow ) {
		workflows.object.Activity.parent.call( this, cfg, workflow );

		this.properties = cfg.properties || {};
		this.rawProperties = cfg.rawProperties || {};
		this.state = cfg.status;
	};

	OO.inheritClass( workflows.object.Activity, workflows.object.Element );

	workflows.object.Activity.prototype.getProperties = function () {
		return this.properties;
	};

	workflows.object.Activity.prototype.getRawProperties = function () {
		return this.rawProperties;
	};

	/**
	 * One of the workflows.state.activity states
	 *
	 * @return {number}
	 */
	workflows.object.Activity.prototype.getState = function () {
		return this.state;
	};

}() );

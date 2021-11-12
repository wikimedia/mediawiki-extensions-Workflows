( function ( mw, $ ) {
	workflows.object.UserInteractionModule = function( cfg ) {
		this.modules = cfg.modules || [];
		this.class = cfg.class || null;
		this.callback = cfg.callback || null;
		this.data = cfg.data || {};
	};

	OO.initClass( workflows.object.UserInteractionModule );

	workflows.object.UserInteractionModule.prototype.getModules = function() {
		return this.modules;
	};

	workflows.object.UserInteractionModule.prototype.getClass = function() {
		return this.class;
	};

	workflows.object.UserInteractionModule.prototype.getCallback = function() {
		return this.callback;
	};

	workflows.object.UserInteractionModule.prototype.getData = function() {
		return this.data;
	};

} )( mediaWiki, jQuery );

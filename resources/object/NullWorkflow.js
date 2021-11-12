( function ( mw, $ ) {
	workflows.object.NullWorkflow = function() {
		this.loaded = true;
	};

	OO.inheritClass( workflows.object.NullWorkflow, workflows.object.Workflow );

	workflows.object.NullWorkflow.prototype.load = function() {
		return $.Deferred().promise().resolve();
	};

	workflows.object.NullWorkflow.prototype.getId = function() {
		return 'empty';
	};

	workflows.object.NullWorkflow.prototype.getState = function() {
		return workflows.state.RUNNING;
	};

	workflows.object.NullWorkflow.prototype.getCurrent = function() {
		return null;
	};
} )( mediaWiki, jQuery );

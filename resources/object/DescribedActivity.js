( function () {
	workflows.object.DescribedActivity = function ( cfg, workflow ) {
		workflows.object.DescribedActivity.parent.call( this, cfg, workflow );

		this.description = cfg.description || {};
		this.name = cfg.description.name || this.id;
		this.history = cfg.history || {};
		this.displayData = cfg.displayData || {};
	};

	OO.inheritClass( workflows.object.DescribedActivity, workflows.object.Activity );

	workflows.object.DescribedActivity.prototype.getDescription = function () {
		return this.description;
	};

	workflows.object.DescribedActivity.prototype.getHistory = function () {
		return this.history;
	};

	workflows.object.DescribedActivity.prototype.getDisplayData = function () {
		return this.displayData;
	};

}() );

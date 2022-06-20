workflows.store.Workflows = function ( cfg ) {
	this.total = 0;
	cfg.remoteSort = true;
	cfg.remoteFilter = true;

	workflows.store.Workflows.parent.call( this, cfg );
};

OO.inheritClass( workflows.store.Workflows, OOJSPlus.ui.data.store.Store );

workflows.store.Workflows.prototype.doLoadData = function() {
	var dfd = $.Deferred();

	workflows.list.filtered( {
		filter: this.filters || {},
		sort: this.sorters || {},
		offset: this.offset,
		limit: this.limit
	} ).done(
		function( response ) {
			if ( !response.hasOwnProperty( 'workflows' ) ) {
				return;
			}

			this.total = response.total;
			dfd.resolve( this.indexData( response.workflows ) );
		}.bind( this)
	);

	return dfd.promise();
};

workflows.store.Workflows.prototype.getTotal = function() {
	return this.total;
};

( function ( mw, $ ) {

	workflows.object.form.EditRequest = function( cfg, activity ) {
		workflows.object.form.EditRequest.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.EditRequest, workflows.object.form.Form );

	workflows.object.form.EditRequest.prototype.getDefinitionItems = function() {
		return [
			{
				type: 'label',
				widget_label: mw.message( 'workflows-form-edit-request-submit-to-continue' ).text(),
				noLayout: true
			},
			{
				name: 'instructions',
				noLayout: true,
				type: 'static_wikitext',
				widget_loadingText: mw.message( 'workflows-form-instructions-loading-text' ).text()
			},
			{
				name: 'assigned_user',
				hidden: true,
				type: 'text'
			}
		];
	};
} )( mediaWiki, jQuery );

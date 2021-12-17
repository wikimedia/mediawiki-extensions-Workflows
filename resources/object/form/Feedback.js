( function ( mw, $ ) {
	workflows.object.form.Feedback = function( cfg, activity ) {
		workflows.object.form.Feedback.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.Feedback, workflows.object.form.Form );

	workflows.object.form.Feedback.prototype.getDefinitionItems = function() {
		return [
			{
				name: 'instructions',
				type: 'static_wikitext',
				noLayout: true,
				widget_loadingText: mw.message( 'workflows-form-instructions-loading-text' ).text()
			},
			{
				name: 'comment',
				placeholder: mw.message( "workflows-form-instructions-comment" ).text(),
				noLayout: true,
				type: 'textarea',
				required: true
			}
		];
	};

} )( mediaWiki, jQuery );

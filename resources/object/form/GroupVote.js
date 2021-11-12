( function ( mw, $ ) {

	workflows.object.form.GroupVote = function( cfg, activity ) {
		workflows.object.form.GroupVote.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.GroupVote, workflows.object.form.Form );

	workflows.object.form.GroupVote.prototype.getDefinitionItems = function() {
		return [
			{
				name: 'instructions',
				noLayout: true,
				type: 'static_wikitext',
				widget_loadingText: mw.message( 'workflows-form-instructions-loading-text' ).text()
			},
			{
				name: 'vote',
				noLayout: true,
				type: 'vote_widget',
				required: true
			},
			{
				name: 'comment',
				type: 'textarea',
				noLayout: true,
				placeholder: mw.message( 'workflows-form-placeholder-comment' ).text()
			}
		];
	};
} )( mediaWiki, jQuery );

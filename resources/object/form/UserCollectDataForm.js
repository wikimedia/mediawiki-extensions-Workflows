( function ( mw, $ ) {
	workflows.object.form.UserCollectData = function( cfg, activity ) {
		workflows.object.form.UserCollectData.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.UserCollectData, workflows.object.form.Form );

	workflows.object.form.UserCollectData.prototype.getDefinitionItems = function() {
		return [
			{
				name: 'username',
				label: mw.message( 'workflows-collect-data-form-username' ).text(),
				type: 'user_picker',
				required: true
			},
			{
				name: 'instructions',
				label: mw.message( 'workflows-collect-data-form-instructions' ).text(),
				type: 'textarea'
			},
			{
				name: 'reportrecipient',
				label: mw.message( 'workflows-collect-data-form-reportrecipient' ).text(),
				type: 'text'
			}
		];
	};

} )( mediaWiki, jQuery );

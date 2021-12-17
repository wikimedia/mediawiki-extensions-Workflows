( function ( mw, $ ) {
	workflows.object.form.GroupCollectData = function( cfg, activity ) {
		workflows.object.form.GroupCollectData.parent.call( this, cfg, activity );
	};

	OO.inheritClass( workflows.object.form.GroupCollectData, workflows.object.form.Form );

	workflows.object.form.GroupCollectData.prototype.getDefinitionItems = function() {
		return [
			{
				name: 'groupname',
				label: mw.message( 'workflows-collect-data-form-groupname' ).text(),
				type: 'group_picker',
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

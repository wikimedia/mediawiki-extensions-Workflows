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
			},
			{
				name: 'threshold_unit',
				label: mw.message( 'workflows-collect-data-form-threshold-unit' ).text(),
				type: 'dropdown',
				options: [
					{ data: 'user', label: mw.message( 'workflows-collect-data-form-threshold-unit-user' ).text() },
					{ data: 'percent', label: mw.message( 'workflows-collect-data-form-threshold-unit-percent' ).text() },
				]
			},
			{
				name: 'threshold_value',
				label: mw.message( 'workflows-collect-data-form-threshold-value' ).text(),
				type: 'wf_threshold_value'
			}
		];
	};

	workflows.object.form.GroupCollectData.prototype.onRenderComplete = function( form ) {
		form.getItem( 'groupname' ).connect( this, {
			change: function( value ) {
				form.getItem( 'threshold_value' ).setGroupName( value );
			}
		} );
		form.getItem( 'threshold_unit' ).connect( this, {
			change: function( value ) {
				form.getItem( 'threshold_value' ).setType( value );
			},
		} );
		form.getItem( 'threshold_value' ).setType( form.getItem( 'threshold_unit' ).getValue() );
		form.getItem( 'threshold_value' ).setGroupName( form.getItem( 'groupname' ).getValue() );

		form.getItem( 'threshold_value' ).connect( this, {
			layoutChange: function() {
				this.emit( 'layoutChange' );
			}
		} );
	};

} )( mediaWiki, jQuery );

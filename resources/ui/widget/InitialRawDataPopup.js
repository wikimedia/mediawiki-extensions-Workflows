( function ( mw ) {
	workflows.ui.widget.InitialRawDataPopup = function ( data, cfg ) {

		workflows.ui.widget.InitialRawDataPopup.parent.call( this, data, Object.assign( {
			icon: 'info',
			title: mw.message( 'workflows-ui-overview-details-raw-data-popup-label' ).text(),
			framed: false,
			flags: [ 'progressive', 'primary' ],
			$overlay: false
		}, cfg || {} ) );
	};

	OO.inheritClass( workflows.ui.widget.InitialRawDataPopup, workflows.ui.widget.ActivityRawDataPopup );

}( mediaWiki ) );

( function( mw, $ ) {
	workflows.ui.widget.InitialRawDataPopup = function( data ) {

		var cfg = {
			label: mw.message( 'workflows-ui-workflow-overview-initial-data-title' ).text(),
			title: mw.message( 'workflows-ui-overview-details-raw-data-popup-label' ).text(),
			framed: false,
			flags: [ 'progressive', 'primary' ],
			$overlay: true,
		};

		workflows.ui.widget.InitialRawDataPopup.parent.call( this, data, cfg );
	};

	OO.inheritClass( workflows.ui.widget.InitialRawDataPopup, workflows.ui.widget.ActivityRawDataPopup );

	workflows.ui.widget.InitialRawDataPopup.prototype.getPopupClass = function() {
		return 'initial-data-raw-popup';
	}

} )( mediaWiki, jQuery );

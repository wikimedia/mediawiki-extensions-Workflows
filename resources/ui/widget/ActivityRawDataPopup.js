( function( mw, $ ) {
	workflows.ui.widget.ActivityRawDataPopup = function( data ) {

		var $table = $( '<table>' ).append(
			$( '<tr>' ).append(
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-prop' ).text() ),
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-value' ).text() )
			)
		);
		for ( var prop in data ) {
			if ( !data.hasOwnProperty( prop ) ) {
				continue;
			}
			$table.append(
				$( '<tr>' ).append(
					$( '<td>' ).text( prop ),
					$( '<td>' ).text( data[prop] )
				)
			);
		}

		var cfg = {
			icon: 'info',
			title: mw.message( 'workflows-ui-overview-details-raw-data-popup-label' ).text(),
			framed: false,
			$overlay: true,
			popup: {
				$content: $table,
				padded: true
			}
		};
		workflows.ui.widget.ActivityRawDataPopup.parent.call( this, cfg );

		this.$element.addClass( 'activity-data-raw-popup' );
	};

	OO.inheritClass( workflows.ui.widget.ActivityRawDataPopup, OO.ui.PopupButtonWidget );
} )( mediaWiki, jQuery );

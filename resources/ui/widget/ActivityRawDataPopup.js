( function ( mw, $ ) {
	workflows.ui.widget.ActivityRawDataPopup = function ( data, cfg ) {
		this.data = data;

		if ( !cfg ) {
			cfg = {
				icon: 'info',
				title: mw.message( 'workflows-ui-overview-details-raw-data-popup-label' ).text(),
				framed: false,
				$overlay: true
			};
		}

		const popupContent = this.getContent();
		cfg.popup = $.extend( cfg.popup || {}, {
			$content: popupContent.$content,
			height: popupContent.height + 30,
			width: popupContent.width + 30,
			padded: false,
			autoFlip: true,
			$overlay: true,
			classes: [ 'workflow-data-popup' ]
		} );

		workflows.ui.widget.ActivityRawDataPopup.parent.call( this, cfg );
		this.popup.connect( this, {
			ready: function () {
				// this hack is needed because popup body is never set to the correct height,
				// even though it should, based on docu. This is a workaround for that.
				// Has to be in an event handler as its lazy set
				this.popup.$body.css( 'height', '100%' );
			}
		} );
		const children = this.$element.children(); // eslint-disable-line no-jquery/variable-pattern
		if ( children.length > 1 ) {
			children[ 1 ].tabIndex = 0;
		}
		this.$element.addClass( 'activity-data-raw-popup' );
	};

	OO.inheritClass( workflows.ui.widget.ActivityRawDataPopup, OO.ui.PopupButtonWidget );

	workflows.ui.widget.ActivityRawDataPopup.prototype.getContent = function () {
		const $table = $( '<table>' )
			.addClass( 'activity-data-table' )
			.append(
				$( '<tr class="popup-table-header">' ).append( // eslint-disable-line no-jquery/no-parse-html-literal
					$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-prop' ).text() ),
					$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-value' ).text() )
				)
			);

		for ( const prop in this.data ) {
			if ( !this.data.hasOwnProperty( prop ) ) {
				continue;
			}
			if ( !this.data[ prop ] ) {
				// Do not show empty values
				continue;
			}
			$table.append(
				$( '<tr>' ).append(
					$( '<th>' ).text( prop ),
					$( '<td>' ).text( this.data[ prop ] )
				)
			);
		}

		// Hack to get dimensions of the table (has to be in the DOM)
		$( 'body' ).append( $table );
		const height = $table.outerHeight(),
			width = $table.outerWidth();
		$table.remove();
		return {
			$content: $table,
			width: width,
			height: height
		};
	};
}( mediaWiki, jQuery ) );

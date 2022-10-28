( function( mw, $ ) {
	workflows.ui.widget.ActivityRawDataPopup = function( data, cfg ) {
		this.data = data;

		if ( !cfg ) {
			cfg = {
				icon: 'info',
				title: mw.message('workflows-ui-overview-details-raw-data-popup-label').text(),
				framed: false,
				$overlay: false,
				flags: [ 'progressive' ]
			};
		}

		cfg.popup = {
			$content: this.getContent(),
			padded: true,
			classes: [ 'workflow-data-popup' ]
		};

		workflows.ui.widget.ActivityRawDataPopup.parent.call( this, cfg );

		this.$element.children()[1].tabIndex = 0;
		this.$element.addClass( this.getPopupClass() );
	};

	OO.inheritClass( workflows.ui.widget.ActivityRawDataPopup, OO.ui.PopupButtonWidget );

	workflows.ui.widget.ActivityRawDataPopup.prototype.getPopupClass = function() {
		return 'activity-data-raw-popup';
	}

	workflows.ui.widget.ActivityRawDataPopup.prototype.getTableClass = function() {
		return 'activity-data-table';
	}

	workflows.ui.widget.ActivityRawDataPopup.prototype.getContent = function() {
		var $table = $( '<table>' ).addClass( this.getTableClass() ).append(
			$( '<tr class="popup-table-header">' ).append(
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-prop' ).text() ),
				$( '<th>' ).text( mw.message( 'workflows-ui-overview-details-raw-data-popup-value' ).text() )
			)
		);

		for ( var prop in this.data ) {
			if ( !this.data.hasOwnProperty( prop ) ) {
				continue;
			}
			$table.append(
				$( '<tr>' ).append(
					$( '<th>' ).text( prop ),
					$( '<td>' ).text( this.data[prop] )
				)
			);
		}

		return $table;
	}
} )( mediaWiki, jQuery );

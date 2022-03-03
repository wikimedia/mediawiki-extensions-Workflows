$( function() {
	var $container = $( '#workflows-triggers-cnt' );
	if ( $container.length === 0 ) {
		return;
	}
	mw.loader.using( 'ext.workflows.trigger.editor.panel', function() {
		var panel = new workflows.ui.panel.TriggerEditor( {
			expanded: false
		} );

		panel.connect( this, {
			loaded: function() {
				$container.html( panel.$element );
			}
		} );
	} );

} );

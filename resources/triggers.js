$( function() {
	var $viewCnt = $( '#workflows-triggers-cnt' );
	let panel = null;
	if ( $viewCnt.length ) {
		panel = new workflows.ui.panel.TriggerOverview( {
			expanded: false
		} );
		panel.connect( this, {
			loaded: function() {
				$viewCnt.html( panel.$element );
			}
		} );
	}

	const $editorCnt = $( '#workflows-triggers-editor-cnt' );
	if ( $editorCnt.length ) {
		panel = new workflows.ui.panel.TriggerEditor( {
			expanded: false
		} );
		panel.connect( this, {
			loaded: function() {
				$editorCnt.html( panel.$element );
			}
		} );
	}
} );

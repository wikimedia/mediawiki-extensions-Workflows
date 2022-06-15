$( function() {
	var $container = $( '#workflows-overview' );
		if ( $container.length === 0 ) {
		return;
	}
	var $loader = $( '#workflows-overview-loader' );

	var panel = new workflows.ui.panel.WorkflowList( {
		expanded: false
	} );

	function setLoading( loading ) {
		if ( loading ) {
			$loader.html(
				new OO.ui.ProgressBarWidget( {
					progress: false
				} ).$element
			);
		} else {
			$loader.children().remove();
		}
	}

	panel.connect( this, {
		selected: function( id ) {
			setLoading( true );
			workflows.getWorkflow( id ).done( function( workflow ) {
				var windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				var dialog = new workflows.ui.dialog.WorkflowOverview( workflow, null );
				setLoading( false );
				windowManager.addWindows( [ dialog ] );
				windowManager.openWindow( dialog ).closed.then( function() {
						this.load();
				}.bind( panel ) );
			} );
		}
	} );

	$container.append( panel.$element );
} );

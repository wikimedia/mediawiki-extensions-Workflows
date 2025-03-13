$( function () {
	const $container = $( '#workflows-overview' );
	if ( $container.length === 0 ) {
		return;
	}
	const $loader = $( '#workflows-overview-loader' );

	const panel = new workflows.ui.panel.WorkflowList( {
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
		selected: function ( id ) {
			setLoading( true );
			workflows.getWorkflow( id ).done( ( workflow ) => {
				const windowManager = new OO.ui.WindowManager();
				$( document.body ).append( windowManager.$element );

				const dialog = new workflows.ui.dialog.WorkflowOverview( workflow, null );
				setLoading( false );
				windowManager.addWindows( [ dialog ] );
				windowManager.openWindow( dialog ).closed.then( function () {
					this.load();
				}.bind( panel ) );
			} );
		}
	} );

	$container.append( panel.$element );
} );

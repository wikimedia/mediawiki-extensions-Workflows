( function ( mw, $, wf ) {
	workflows.ui.WorkflowPickerWidget = function( cfg ) {
		cfg.label = mw.message( 'workflows-ui-starter-select-workflow' ).text();
		cfg.$overlay = true;
		this.repos = cfg.repos || [];
		workflows.ui.WorkflowPickerWidget.parent.call( this, cfg );
		this.allowed = mw.config.get( 'workflowsAllowed' ) || [];

		this.loadOptions();
	};

	OO.inheritClass( workflows.ui.WorkflowPickerWidget, OO.ui.DropdownWidget );

	workflows.ui.WorkflowPickerWidget.prototype.loadOptions = function() {
		wf.initiate.listAvailableTypes().done( function ( types ) {
			var options = [], definitions, repo, i;
			for ( repo in types ) {
				if ( !types.hasOwnProperty( repo ) ) {
					continue;
				}
				if ( this.repos.length > 0 && this.repos.indexOf( repo ) === -1 ) {
					continue;
				}
				definitions = types[repo].definitions;
				for ( i = 0; i < definitions.length; i++ ) {
					if ( !this.isAllowed( repo, definitions[i].key ) ) {
						continue;
					}
					var option = new OO.ui.MenuOptionWidget( {
						data: {
							workflow: {
								repo: repo,
								workflow: definitions[i].key,
							},
							desc: definitions[i].desc || ''
						},
						label: definitions[i].title
					} );
					$( '<span>' )
					.css( {
						'font-size': '0.9em',
						'color': 'grey'
					} )
					.html( definitions[i].desc ).insertAfter( option.$label );
					options.push(  option );
				}
			}

			this.menu.addItems( options );
		}.bind( this ) ).fail( function() {
			this.emit( 'error' );
		}.bind( this ) );
	};

	workflows.ui.WorkflowPickerWidget.prototype.isAllowed = function( repo, definition ) {
		for ( var i = 0; i < this.allowed.length; i ++ ) {
			if ( !this.allowed[i].hasOwnProperty( 'repo' ) || !this.allowed[i].hasOwnProperty( 'definition' ) ) {
				continue;
			}
			if ( this.allowed[i].repo === repo && this.allowed[i].definition === definition ) {
				return true;
			}
		}
		return false;
	};
} )( mediaWiki, jQuery, workflows );

( function ( mw, $ ) {
	workflows.ui.WorkflowAbortRestorePage = function( name, cfg ) {
		workflows.ui.WorkflowAbortRestorePage.parent.call( this, name, cfg );
		this.action = '';
		this.panel = new OO.ui.PanelLayout( {
			padded: false,
			expanded: false
		} );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.WorkflowAbortRestorePage, OO.ui.PageLayout );

	workflows.ui.WorkflowAbortRestorePage.prototype.init = function( workflow, action ) {
		this.workflow = workflow;
		this.message = new OO.ui.LabelWidget( {
			label: mw.message( 'workflows-ui-overview-details-action-' + action + '-note' ).text()
		} );
		this.action = action;
		this.reason = new OO.ui.MultilineTextInputWidget( {
			rows: 5,
			readOnly: false
		} );

		this.panel.$element.children().remove();
		this.panel.$element.append(
			this.message.$element,
			new OO.ui.FieldLayout( this.reason, {
				align: 'top',
				label: mw.message( 'workflows-ui-overview-details-action-reason' ).text()
			} ).$element
		);
	};

	workflows.ui.WorkflowAbortRestorePage.prototype.getReason = function() {
		return this.reason.getValue();
	};

	workflows.ui.WorkflowAbortRestorePage.prototype.getTitle = function() {
		return this.action === 'abort' ?
			mw.message( 'workflows-ui-workflow-overview-dialog-title-abort' ).text() :
			mw.message( 'workflows-ui-workflow-overview-dialog-title-restore' ).text();
	};

} )( mediaWiki, jQuery );

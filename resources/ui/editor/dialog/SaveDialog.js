workflows.editor.dialog.SaveDialog = function ( config ) {
	workflows.editor.dialog.SaveDialog.super.call( this, config );
};

OO.inheritClass( workflows.editor.dialog.SaveDialog, OO.ui.ProcessDialog );

workflows.editor.dialog.SaveDialog.static.name = 'save';
workflows.editor.dialog.SaveDialog.static.title = mw.msg( 'workflows-editor-editor-dialog-save-title' );
workflows.editor.dialog.SaveDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'workflows-editor-editor-dialog-save-action-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'workflows-editor-editor-dialog-save-action-cancel' ), flags: 'safe' }
];

workflows.editor.dialog.SaveDialog.prototype.initialize = function () {
	workflows.editor.dialog.SaveDialog.super.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.$element.append( this.content.$element );

	this.summaryField = new OO.ui.MultilineTextInputWidget( {
		rows: 3,
		maxRows: 3
	} );
	this.summaryField.connect( this, {
		resize: 'updateSize'
	} );
	this.content.$element.append( new OO.ui.FieldLayout( this.summaryField, {
		label: mw.msg( 'workflows-editor-editor-field-summary' ),
		align: 'top'
	} ).$element );
	this.$body.append( this.content.$element );
};

workflows.editor.dialog.SaveDialog.prototype.getBodyHeight = function () {
	return this.content.$element.outerHeight( true ) + 20;
};

workflows.editor.dialog.SaveDialog.prototype.getActionProcess = function ( action ) {
	return workflows.editor.dialog.SaveDialog.parent.prototype.getActionProcess.call( this, action ).next( () => {
		if ( action === 'save' ) {
			this.close( { action: 'save', summary: this.summaryField.getValue() } );
		} else {
			this.close( { action: 'cancel' } );
		}
	} );
};

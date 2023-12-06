<<<<<<< HEAD   (893d9e Merge "Update Activity description" into REL1_39-2.0.x)
=======
workflows.editor.inspector.InspectorDialog = function( element, cfg ) {
	cfg = cfg || {};
	cfg.size = 'large';
	workflows.editor.inspector.InspectorDialog.parent.call( this, cfg );

	this.element = element;
	this.inspector = cfg.inspector;

	this.inspector.dialog = this;
};

OO.inheritClass( workflows.editor.inspector.InspectorDialog, OO.ui.ProcessDialog );


workflows.editor.inspector.InspectorDialog.static.name = 'taskCompletion';
workflows.editor.inspector.InspectorDialog.static.title = mw.message( 'workflows-ui-editor-inspector-title' ).text();
workflows.editor.inspector.InspectorDialog.static.actions = [
	{
		action: 'save',
		label: mw.message( 'workflows-editor-editor-button-save' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		label: mw.message( 'workflows-editor-editor-button-cancel' ).text(),
		action: 'cancel',
		flags: 'safe'
	}
];

workflows.editor.inspector.InspectorDialog.prototype.initialize = function () {
	workflows.editor.inspector.InspectorDialog.static.title = this.inspector.getDialogTitle();
	workflows.editor.inspector.InspectorDialog.super.prototype.initialize.apply( this, arguments );


	this.panel = new OO.ui.PanelLayout( {
		expanded: true,
		padded: true
	} );
	this.$body.append( this.panel.$element );
	this.form = this.inspector.getForm();
	this.form.render();
	// this.form is the standalone, this.form.form is actual Form object
	this.form.form.connect( this, {
		layoutChange: function() {
			this.updateSize();
		}
	} );
	this.form.$element.addClass( 'nopadding' );
	this.panel.$element.append( this.form.$element );
	this.updateSize();
};

workflows.editor.inspector.InspectorDialog.prototype.getActionProcess = function ( action ) {
	return workflows.editor.inspector.InspectorDialog.parent.prototype.getActionProcess.call( this, action ).next(
		function() {
			if ( action === 'save' ) {
				this.pushPending();
				var dfd = $.Deferred();
				this.form.connect( this, {
					dataSubmitted: function( data ) {
						this.inspector.updateModel( data );
						this.close();
					},
					validationFailed: function() {
						this.popPending();
						dfd.resolve();
					}
				} );
				this.form.submit();

				return dfd.promise();
			}
			if ( action === 'cancel' ) {
				this.close();
			}
		}, this
	);
};

workflows.editor.inspector.InspectorDialog.prototype.getBodyHeight = function () {
	return this.$element.find( '.oo-ui-window-body' )[0].scrollHeight + 10;
};
>>>>>>> CHANGE (6fc28f Implement activity editors)

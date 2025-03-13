workflows.editor.inspector.GatewayInspector = function ( element, dialog ) {
	workflows.editor.inspector.GatewayInspector.parent.call( this, element, dialog );
};

OO.inheritClass( workflows.editor.inspector.GatewayInspector, workflows.editor.inspector.Inspector );

workflows.editor.inspector.GatewayInspector.prototype.getDialogTitle = function () {
	return mw.message( 'workflows-ui-editor-inspector-gateway-title' ).text();
};

workflows.editor.inspector.GatewayInspector.prototype.getItems = function () {
	return [
		{
			type: 'message',
			widget_type: 'info',
			widget_label: mw.message( 'workflows-ui-editor-inspector-gateway-notice' ).text(),
			style: 'margin-top: 10px; margin-bottom: 10px;'
		}
	];
};

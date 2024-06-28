workflows.editor.tool.CancelTool = function () {
	workflows.editor.tool.CancelTool.super.apply( this, arguments );
};

OO.inheritClass( workflows.editor.tool.CancelTool, OO.ui.Tool );
workflows.editor.tool.CancelTool.static.name = 'cancel';
workflows.editor.tool.CancelTool.static.icon = 'cancel';
workflows.editor.tool.CancelTool.static.iconTitle = mw.message( 'workflows-editor-editor-button-cancel' );
workflows.editor.tool.CancelTool.static.flags = [ 'destructive' ];
workflows.editor.tool.CancelTool.static.displayBothIconAndLabel = false;
workflows.editor.tool.CancelTool.prototype.onSelect = function () {
	this.setActive( false );
	window.location.href = mw.util.getUrl( mw.config.get( 'wgPageName' ) );
};
workflows.editor.tool.CancelTool.prototype.onUpdateState = function () {};

workflows.editor.toolFactory.register( workflows.editor.tool.CancelTool );

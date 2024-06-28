workflows.editor.tool.SaveTool = function () {
	workflows.editor.tool.SaveTool.super.apply( this, arguments );
};

OO.inheritClass( workflows.editor.tool.SaveTool, OO.ui.Tool );
workflows.editor.tool.SaveTool.static.name = 'save';
workflows.editor.tool.SaveTool.static.icon = '';
workflows.editor.tool.SaveTool.static.title = mw.message( 'workflows-editor-editor-button-save' );
workflows.editor.tool.SaveTool.static.flags = [ 'primary', 'progressive' ];
workflows.editor.tool.SaveTool.static.displayBothIconAndLabel = true;
workflows.editor.tool.SaveTool.prototype.onSelect = function () {
	this.setActive( false );
	this.toolbar.emit( 'save' );
	this.toolbar.emit( 'updateState' );
};
workflows.editor.tool.SaveTool.prototype.onUpdateState = function () {};

workflows.editor.toolFactory.register( workflows.editor.tool.SaveTool );

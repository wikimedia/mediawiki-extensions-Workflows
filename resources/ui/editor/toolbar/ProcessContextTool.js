workflows.editor.tool.ProcessContextTool = function () {
	workflows.editor.tool.ProcessContextTool.super.apply( this, arguments );
};

OO.inheritClass( workflows.editor.tool.ProcessContextTool, OO.ui.Tool );
workflows.editor.tool.ProcessContextTool.static.name = 'inspectProcess';
workflows.editor.tool.ProcessContextTool.static.icon = '';
workflows.editor.tool.ProcessContextTool.static.title = mw.message( 'workflows-ui-editor-inspector-process-title' );
workflows.editor.tool.ProcessContextTool.static.displayBothIconAndLabel = true;
workflows.editor.tool.ProcessContextTool.prototype.onSelect = function () {
	this.setActive( false );
	this.toolbar.emit( 'editProcessContext' );
	this.toolbar.emit( 'updateState' );
};
workflows.editor.tool.ProcessContextTool.prototype.onUpdateState = function () {};

workflows.editor.toolFactory.register( workflows.editor.tool.ProcessContextTool );

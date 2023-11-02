var inspectorRegistry = function() {
	inspectorRegistry.parent.call( this );
};

OO.inheritClass( inspectorRegistry, OO.Registry );

inspectorRegistry.prototype.getInspectorForElement = function( element, dialog ) {
	switch ( element.type ) {
		case 'bpmn:Process':
			return new workflows.editor.inspector.ProcessInspector( element, dialog );
		case 'bpmn:Task':
		case 'bpmn:UserTask':
				var activityType = this.getActivityType( element );
				if ( activityType ) {
					var inspectorClass = this.lookup( activityType );
					if ( inspectorClass ) {
						return new inspectorClass( element, dialog );
					}
				}
				return new workflows.editor.inspector.ActivityInspector( element );
		case 'bpmn:Gateway':
		case 'bpmn:ExclusiveGateway':
			return new workflows.editor.inspector.GatewayInspector( element );
		default:
			return null;
	}
};

inspectorRegistry.prototype.getActivityType = function( element ) {
	var typeEl = workflows.editor.util.extensionElements.get( element, 'wf:Type' );
	if ( typeEl ) {
		return typeEl.get( 'text' );
	}
	return null;
};

window.workflows.editor.inspector.Registry = new inspectorRegistry();

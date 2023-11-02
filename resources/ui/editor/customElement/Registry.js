var elementRegistry = function() {
	elementRegistry.parent.call( this );
};

OO.inheritClass( elementRegistry, OO.Registry );

elementRegistry.prototype.getAll = function() {
	var elements = {};
	for ( var name in this.registry ) {
		if ( !this.registry.hasOwnProperty( name ) ) {
			continue;
		}
		var element = this.registry[ name ];
		if ( element instanceof workflows.editor.element.CustomElement ) {
			elements[name] = element;
			continue;
		}
		if ( typeof element !== 'object' ) {
			console.error( 'Element ' + name + ' is not an object' );
			continue;
		}
		elements[name] = new workflows.editor.element.WorkflowActivityElement( name, {
			isUserActivity: element.isUserActivity || false,
			label: element.label || null,
			class: element.class || null,
			defaultData: element.defaultData || {}
		} );
	}

	return elements;
};

window.workflows.editor.element.registry = new elementRegistry();

var elementRegistry = function () { // eslint-disable-line no-var
	elementRegistry.parent.call( this );
};

OO.inheritClass( elementRegistry, OO.Registry );

elementRegistry.prototype.getAll = function () {
	const elements = {};
	for ( const name in this.registry ) {
		if ( !this.registry.hasOwnProperty( name ) ) {
			continue;
		}
		const element = this.registry[ name ];
		if ( element instanceof workflows.editor.element.CustomElement ) {
			elements[ name ] = element;
			continue;
		}
		if ( typeof element !== 'object' ) {
			console.error( 'Element ' + name + ' is not an object' ); // eslint-disable-line no-console
			continue;
		}
		elements[ name ] = new workflows.editor.element.WorkflowActivityElement( name, {
			isUserActivity: element.isUserActivity || false,
			label: element.label || null,
			class: element.class || null,
			defaultData: element.defaultData || {}
		} );
	}

	return elements;
};

window.workflows.editor.element.registry = new elementRegistry(); // eslint-disable-line new-cap

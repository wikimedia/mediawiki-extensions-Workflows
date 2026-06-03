mw.hook( 'ext.visualEditorPlus.tags.registerTags' ).add( ( registry ) => {
	const originalCreateDm = registry.createDmForTag;
	registry.createDmForTag = function ( definition ) {
		originalCreateDm.call( this, definition );
		if ( definition.classname === 'Myopenworkflows' ) {
			const classname = definition.classname + 'Node';
			window.ext.visualEditorPlus.dm[ classname ].prototype.isEditable = function () {
				return false;
			};
		}
	};
} );

workflows.dm.NoWorkflowMetaItem = function () {
	workflows.dm.NoWorkflowMetaItem.super.apply( this, arguments );
};

OO.inheritClass( workflows.dm.NoWorkflowMetaItem, ve.dm.MetaItem );

workflows.dm.NoWorkflowMetaItem.static.name = 'noworkflowexecution';

workflows.dm.NoWorkflowMetaItem.static.group = 'noworkflowexecution';

workflows.dm.NoWorkflowMetaItem.static.matchTagNames = [ 'meta' ];

workflows.dm.NoWorkflowMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/NOWORKFLOWEXECUTION' ];

workflows.dm.NoWorkflowMetaItem.static.toDomElements = function ( dataElement, doc ) {
	const meta = doc.createElement( 'meta' );
	meta.setAttribute( 'property', 'mw:PageProp/NOWORKFLOWEXECUTION' );
	return [ meta ];
};

ve.dm.modelRegistry.register( workflows.dm.NoWorkflowMetaItem );

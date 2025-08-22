class WorkflowsContextPadProvider {
	constructor( contextPad ) {
		contextPad.registerProvider( this );
	}

	getContextPadEntries( element ) { // eslint-disable-line no-unused-vars
		return function ( entries ) {
			delete entries[ 'append.intermediate-event' ];
			delete entries[ 'append.append-task' ];
			delete entries.append;
			delete entries.replace;
			return entries;
		};
	}
}

WorkflowsContextPadProvider.$inject = [ 'contextPad' ];

class WorkflowsPaletteProvider {

	constructor( palette, create, elementFactory,
		spaceTool, lassoTool, handTool,
		globalConnect, bpmnFactory
	) {
		this._palette = palette;
		this._create = create;
		this._elementFactory = elementFactory;
		this._spaceTool = spaceTool;
		this._lassoTool = lassoTool;
		this._handTool = handTool;
		this._globalConnect = globalConnect;
		this._bpmnFactory = bpmnFactory;

		palette.registerProvider( this );
	}
}

WorkflowsPaletteProvider.$inject = [
	'palette',
	'create',
	'elementFactory',
	'spaceTool',
	'lassoTool',
	'handTool',
	'globalConnect',
	'bpmnFactory'
];

WorkflowsPaletteProvider.prototype.getPaletteEntries = function () {
	const actions = {},
		create = this._create,
		elementFactory = this._elementFactory,
		spaceTool = this._spaceTool,
		lassoTool = this._lassoTool,
		handTool = this._handTool,
		globalConnect = this._globalConnect,
		bpmnFactory = this._bpmnFactory;

	function createAction( type, group, className, title, options ) {
		function createListener( event ) {
			const shape = elementFactory.createShape( { type: type }, options );
			create.start( event, shape );
		}

		const shortType = type.replace( /^bpmn:/, '' ); // eslint-disable-line no-unused-vars

		return {
			group: group,
			className: className,
			title: title,
			action: {
				dragstart: createListener,
				click: createListener
			}
		};
	}

	function createActivityAction( type, group, className, title, options ) {
		function createActivity( event ) {
			const businessObject = bpmnFactory.create( type ),
				shape = elementFactory.createShape( { type: type, businessObject: businessObject } );

			businessObject.extensionElements = workflows.editor.util.extensionElements.create(
				'bpmn:ExtensionElements', {}, shape
			);

			const extElementsData = options.extensionElements || {};
			extElementsData[ 'wf:Type' ] = options.activityType;
			workflows.editor.util.extensionElements.assignFromData( shape, extElementsData );

			const properties = options.properties || {};
			for ( const property in properties ) {
				if ( !properties.hasOwnProperty( property ) ) {
					continue;
				}
				workflows.editor.util.properties.set( shape, property, properties[ property ] );
			}
			create.start( event, shape );
		}

		return {
			group: group,
			className: className,
			title: title,
			action: {
				dragstart: createActivity,
				click: createActivity
			}
		};
	}

	Object.assign( actions, {
		'hand-tool': {
			group: 'tools',
			className: 'bpmn-icon-hand-tool',
			title: mw.msg( 'workflows-ui-editor-toolbar-hand-tool' ),
			action: {
				click: function ( event ) {
					handTool.activateHand( event );
				}
			}
		},
		'lasso-tool': {
			group: 'tools',
			className: 'bpmn-icon-lasso-tool',
			title: mw.msg( 'workflows-ui-editor-toolbar-lasso-tool' ),
			action: {
				click: function ( event ) {
					lassoTool.activateSelection( event );
				}
			}
		},
		'space-tool': {
			group: 'tools',
			className: 'bpmn-icon-space-tool',
			title: mw.msg( 'workflows-ui-editor-toolbar-space-tool' ),
			action: {
				click: function ( event ) {
					spaceTool.activateSelection( event );
				}
			}
		},
		'global-connect-tool': {
			group: 'tools',
			className: 'bpmn-icon-connection-multi',
			title: mw.msg( 'workflows-ui-editor-toolbar-global-connect-tool' ),
			action: {
				click: function ( event ) {
					globalConnect.start( event );
				}
			}
		},
		'tool-separator': {
			group: 'tools',
			separator: true
		},
		'create.start-event': createAction(
			'bpmn:StartEvent', 'event', 'bpmn-icon-start-event-none',
			mw.msg( 'workflows-ui-editor-toolbar-create-start-event' )
		),
		'create.end-event': createAction(
			'bpmn:EndEvent', 'event', 'bpmn-icon-end-event-none',
			mw.msg( 'workflows-ui-editor-toolbar-create-end-event' )
		),
		'create.exclusive-gateway': createAction(
			'bpmn:ExclusiveGateway', 'gateway', 'bpmn-icon-gateway-none',
			mw.msg( 'workflows-ui-editor-toolbar-create-exclusive-gateway' )
		),
		'create-separator': {
			group: 'create',
			separator: true
		}
	} );

	const customElements = workflows.editor.element.registry.getAll();
	for ( const key in customElements ) {
		if ( !customElements.hasOwnProperty( key ) ) {
			continue;
		}
		const element = customElements[ key ];
		if ( element instanceof workflows.editor.element.WorkflowActivityElement ) {
			actions[ 'create.activity-' + key ] = createActivityAction(
				element.getType(), element.getGroup(), element.getClass(),
				element.getLabel(),
				Object.assign( {
					activityType: key
				}, element.getDefaultData() )
			);
		} else {
			actions[ 'create.' + key ] = createAction(
				element.getType(), element.getGroup(), element.getClass(), element.getLabel()
			);
		}
	}

	return actions;
};

window.workflows.editor.customMenuModule = {
	__init__: [
		'customContextPadProvider',
		'paletteProvider'
	],
	paletteProvider: [ 'type', WorkflowsPaletteProvider ],
	customContextPadProvider: [ 'type', WorkflowsContextPadProvider ]
};

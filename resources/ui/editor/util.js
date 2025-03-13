workflows.editor.util = {
	extensionElements: {
		getAll: function ( element ) {
			if (
				!element ||
				!element.hasOwnProperty( 'businessObject' ) ||
				!element.businessObject.hasOwnProperty( 'extensionElements' )
			) {
				return null;
			}

			return element.businessObject.get( 'extensionElements' );
		},
		create: function ( type, attributes, parent ) {
			attributes = attributes || {};
			const el = workflows.editor.modeler.get( 'moddle' ).create( type );
			for ( const key in attributes ) {
				if ( attributes.hasOwnProperty( key ) ) {
					el[ key ] = attributes[ key ];
				}
			}
			if ( parent ) {
				el.$parent = parent;
			}
			return el;
		},
		add: function ( element, extensionElement ) {
			let ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				ee = workflows.editor.modeler.get( 'moddle' ).create( 'bpmn:ExtensionElements' );
			}

			ee.set( 'values', ( ee.get( 'values' ) || [] ).concat( extensionElement ) );
		},
		get: function ( element, type ) {
			const ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				return null;
			}

			const items = ( ee.get( 'values' ) || [] ).filter( ( e ) => e.$type === type );

			return items.length === 1 ? items[ 0 ] : items.length > 0 ? items : null;
		},
		remove: function ( element, elementType ) {
			const ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				return;
			}

			if ( !Array.isArray( ee.values ) ) {
				return;
			}
			// Has to be done this way becuase of reasons
			const values = ee.values.filter( ( e ) => e.$type !== elementType );
			ee.values = values;
		},
		assignFromData: function ( element, data ) {
			for ( const key in data ) {
				if ( !data.hasOwnProperty( key ) ) {
					continue;
				}
				const value = data[ key ],
					converted = workflows.editor.util.extensionElements._convertToExtensionElement( key, value );
				if ( Array.isArray( converted ) ) {
					for ( let i = 0; i < converted.length; i++ ) {
						workflows.editor.util.extensionElements.add( element, converted[ i ] );
					}
				} else {
					workflows.editor.util.extensionElements.add( element, converted );
				}
			}
		},
		_convertToExtensionElement: function ( key, value, $parent ) {
			let actualValue = typeof value === 'object' && value.hasOwnProperty( 'value' ) ? value.value : value;
			const attrs = typeof value === 'object' && value.hasOwnProperty( 'attributes' ) ? value.attributes : null;
			const isArray = Array.isArray( actualValue );
			const isObject = typeof ( actualValue ) === 'object' && !isArray;

			if ( typeof value === 'object' && value.hasOwnProperty( 'attributes' ) ) {
				delete ( value.attributes );
			}

			if ( isArray ) {
				const els = [];
				for ( let i = 0; i < actualValue.length; i++ ) {
					els.push( this._convertToExtensionElement( key, actualValue[ i ], $parent ) );
				}
				return els;
			}
			const el = workflows.editor.util.extensionElements.create(
				key,
				attrs || {},
				$parent || null
			);

			if ( !isObject ) {
				if ( typeof actualValue === 'boolean' ) {
					actualValue = actualValue.toString();
				}
				el.text = actualValue;
				return el;
			}

			for ( const subkey in actualValue ) {
				if ( !actualValue.hasOwnProperty( subkey ) ) {
					continue;
				}
				const subel = workflows.editor.util.extensionElements._convertToExtensionElement( subkey, actualValue[ subkey ], el );
				if ( Array.isArray( subel ) ) {
					el.items = el.items || [];
					for ( let i = 0; i < subel.length; i++ ) {
						el.items.push( subel[ i ] );
					}
					return el;
				}
				const subprop = workflows.editor.util.extensionElements._getPropForElement( subkey );
				if ( subprop ) {
					el[ subprop ] = subel;
				}

			}

			return el;
		},
		_getPropForElement: function ( elementName ) {
			const schema = workflows.editor.Schema;
			for ( const ns in schema ) {
				if ( !schema.hasOwnProperty( ns ) ) {
					continue;
				}
				const nsTypes = schema[ ns ].type || [],
					bits = elementName.split( ':' );

				if ( bits.length === 1 ) {
					// NO NS: Invalid
					continue;
				}
				if ( bits[ 0 ] !== ns ) {
					// Not this namespace
					continue;
				}
				for ( let i = 0; i < nsTypes.length; i++ ) {
					const type = nsTypes[ i ];
					const properties = type.properties || [];
					for ( let j = 0; j < properties.length; j++ ) {
						const prop = properties[ j ];
						if ( prop.type.toLowerCase() === bits[ 1 ].toLowerCase() ) {
							return prop.name;
						}
					}
				}
			}
			return elementName;
		}
	},
	workflowContext: {
		setWFContextItem: function ( item, value, element ) {
			let context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				context = workflows.editor.util.extensionElements.create( 'wf:Context', {}, element );
				workflows.editor.util.extensionElements.add( element, context );
			}

			let contextItem = workflows.editor.util.workflowContext.getWFContextItem( item, element );
			if ( !contextItem ) {
				contextItem = workflows.editor.util.extensionElements.create( 'wf:ContextItem', {
					name: item,
					value: value
				}, context );
				if ( !context.get( 'items' ) ) {
					context.set( 'items', [] );
				}

				context.get( 'items' ).push( contextItem );
			} else {
				contextItem.set( 'value', value );
			}
		},
		getWFContextItem: function ( item, element ) {
			const context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				return null;
			}

			return ( context.get( 'items' ) || [] ).find( ( contextItem ) => contextItem.$type === 'wf:ContextItem' && contextItem.get( 'name' ) === item );
		},
		removeWFContextItem: function ( item, element ) {
			const context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				return;
			}
			const contextItem = workflows.editor.util.workflowContext.getWFContextItem( item, element );
			if ( !contextItem ) {
				return;
			}
			const index = context.get( 'items' ).indexOf( contextItem );
			context.items.splice( index, 1 );
		}
	},
	properties: {
		get: function ( element, property ) {
			const properties = element.businessObject.get( 'properties' ) || [];
			for ( let i = 0; i < properties.length; i++ ) {
				if ( properties[ i ].name === property ) {
					return properties[ i ];
				}
			}
		},
		set: function ( element, property, value ) {
			const existing = workflows.editor.util.properties.get( element, property );
			if ( existing ) {
				existing.set( 'default', value );
			} else {
				property = workflows.editor.modeler.get( 'moddle' ).create( 'bpmn:Property', {
					name: property,
					default: value
				} );
				const properties = element.businessObject.get( 'properties' ) || [];
				properties.push( property );
			}
		},
		remove: function ( element, property ) {
			const existing = workflows.editor.util.properties.get( element, property );
			if ( existing ) {
				const properties = element.businessObject.get( 'properties' ) || [];
				const index = properties.indexOf( existing );
				properties.splice( index, 1 );
			}
		}
	}
};

workflows.editor.toolFactory = new OO.ui.ToolFactory();
workflows.editor.toolGroupFactory = new OO.ui.ToolGroupFactory();

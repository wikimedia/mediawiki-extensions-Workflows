workflows.editor.util = {
	extensionElements: {
		getAll: function( element ) {
			if (
				!element ||
				!element.hasOwnProperty( 'businessObject' ) ||
				!element.businessObject.hasOwnProperty( 'extensionElements' )
			) {
				return null;
			}

			return element.businessObject.get( 'extensionElements' );
		},
		create: function( type, attributes, parent ) {
			attributes = attributes || {};
			var el = workflows.editor.modeler.get( 'moddle' ).create( type );
			for ( var key in attributes ) {
				if ( attributes.hasOwnProperty( key ) ) {
					el[key] = attributes[ key ];
				}
			}
			if ( parent ) {
				el.$parent = parent;
			}
			return el;
		},
		add: function( element, extensionElement ) {
			var ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				ee = workflows.editor.modeler.get( 'moddle' ).create( 'bpmn:ExtensionElements' );
			}

			ee.set( 'values', ( ee.get( 'values' ) || [] ).concat( extensionElement ) );
		},
		get: function( element, type ) {
			var ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				return null;
			}

			var items = ( ee.get( 'values' ) || [] ).filter( function( e ) {
				return e.$type === type;
			} );

			return items.length === 1 ? items[ 0 ] : items.length > 0 ? items : null;
		},
		remove: function( element, elementType ) {
			var ee = workflows.editor.util.extensionElements.getAll( element );
			if ( !ee ) {
				return;
			}

			if ( !Array.isArray( ee.values ) ) {
				return;
			}
			// Has to be done this way becuase of reasons
			var values = ee.values.filter( function( e ) {
				return e.$type !== elementType;
			} );
			ee.values = values;
		},
		assignFromData: function( element, data ) {
			for ( var key in data ) {
				if ( !data.hasOwnProperty( key ) ) {
					continue;
				}
				var value = data[key],
					converted = workflows.editor.util.extensionElements._convertToExtensionElement( key, value );
				if ( Array.isArray( converted ) ) {
					for ( var i = 0; i < converted.length; i++ ) {
						workflows.editor.util.extensionElements.add( element, converted[i] );
					}
				} else {
					workflows.editor.util.extensionElements.add( element, converted );
				}
			}
		},
		_convertToExtensionElement: function( key, value, $parent ) {
			var actualValue = typeof value === 'object' && value.hasOwnProperty( 'value' ) ? value.value : value,
				attrs = typeof value === 'object' && value.hasOwnProperty( 'attributes' ) ? value.attributes : null,
				isArray = Array.isArray( actualValue ),
				isObject = typeof ( actualValue ) === 'object' && !isArray;

			if ( typeof value === 'object' && value.hasOwnProperty( 'attributes' ) ) {
				delete( value.attributes );
			}

			if ( isArray ) {
				var els = [];
				for ( var i = 0; i < actualValue.length; i++ ) {
					els.push( this._convertToExtensionElement( key, actualValue[i], $parent ) );
				}
				return els;
			}
			var el = workflows.editor.util.extensionElements.create(
				key,
				attrs ? attrs : {},
				$parent || null
			);

			if ( !isObject ) {
				if ( typeof actualValue === 'boolean' ) {
					actualValue = actualValue.toString();
				}
				el.text = actualValue;
				return el;
			}

			for ( var subkey in actualValue ) {
				if ( !actualValue.hasOwnProperty( subkey ) ) {
					continue;
				}
				var subel = workflows.editor.util.extensionElements._convertToExtensionElement( subkey, actualValue[subkey], el );
				if ( Array.isArray( subel ) ) {
					el.items = el.items || [];
					for ( var i = 0; i < subel.length; i++ ) {
						el.items.push( subel[i] );
					}
					return el;
				}
				var subprop = workflows.editor.util.extensionElements._getPropForElement( subkey );
				if ( subprop ) {
					el[subprop] = subel;
				}

			}

			return el;
		},
		_getPropForElement: function( elementName ) {
			var schema = workflows.editor.Schema;
			for( var ns in schema ) {
				if ( !schema.hasOwnProperty( ns ) ) {
					continue;
				}
				var nsTypes = schema[ns].type || [],
					bits = elementName.split( ':' );

				if ( bits.length === 1 ) {
					// NO NS: Invalid
					continue;
				}
				if ( bits[0] !== ns ) {
					// Not this namespace
					continue;
				}
				for ( var i = 0; i < nsTypes.length; i++ ) {
					var type = nsTypes[i];
					var properties = type.properties || [];
					for ( var j = 0; j < properties.length; j++ ) {
						var prop = properties[j];
						if ( prop.type.toLowerCase() === bits[1].toLowerCase() ) {
							return prop.name;
						}
					}
				}
			}
			return elementName;
		}
	},
	workflowContext: {
		setWFContextItem: function( item, value, element ) {
			var context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				context = workflows.editor.util.extensionElements.create( 'wf:Context', {}, element );
				workflows.editor.util.extensionElements.add( element, context );
			}

			var contextItem = workflows.editor.util.workflowContext.getWFContextItem( item, element );
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
		getWFContextItem: function( item, element ) {
			var context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				return null;
			}

			return ( context.get( 'items' ) || [] ).find( function( contextItem ) {
				return contextItem.$type === 'wf:ContextItem' && contextItem.get( 'name' ) === item;
			} );
		},
		removeWFContextItem: function( item, element ) {
			var context = workflows.editor.util.extensionElements.get( element, 'wf:Context' );
			if ( !context ) {
				return;
			}
			var contextItem = workflows.editor.util.workflowContext.getWFContextItem( item, element );
			if ( !contextItem ) {
				return;
			}
			var index = context.get( 'items' ).indexOf( contextItem );
			context.items.splice( index, 1 );
		},
	},
	properties: {
		get: function( element, property ) {
			var properties = element.businessObject.get( 'properties' ) || [];
			for ( var i = 0; i < properties.length; i++ ) {
				if ( properties[i].name === property ) {
					return properties[i];
				}
			}
		},
		set: function( element, property, value ) {
			var existing = workflows.editor.util.properties.get( element, property );
			if ( existing ) {
				existing.set( 'default', value );
			} else {
				var property = workflows.editor.modeler.get( 'moddle' ).create( 'bpmn:Property', {
					name: property,
					default: value
				} );
				var properties = element.businessObject.get( 'properties' ) || [];
				properties.push( property );
			}
		},
		remove: function( element, property ) {
			var existing = workflows.editor.util.properties.get( element, property );
			if ( existing ) {
				var properties = element.businessObject.get( 'properties' ) || [];
				var index = properties.indexOf( existing );
				properties.splice( index, 1 );
			}
		}
	}
};

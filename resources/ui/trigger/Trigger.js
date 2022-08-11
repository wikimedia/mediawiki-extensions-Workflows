( function ( mw, $ ) {
	workflows.ui.trigger.Trigger = function( data ) {
		OO.EventEmitter.call( this );
		this.value = data || {};
		this.conditionWidgets = { include: {}, exclude: {} };
	};

	OO.inheritClass( workflows.ui.trigger.Trigger, OO.ui.Widget );
	OO.mixinClass( workflows.ui.trigger.Trigger, OO.EventEmitter );

	workflows.ui.trigger.Trigger.static.tagName = 'div';

	workflows.ui.trigger.Trigger.prototype.getFields = function() {
		if ( !this.value.hasOwnProperty( 'active' ) ) {
			this.value.active = true;
		}
		this.name = new OO.ui.TextInputWidget( { required: true, value: this.value.name || '' } );
		this.description = new OO.ui.MultilineTextInputWidget( { value: this.value.description || '' , rows: 2 } );
		this.active = new OO.ui.CheckboxInputWidget( { selected: this.value.active } );

		return [
			new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.FieldLayout( this.name, {
						align: 'top',
						label: mw.message( 'workflows-ui-trigger-field-name' ).text()
					} ),
					new OO.ui.FieldLayout( this.active, {
						align: 'top',
						label: mw.message( 'workflows-ui-trigger-field-active' ).text()
					} )
				]
			} ),
			new OO.ui.FieldLayout( this.description, {
				align: 'top',
				label: mw.message( 'workflows-ui-trigger-field-description' ).text()
			} )
		];
	};

	workflows.ui.trigger.Trigger.prototype.getConditionPanelConfig = function() {
		return {};
	};

	workflows.ui.trigger.Trigger.prototype.getConditionPanel = function() {
		var def = this.getConditionPanelConfig(),
			value = this.value.rules || {},
			$panel = $( '<div>' );
		this.conditionsPanel = new OOJSPlus.ui.widget.ExpandablePanel( {
			$content: $panel,
			// TODO: i18n
			label: 'Conditions',
			collapsed: true,
			expanded: false,
			padded: false
		} );
		this.conditionsPanel.connect( this, {
			stateChange: function() {
				this.emit( 'sizeChange' );
			}
		} );
		var include = $.extend( def.include || {}, value.include || {} );
		var exclude = $.extend( def.exclude || {}, value.exclude || {} );
		this.initConditionSection( 'include', include, $panel );
		this.initConditionSection( 'exclude', exclude || {}, $panel );

		return this.conditionsPanel;
	};

	workflows.ui.trigger.Trigger.prototype.initConditionSection = function( type, config, $panel ) {
		for( var key in config ) {
			if ( !config.hasOwnProperty( key ) || config[key] === false ) {
				continue;
			}
			var widget = this.getConditionWidget( key, type );
			if ( !widget ) {
				continue;
			}
			this.conditionWidgets[type][key] = widget;
			/*
				workflows-trigger-ui-condition-include-namespace
				workflows-trigger-ui-condition-exclude-namespace
				workflows-trigger-ui-condition-exclude-category
				workflows-trigger-ui-condition-include-category
				workflows-trigger-ui-condition-exclude-editType
			*/
			$panel.append( new OO.ui.FieldLayout( this.conditionWidgets[type][key], {
				label: mw.message( 'workflows-trigger-ui-condition-' + type + '-' + key ).text(),
				align: 'left'
			} ).$element );
		}
	};

	workflows.ui.trigger.Trigger.prototype.getConditionWidget = function( key, type ) {
		switch ( key ) {
			case 'namespace':
				var value = workflows.util.getDeepValue( this.value, 'rules.' + type + '.namespace' ) || [];
				value = value.map(
					function( id ) {
						return id + '';
					}
				);
				return new mw.widgets.NamespacesMultiselectWidget( {
					$overlay: true,
					selected: value
				} );
			case 'category':
				return new OOJSPlus.ui.widget.CategoryMultiSelectWidget( {
					$overlay: true,
					selected: workflows.util.getDeepValue( this.value, 'rules.' + type + '.category' ) || []
				} );
			case 'editType':
				var value = workflows.util.getDeepValue( this.value, 'rules.' + type + '.editType' ) || null;
				return new OO.ui.CheckboxInputWidget( { selected: value === 'minor' } );
			default:
				return null;
		}
	};

	workflows.ui.trigger.Trigger.prototype.getConditionValue = function() {
		return {
			include: this.getConditionGroupValue( this.conditionWidgets.include || {}, 'include' ),
			exclude: this.getConditionGroupValue( this.conditionWidgets.exclude || {}, 'exclude' ),
		};
	};

	workflows.ui.trigger.Trigger.prototype.getConditionGroupValue = function( items, type ) {
		var value = {};
		for ( var key in items ) {
			if ( !items.hasOwnProperty( key ) ) {
				continue;
			}
			// Special case, "only major edits" is always an exclude rule, exclude minor
			if ( key === 'editType' && type === 'exclude' && items[key].isSelected() ) {
				value.editType = 'minor';
			} else {
				var itemVal = items[key].getValue();
				if ( typeof itemVal === 'object' && $.isEmptyObject( itemVal ) ) {
					continue;
				}
				if ( itemVal ) {
					value[key] = items[key].getValue();
				}
			}
		}

		return value;
	};

	workflows.ui.trigger.Trigger.prototype.generateData = function() {
		this.value.active = this.active.isSelected();
		this.value.name = this.name.getValue();
		this.value.description = this.description.getValue();

		this.value.rules = this.getConditionValue();
		if ( !this.value.hasOwnProperty( 'id' ) ) {
			this.value.id = this.generateTriggerId( this.value.name, this.value.type );
		}
	};

	workflows.ui.trigger.Trigger.prototype.generateTriggerId = function( name, type ) {
		var key = name.toLowerCase().replace( ' ', '-' ).trim();
		return 'trigger-' + key + '-' + type;
	};

	workflows.ui.trigger.Trigger.prototype.getValue = function() {
		var dfd = $.Deferred();

		this.getValidity().done( function() {
			dfd.resolve( this.generateData() );
		}.bind( this ) ).fail( function() {
			dfd.reject();
		} );

		return dfd.promise();
	};

	workflows.ui.trigger.Trigger.prototype.getValidity = function() {
		return this.name.getValidity();
	};
} )( mediaWiki, jQuery );

( function ( mw, $ ) {
	workflows.ui.trigger.Trigger = function ( data, cfg ) {
		cfg = cfg || {};
		this.$overlay = cfg.$overlay || null;
		OO.EventEmitter.call( this );
		this.value = data || {};
		this.alienConditions = { include: {}, exclude: {} };
		this.conditionWidgets = { include: {}, exclude: {} };
	};

	OO.inheritClass( workflows.ui.trigger.Trigger, OO.ui.Widget );
	OO.mixinClass( workflows.ui.trigger.Trigger, OO.EventEmitter );

	workflows.ui.trigger.Trigger.static.tagName = 'div';

	workflows.ui.trigger.Trigger.prototype.getFields = function () {
		if ( !this.value.hasOwnProperty( 'active' ) ) {
			this.value.active = true;
		}
		let name = this.value.name || '';
		if ( name && mw.message( name ).exists() ) { // eslint-disable-line mediawiki/msg-doc
			name = mw.message( name ).text(); // eslint-disable-line mediawiki/msg-doc
		}
		this.name = new OO.ui.TextInputWidget( {
			required: true,
			value: name
		} );
		let description = this.value.description || '';
		if ( description && mw.message( description ).exists() ) { // eslint-disable-line mediawiki/msg-doc
			description = mw.message( description ).text(); // eslint-disable-line mediawiki/msg-doc
		}
		this.description = new OO.ui.MultilineTextInputWidget( {
			value: description,
			rows: 2
		} );
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

	workflows.ui.trigger.Trigger.prototype.getConditionPanelConfig = function () {
		return {};
	};

	workflows.ui.trigger.Trigger.prototype.getConditionPanel = function () {
		const def = this.getConditionPanelConfig(),
			value = this.value.rules || {},
			$panel = $( '<div>' );
		this.conditionsPanel = new OOJSPlus.ui.widget.ExpandablePanel( {
			$content: $panel,
			label: mw.message( 'workflows-ui-trigger-field-conditions' ).text(),
			collapsed: true,
			expanded: false,
			padded: false
		} );
		this.conditionsPanel.connect( this, {
			stateChange: function () {
				this.emit( 'sizeChange' );
			}
		} );
		const include = $.extend( def.include || {}, value.include || {} );
		const exclude = $.extend( def.exclude || {}, value.exclude || {} );
		this.initConditionSection( 'include', include, $panel );
		this.initConditionSection( 'exclude', exclude || {}, $panel );

		return this.conditionsPanel;
	};

	workflows.ui.trigger.Trigger.prototype.initConditionSection = function ( type, config, $panel ) {
		for ( const key in config ) {
			if ( !config.hasOwnProperty( key ) || config[ key ] === false ) {
				continue;
			}
			const widget = this.getConditionWidget( key, type );
			if ( !widget ) {
				// Could not find a widget for this condition, consider it an alien condition
				// and store it so that it can be preserved when saving
				this.alienConditions[ type ][ key ] = config[ key ];
				continue;
			}
			this.conditionWidgets[ type ][ key ] = widget;
			$panel.append( new OO.ui.FieldLayout( this.conditionWidgets[ type ][ key ], {
				// The following messages are used here:
				// * workflows-trigger-ui-condition-include-namespace
				// * workflows-trigger-ui-condition-exclude-namespace
				// * workflows-trigger-ui-condition-exclude-category
				// * workflows-trigger-ui-condition-include-category
				// * workflows-trigger-ui-condition-exclude-editType
				label: mw.message( 'workflows-trigger-ui-condition-' + type + '-' + key ).text(),
				align: 'left'
			} ).$element );
		}
	};

	workflows.ui.trigger.Trigger.prototype.getConditionWidget = function ( key, type ) {
		let value;
		switch ( key ) {
			case 'namespace':
				value = workflows.util.getDeepValue( this.value, 'rules.' + type + '.namespace' ) || [];
				value = value.map(
					( id ) => String( id )
				);
				return new mw.widgets.NamespacesMultiselectWidget( {
					$overlay: this.$overlay,
					selected: value
				} );
			case 'category':
				return new OOJSPlus.ui.widget.CategoryMultiSelectWidget( {
					$overlay: this.$overlay,
					selected: workflows.util.getDeepValue( this.value, 'rules.' + type + '.category' ) || []
				} );
			case 'editType':
				value = workflows.util.getDeepValue( this.value, 'rules.' + type + '.editType' ) || null;
				return new OO.ui.CheckboxInputWidget( { selected: value === 'minor' } );
			default:
				return null;
		}
	};

	workflows.ui.trigger.Trigger.prototype.getConditionValue = function () {
		return {
			include: $.extend(
				this.alienConditions.include,
				this.getConditionGroupValue( this.conditionWidgets.include || {}, 'include' )
			),
			exclude: $.extend(
				this.alienConditions.exclude, this.getConditionGroupValue( this.conditionWidgets.exclude || {}, 'exclude' )
			)
		};
	};

	workflows.ui.trigger.Trigger.prototype.getConditionGroupValue = function ( items, type ) {
		const value = {};
		for ( const key in items ) {
			if ( !items.hasOwnProperty( key ) ) {
				continue;
			}
			// Special case, "only major edits" is always an exclude rule, exclude minor
			if ( key === 'editType' && type === 'exclude' && items[ key ].isSelected() ) {
				value.editType = 'minor';
			} else {
				const itemVal = items[ key ].getValue();
				if ( typeof itemVal === 'object' && $.isEmptyObject( itemVal ) ) {
					continue;
				}
				if ( itemVal ) {
					value[ key ] = items[ key ].getValue();
				}
			}
		}

		return value;
	};

	workflows.ui.trigger.Trigger.prototype.generateData = function () {
		this.value.active = this.active.isSelected();
		this.value.name = this.name.getValue();
		this.value.description = this.description.getValue();

		this.value.rules = this.getConditionValue();
		if ( !this.value.hasOwnProperty( 'id' ) ) {
			this.value.id = this.generateTriggerId( this.value.name, this.value.type );
		}
	};

	workflows.ui.trigger.Trigger.prototype.generateTriggerId = function ( name, type ) {
		const key = name.toLowerCase().replace( ' ', '-' ).trim();
		return 'trigger-' + key + '-' + type;
	};

	workflows.ui.trigger.Trigger.prototype.getValue = function () {
		const dfd = $.Deferred();

		this.getValidity().done( () => {
			dfd.resolve( this.generateData() );
		} ).fail( () => {
			dfd.reject();
		} );

		return dfd.promise();
	};

	workflows.ui.trigger.Trigger.prototype.getValidity = function () {
		return this.name.getValidity();
	};
}( mediaWiki, jQuery ) );

( function ( mw, $ ) {
	workflows.object.form.Form = function( cfg, activity ) {
		OO.EventEmitter.call( this );

		cfg = cfg || {};

		this.properties = cfg.properties || {};
		this.buttons = cfg.hasOwnProperty( 'buttons' ) ? cfg.buttons : [ 'submit' ];
		this.activity = activity || {};
		this.action = activity.getState() === workflows.state.activity.COMPLETE ? 'view' : 'edit';
		this.moduleData = cfg.moduleData || {};

		var formConfig = {};
		if ( this.moduleData.hasOwnProperty( 'definitionJSON' ) ) {
			var definition = JSON.parse( this.moduleData.definitionJSON );
			mw.ext.forms.widget.Form.static.convertToJS( definition );
			formConfig.definition = $.extend( true, {}, definition, {
				buttons: this.getButtons()
			} );
			delete( formConfig.definition.listeners );
		} else {
			formConfig.definition = {
				items: this.getDefinitionItems( this.properties ),
				buttons: this.getButtons()
			};
		}
		if ( this.getTitle() ) {
			formConfig.title = this.getTitle();
			formConfig.showTitle = true;
		}
		formConfig.errorReporting = false;
		formConfig.data = this.properties;
		workflows.object.form.Form.parent.call( this, formConfig );

		this.render();
	};

	OO.inheritClass( workflows.object.form.Form, mw.ext.forms.standalone.Form );
	OO.mixinClass( workflows.object.form.Form, OO.EventEmitter );

	workflows.object.form.Form.prototype.getDefinitionItems = function() {
		var items = [];
		for ( var prop in this.properties ) {
			if ( !this.properties.hasOwnProperty( prop ) ) {
				continue;
			}
			items.push( {
				type: 'text',
				name: prop,
				label: prop
			} );
		}

		return items;
	};

	workflows.object.form.Form.prototype.getButtons = function() {
		return this.buttons;
	};

	workflows.object.form.Form.prototype.getData = function() {
		return this.properties;
	};

	workflows.object.form.Form.prototype.getAction = function() {
		return this.action;
	};

	workflows.object.form.Form.prototype.getTitle = function() {
		return this.activity.getName();
	};

	mw.ext.forms.standalone.Form.prototype.onDataSubmitted = function( data, summary ) {
		this.emit( 'submit', data, summary );
	};

} )( mediaWiki, jQuery );

( function ( mw, $ ) {
	workflows.ui.TriggerDetailsPage = function( name, cfg ) {
		workflows.ui.TriggerDetailsPage.parent.call( this, name, cfg );

		this.panel = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false
		} );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.TriggerDetailsPage, OO.ui.PageLayout );

	workflows.ui.TriggerDetailsPage.prototype.init = function( data ) {
		this.editor = data.editor || null;
		this.editorWidget = null;
		this.value = $.extend( {}, data.value || {}, {
			type: data.type
		} );

		var layout = new OO.ui.FieldsetLayout( {
			label: data.label,
			help: data.desc,
			helpInline: true
		} );

		this.initEditor().done( function( editor ) {
			editor.connect( this, {
				sizeChange: function() {
					this.emit( 'sizeChange' );
				},
				loading: function() {
					this.emit( 'loading' );
				},
				loaded: function() {
					this.emit( 'loaded' );
				}
			} );
			layout.addItems( editor.getFields() );
			layout.addItems( [ editor.getConditionPanel() ] );
			this.panel.$element.append( layout.$element );
			this.editorWidget = editor;
			this.emit( 'loaded' );
		}.bind( this ) );
	};

	workflows.ui.TriggerDetailsPage.prototype.getTitle = function() {
		return mw.message( 'workflows-ui-workflow-trigger-editor-booklet-page-details-title' ).text();
	};

	workflows.ui.TriggerDetailsPage.prototype.reset = function() {
	};

	workflows.ui.TriggerDetailsPage.prototype.getValidity = function() {
		if ( !this.editorWidget ) {
			console.error( 'Called "getValidity" before instantiating editor' );
			return $.Deferred().reject().promise();
		}
		return this.editorWidget.getValidity();
	};

	workflows.ui.TriggerDetailsPage.prototype.getValue = function() {
		if ( !this.editorWidget ) {
			console.error( 'Called "getValue" before instantiating editor' );
			return $.Deferred().reject().promise();
		}
		return this.editorWidget.getValue();
	};

	workflows.ui.TriggerDetailsPage.prototype.initEditor = function() {
		var dfd = $.Deferred();

		mw.loader.using( this.editor.module, function() {
			var cls = this.editor.class || '',
				cb = this.editor.cb || '',
				func = '', editor = null;

			if ( cls ) {
				func = workflows.util.callbackFromString( cls );
				editor = new func( this.value );
			} else if ( cb ) {
				func = workflows.util.callbackFromString( cb );
				editor = func( this.value );
			}
			if ( editor instanceof workflows.ui.trigger.Trigger ){
				dfd.resolve( editor );
			} else {
				dfd.reject( mw.message( 'workflows-ui-trigger-editor-error' ).text() );
			}
		}.bind( this ) );

		return dfd.promise();
	};

} )( mediaWiki, jQuery );

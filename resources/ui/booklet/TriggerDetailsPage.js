( function ( mw, $ ) {
	workflows.ui.TriggerDetailsPage = function ( name, cfg ) {
		workflows.ui.TriggerDetailsPage.parent.call( this, name, cfg );

		this.$overlay = cfg.$overlay;
		this.panel = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false
		} );

		this.$element.append( this.panel.$element );
	};

	OO.inheritClass( workflows.ui.TriggerDetailsPage, OO.ui.PageLayout );

	workflows.ui.TriggerDetailsPage.prototype.init = function ( data ) {
		this.editor = data.editor || null;
		this.editorWidget = null;
		this.value = Object.assign( {}, data.value || {}, {
			type: data.type
		} );

		const layout = new OO.ui.FieldsetLayout( {
			label: data.label,
			help: data.desc,
			helpInline: true
		} );

		this.initEditor().done( ( editor ) => {
			editor.connect( this, {
				sizeChange: function () {
					this.emit( 'sizeChange' );
				},
				loading: function () {
					this.emit( 'loading' );
				},
				loaded: function () {
					this.emit( 'loaded' );
				}
			} );
			layout.addItems( editor.getFields() );
			layout.addItems( [ editor.getConditionPanel() ] );
			this.panel.$element.append( layout.$element );
			this.editorWidget = editor;
			this.emit( 'loaded' );
		} );
	};

	workflows.ui.TriggerDetailsPage.prototype.getTitle = function () {
		return mw.message( 'workflows-ui-workflow-trigger-editor-booklet-page-details-title' ).text();
	};

	workflows.ui.TriggerDetailsPage.prototype.reset = function () {
	};

	workflows.ui.TriggerDetailsPage.prototype.getValidity = function () {
		if ( !this.editorWidget ) {
			console.error( 'Called "getValidity" before instantiating editor' ); // eslint-disable-line no-console
			return $.Deferred().reject().promise();
		}
		return this.editorWidget.getValidity();
	};

	workflows.ui.TriggerDetailsPage.prototype.getValue = function () {
		if ( !this.editorWidget ) {
			console.error( 'Called "getValue" before instantiating editor' ); // eslint-disable-line no-console
			return $.Deferred().reject().promise();
		}
		return this.editorWidget.getValue();
	};

	workflows.ui.TriggerDetailsPage.prototype.initEditor = function () {
		const dfd = $.Deferred();

		mw.loader.using( this.editor.module, () => {
			const cls = this.editor.class || '';
			const cb = this.editor.cb || '';
			let func = '', editor = null;

			if ( cls ) {
				func = workflows.util.callbackFromString( cls );
				editor = new func( this.value, { $overlay: this.$overlay } ); // eslint-disable-line new-cap
			} else if ( cb ) {
				func = workflows.util.callbackFromString( cb );
				editor = func( this.value, { $overlay: this.$overlay } );
			}
			if ( editor instanceof workflows.ui.trigger.Trigger ) {
				dfd.resolve( editor );
			} else {
				dfd.reject( mw.message( 'workflows-ui-trigger-editor-error' ).text() );
			}
		} );

		return dfd.promise();
	};

}( mediaWiki, jQuery ) );

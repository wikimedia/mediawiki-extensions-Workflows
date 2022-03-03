( function ( mw, $, wf ) {
	workflows.ui.panel.TriggerEditor = function( cfg ) {
		cfg = $.extend( {
			padded: true
		}, cfg || {} );

		workflows.ui.panel.TriggerEditor.parent.call( this, cfg );

		this.triggerData = {};

		mw.loader.using( [ 'ext.workflows.trigger.editor.panel', 'ext.workflows.trigger.editor.dialog' ], function() {
			workflows.triggers.getAvailableTypes().done( function( types ) {
				workflows.triggers.getAll().done( function( data ) {
					this.$triggerCnt = $( '<div>' ).addClass( 'workflows-ui-trigger-cnt' );
					this.appendHeader();
					this.appendTriggers( data, types );
					this.$element.append( this.$triggerCnt );
					this.emit( 'loaded' );
				}.bind( this ) ).fail( function() {
					this.showError();
				}.bind( this ) );
			}.bind( this ) ).fail( function() {
				this.showError();
			}.bind( this ) );

		}.bind( this ) );
	};

	OO.inheritClass( workflows.ui.panel.TriggerEditor, OO.ui.PanelLayout );

	workflows.ui.panel.TriggerEditor.prototype.appendTriggers = function( data, types ) {
		for ( var triggerId in data ) {
			if ( !data.hasOwnProperty( triggerId ) ) {
				continue;
			}
			var triggerData = data[triggerId];
			this.triggerData[triggerId] = triggerData;

			if ( !types.hasOwnProperty( triggerData.type ) ) {
				console.warn( 'Type of trigger ' + triggerId + ' is not supported' );
				continue;
			}
			var widget = new workflows.ui.widget.TriggerEntity( triggerId, triggerData, types[triggerData.type], {
				editable: types[triggerData.type].hasOwnProperty( 'editor' ) && types[triggerData.type].editor !== null
			} );


			widget.connect( this, {
				edit: 'editTrigger',
				delete: 'deleteTrigger'
			} );
			this.$triggerCnt.append( widget.$element );
		}
	};

	workflows.ui.panel.TriggerEditor.prototype.showError = function() {
		//TODO: I18n
		this.$element.html( new OO.ui.MessageWidget( {
			type: 'error',
			label: mw.message( 'workflows-error-generic' ).text()
		} ).$element );
		this.emit( 'loaded' );
	};

	workflows.ui.panel.TriggerEditor.prototype.appendHeader = function() {
		var button = new OO.ui.ButtonWidget( {
			//TODO: I18n
			label: mw.message( 'workflows-ui-triggers-add-button' ).text(),
			icon: 'add',
			flags: [ 'primary', 'progressive' ]
		} );

		button.connect( this, {
			click: 'openEmptyTriggerDialog'
		} );

		this.$element.append( button.$element );
	};

	workflows.ui.panel.TriggerEditor.prototype.editTrigger = function( id, data, typeDesc ) {
		data.id = id;
		this.openEditDialog( $.extend( {}, typeDesc, {
			type: data.type,
			value: data
		} ) );
	};

	workflows.ui.panel.TriggerEditor.prototype.deleteTrigger = function( id ) {
		if ( !this.triggerData.hasOwnProperty( id ) ) {
			return;
		}
		this.openDeleteDialog( id );
	};

	workflows.ui.panel.TriggerEditor.prototype.openEmptyTriggerDialog = function() {
		this.openEditDialog( null );
	};

	workflows.ui.panel.TriggerEditor.prototype.openEditDialog = function( data ) {
		this.doOpenDialog( new workflows.ui.dialog.TriggerEditor( { triggerData: data, allData: this.triggerData } ) );
	};

	workflows.ui.panel.TriggerEditor.prototype.openDeleteDialog = function( key, data ) {
		this.doOpenDialog( new workflows.ui.dialog.DeleteTrigger( { key: key, data: data } ) );
	};

	workflows.ui.panel.TriggerEditor.prototype.doOpenDialog = function( dialog ) {
		var windowManager = new OO.ui.WindowManager();
		$( document.body ).append( windowManager.$element );
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog ).closed.then( function() {
			$( document.body ).remove( windowManager.$element );
		} );
	};

} )( mediaWiki, jQuery, workflows );

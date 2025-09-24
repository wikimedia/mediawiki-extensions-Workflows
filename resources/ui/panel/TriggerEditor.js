workflows.ui.panel.TriggerEditor = function ( cfg ) {
	workflows.ui.panel.TriggerEditor.parent.call( this, cfg );
	this.editable = false;
};

OO.inheritClass( workflows.ui.panel.TriggerEditor, workflows.ui.panel.TriggerOverview );

workflows.ui.panel.TriggerEditor.prototype.load = function () {
	mw.loader.using( [ 'ext.workflows.trigger.editor.dialog' ], () => {
		workflows.ui.panel.TriggerEditor.parent.prototype.load.call( this );
	} );
};

workflows.ui.panel.TriggerEditor.prototype.render = function ( data, types ) {
	mw.user.getRights().done( ( rights ) => {
		if ( rights.includes( 'workflows-admin' ) ) {
			this.editable = true;
		}
		this.$triggerCnt = $( '<div>' ).addClass( 'workflows-ui-trigger-cnt' );
		if ( this.editable ) {
			this.appendHeader();
		}
		this.appendTriggers( data, types );
		if ( $( document ).find( '#oojsplus-skeleton-cnt' ) ) {
			$( '#oojsplus-skeleton-cnt' ).empty();
		}
		if ( $( '#workflows-triggers-hint:visible' ).length === 0 ) { // eslint-disable-line no-jquery/no-sizzle
			$( '#workflows-triggers-hint' )[ 0 ].style.display = 'block';
		}
		this.$element.append( this.$triggerCnt );
		this.emit( 'loaded' );
	} );
};

workflows.ui.panel.TriggerEditor.prototype.appendTriggers = function ( data, types ) {
	for ( const triggerId in data ) {
		if ( !data.hasOwnProperty( triggerId ) ) {
			continue;
		}
		const triggerData = data[ triggerId ];
		this.triggerData[ triggerId ] = triggerData;

		if ( !types.hasOwnProperty( triggerData.type ) ) {
			console.warn( 'Type of trigger ' + triggerId + ' is not supported' ); // eslint-disable-line no-console
			continue;
		}
		const widget = new workflows.ui.widget.TriggerEntity( triggerId, triggerData, types[ triggerData.type ], {
			editMode: this.editable,
			editable: types[ triggerData.type ].hasOwnProperty( 'editor' ) && types[ triggerData.type ].editor !== null
		} );

		widget.connect( this, {
			edit: 'editTrigger',
			delete: 'deleteTrigger'
		} );
		this.$triggerCnt.append( widget.$element );
	}
};

workflows.ui.panel.TriggerEditor.prototype.appendHeader = function () {
	this.toolbar = new OOJSPlus.ui.toolbar.ManagerToolbar( {
		actions: [
			new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
				name: 'add',
				icon: 'add',
				label: mw.msg( 'workflows-ui-triggers-add-button' ),
				flags: [ 'progressive' ],
				title: mw.msg( 'workflows-ui-triggers-add-button' ),
				displayBothIconAndLabel: true
			} )
		],
		saveable: false,
		cancelable: false
	} );
	this.toolbar.connect( this, {
		action: ( action ) => {
			if ( action === 'add' ) {
				this.openEmptyTriggerDialog();
			}
		}
	} );
	this.toolbar.setup();

	this.$element.append( this.toolbar.$element );
};

workflows.ui.panel.TriggerEditor.prototype.onCancel = function () {
	const title = mw.Title.newFromText( mw.config.get( 'wgPageName' ) );
	if ( title ) {
		window.location.href = title.getUrl();
	}
};

workflows.ui.panel.TriggerEditor.prototype.editTrigger = function ( id, data, typeDesc ) {
	data.id = id;
	this.openEditDialog( Object.assign( {}, typeDesc, {
		type: data.type,
		value: data
	} ) );
};

workflows.ui.panel.TriggerEditor.prototype.deleteTrigger = function ( id ) {
	if ( !this.triggerData.hasOwnProperty( id ) ) {
		return;
	}
	this.openDeleteDialog( id );
};

workflows.ui.panel.TriggerEditor.prototype.openEmptyTriggerDialog = function () {
	this.openEditDialog( null );
};

workflows.ui.panel.TriggerEditor.prototype.openEditDialog = function ( data ) {
	this.doOpenDialog( new workflows.ui.dialog.TriggerEditor( { triggerData: data, allData: this.triggerData } ) );
};

workflows.ui.panel.TriggerEditor.prototype.openDeleteDialog = function ( key, data ) {
	this.doOpenDialog( new workflows.ui.dialog.DeleteTrigger( { key: key, data: data } ) );
};

workflows.ui.panel.TriggerEditor.prototype.doOpenDialog = function ( dialog ) {
	const windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog ).closed.then( () => {
		$( document.body ).remove( windowManager.$element );
	} );
};

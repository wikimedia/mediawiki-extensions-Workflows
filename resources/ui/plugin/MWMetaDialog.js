workflows.ui.plugin.MWMetaDialog = function ( component ) { // eslint-disable-line no-unused-vars
	workflows.ui.plugin.MWMetaDialog.super.apply( this, arguments );
};

OO.inheritClass( workflows.ui.plugin.MWMetaDialog, bs.vec.ui.plugin.MWMetaDialog );

workflows.ui.plugin.MWMetaDialog.prototype.initialize = function () {
	this.component.advancedSettingsPage.nowfexec = new OO.ui.FieldLayout(
		new OO.ui.ButtonSelectWidget()
			.addItems( [
				new OO.ui.ButtonOptionWidget( {
					data: 'default',
					label: mw.msg( 'workflows-ui-meta-noworkflows-yes' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/NOWORKFLOWEXECUTION',
					label: mw.msg( 'workflows-ui-meta-noworkflows-no' )
				} )
			] )
			.connect( this, { select: 'onNoWorkflowsChange' } ),
		{
			$overlay: this.component.$overlay,
			align: 'top',
			label: mw.msg( 'workflows-ui-meta-noworkflows-label' )
		}
	);

	this.component.advancedSettingsPage.advancedSettingsFieldset.$element.append(
		this.component.advancedSettingsPage.nowfexec.$element
	);
};

workflows.ui.plugin.MWMetaDialog.prototype.getSetupProcess = function ( parentProcess, data ) {
	const advancedSettingsPage = this.component.advancedSettingsPage;
	this.component.advancedSettingsPage.setup( data.fragment, data );
	const metaList = data.fragment.getSurface().metaList;

	const field = advancedSettingsPage.nowfexec.getField();
	advancedSettingsPage.metaList = metaList;
	const option = advancedSettingsPage.getMetaItem( 'noworkflowexecution' );
	const metaData = option ? 'mw:PageProp/NOWORKFLOWEXECUTION' : 'default';

	field.selectItemByData( metaData );

	return parentProcess;
};

workflows.ui.plugin.MWMetaDialog.prototype.getTeardownProcess = function ( parentProcess ) {
	const advancedSettingsPage = this.component.advancedSettingsPage;

	const option = advancedSettingsPage.getMetaItem( 'noworkflowexecution' );
	const metaData = advancedSettingsPage.nowfexec.getField().findSelectedItem();

	if ( option ) {
		advancedSettingsPage.fragment.removeMeta( option );
	}
	if ( metaData.data !== 'default' ) {
		const item = { type: 'noworkflowexecution' };
		this.component.getFragment().insertMeta( item, 0 );
	}

	return parentProcess;
};

/**
 * Handle option state change events.
 */
workflows.ui.plugin.MWMetaDialog.prototype.onNoWorkflowsChange = function () {
	this.component.actions.setAbilities( {
		done: true
	} );
};

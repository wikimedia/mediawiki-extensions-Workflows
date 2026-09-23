workflows.mixin.SignedActivity = function () {};

OO.initClass( workflows.mixin.SignedActivity );

workflows.mixin.SignedActivity.prototype.getSignatureEditorFormFields = function () {
	return [ {
		type: 'checkbox',
		name: 'properties.require_signing',
		label: mw.msg( 'workflows-ui-editor-inspector-activity-require-signature' ),
		help: mw.msg( 'workflows-ui-editor-inspector-activity-require-signature-help' )
	}, {
		type: 'textarea',
		name: 'properties.signing_confirmation_text',
		label: mw.msg( 'workflows-ui-editor-inspector-activity-signature-confirmation-text' ),
		help: mw.msg( 'workflows-ui-editor-inspector-activity-signature-confirmation-text-help' )
	} ];
};

workflows.mixin.SignedActivity.prototype.getSignatureFormFields = function () {
	return [
		{
			type: 'checkbox',
			name: 'require_signing',
			hidden: true
		},
		{
			type: 'textarea',
			name: 'signing_confirmation_text',
			hidden: true
		},
		{
			type: 'message',
			name: 'signing_requirement_notice',
			widget_type: 'warning',
			hidden: true
		}
	];
};

workflows.mixin.SignedActivity.prototype.onSignatureFormAfterInit = function ( form ) {
	const requirementField = form.getItem( 'require_signing' );
	const requirementNoticeField = form.getItem( 'signing_requirement_notice' );

	if ( requirementNoticeField && requirementField && requirementField.getValue() ) {
		const confirmationText = form.getItem( 'signing_confirmation_text' ).getValue();
		let message = mw.msg( 'workflows-ui-editor-inspector-activity-signature-requirement-notice' );
		if ( confirmationText ) {
			message += '<br>' + confirmationText;
		}
		requirementNoticeField.setValue( new OO.ui.HtmlSnippet( message ) );
		form.showItem( 'signing_requirement_notice' );
	}
};

workflows.mixin.SignedActivity.prototype.mustSign = function ( data ) {
	return data.require_signing || false;
};

workflows.mixin.SignedActivity.prototype.postToSignaturePage = function ( data, activity ) {
	const postData = this.getSignaturePostData( data );
	const wfId = activity.workflow.id;
	const activityId = activity.id;

	const signaturePage = mw.Title.makeTitle( -1, 'SignWorkflowActivity' );
	const form = document.createElement( 'form' );
	form.method = 'POST';
	form.action = signaturePage.getUrl( { backTo: mw.config.get( 'wgPageName' ) } );

	const input = document.createElement( 'input' );
	input.type = 'hidden';
	input.name = 'activity_data';
	input.value = JSON.stringify( postData );
	form.appendChild( input );

	const wfIdInput = document.createElement( 'input' );
	wfIdInput.type = 'hidden';
	wfIdInput.name = 'workflow_id';
	wfIdInput.value = wfId;
	form.appendChild( wfIdInput );

	const activityIdInput = document.createElement( 'input' );
	activityIdInput.type = 'hidden';
	activityIdInput.name = 'activity_id';
	activityIdInput.value = activityId;
	form.appendChild( activityIdInput );

	document.body.appendChild( form );
	form.submit();
};

workflows.mixin.SignedActivity.prototype.getSignaturePostData = function ( data ) {
	delete ( data.require_signing );
	delete ( data.signing_confirmation_text );
	return data;
};

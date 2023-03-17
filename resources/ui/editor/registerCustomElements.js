workflows.editor.element.registry.register( 'custom_form', {
	class: 'activity-custom-form',
	label: mw.message( 'workflows-uto-activity-custom_form' ).text(),
	defaultData: {
		extensionElements: {
			'wf:Form': ''
		}
	}
} );

workflows.editor.element.registry.register( 'user_vote', {
	class: 'activity-user-vote',
	label: mw.message( 'workflows-uto-activity-user_vote' ).text(),
	defaultData: {
		properties: {
			instructions: '',
			due_date: '',
			vote: '',
			comment: '',
			delegate_to: '',
			delegate_comment: '',
			assigned_user: '',
			action: ''
		}
	}
} );

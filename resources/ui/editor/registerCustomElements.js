workflows.editor.element.registry.register( 'custom_form', {
	class: 'activity-custom-form activity-bootstrap-icon',
	label: mw.message( 'workflows-uto-activity-custom_form' ).text(),
	defaultData: {
		extensionElements: {
			'wf:Form': ''
		}
	}
} );

workflows.editor.element.registry.register( 'send_mail', {
	isUserActivity: false,
	class: 'activity-send-mail activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-send-mail-title' ).text(),
	defaultData: {
		properties: {
			recipient: '{{{CollectData.reportrecipient}}}',
			subject: '',
			body: ''
		}
	}
} );

workflows.editor.element.registry.register( 'user_feedback', {
	isUserActivity: true,
	class: 'activity-user-feedback activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-user-feedback-title' ).text(),
	defaultData: {
		properties: {
			assigned_user: '',
			instructions: '',
			due_date: ''
		}
	}
} );

workflows.editor.element.registry.register( 'user_vote', {
	isUserActivity: true,
	class: 'activity-user-vote activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-user-vote-title' ).text(),
	defaultData: {
		properties: {
			assigned_user: '',
			instructions: '',
			due_date: ''
		}
	}
} );

workflows.editor.element.registry.register( 'group_feedback', {
	isUserActivity: true,
	class: 'activity-group-feedback activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-group-feedback-title' ).text(),
	defaultData: {
		properties: {
			assigned_users: '',
			assigned_group: '',
			instructions: '',
			due_date: '',
			threshold_unit: '',
			threshold_value: '',
			users_feedbacks: '',
			comment: '',
		}
	}
} );

workflows.editor.element.registry.register( 'group_vote', {
	isUserActivity: true,
	class: 'activity-group-vote activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-group-vote-title' ).text(),
	defaultData: {
		properties: {
			assigned_users: '',
			assigned_group: '',
			instructions: '',
			due_date: '',
			threshold_yes_unit: '',
			threshold_yes_value: '',
			threshold_no_unit: '',
			threshold_no_value: '',
			users_voted: '',
			vote: '',
			comment: '',
		}
	}
} );

workflows.editor.element.registry.register( 'edit_page', {
	isUserActivity: false,
	class: 'activity-edit-page activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-edit-page-title' ).text(),
	defaultData: {
		properties: {
			title: '',
			user: '',
			content: '',
			minor: false,
			mode: 'append'
		}
	}
} );

workflows.editor.element.registry.register( 'edit_request', {
	isUserActivity: true,
	class: 'activity-edit-request activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-edit-request-title' ).text(),
	defaultData: {
		properties: {
			assigned_user: '',
			instructions: '',
			due_date: ''
		}
	}
} );

workflows.editor.element.registry.register( 'set_template_param', {
	isUserActivity: false,
	class: 'activity-set-template-param activity-bootstrap-icon',
	label: mw.message( 'workflows-ui-editor-inspector-activity-set-template-param-title' ).text(),
	defaultData: {
		properties: {
			user: '',
			title: '',
			'template-index': '',
			'template-param': '',
			value: '',
			minor: '',
			comment: ''
		}
	}
} );

( function ( mw, $ ) {
	workflows.ui.trigger.PageRelated = function ( data, cfg ) {
		cfg = cfg || {};
		workflows.ui.trigger.PageRelated.parent.call( this, data, cfg );
		workflows.ui.trigger.mixin.WorkflowSelector.call( this, $.extend( cfg, {
			useRawProperties: true,
			$overlay: cfg.$overlay
		} ) );
	};

	OO.inheritClass( workflows.ui.trigger.PageRelated, workflows.ui.trigger.Trigger );
	OO.mixinClass( workflows.ui.trigger.PageRelated, workflows.ui.trigger.mixin.WorkflowSelector );

	workflows.ui.trigger.PageRelated.prototype.getFields = function () {
		this.initWorkflowPicker();

		return workflows.ui.trigger.PageRelated.parent.prototype.getFields.call( this ).concat( [
			this.pickerLayout
		] );
	};

	workflows.ui.trigger.PageRelated.prototype.getConditionPanelConfig = function () {
		return {
			include: { namespace: true },
			exclude: { editType: true }
		};
	};

	workflows.ui.trigger.PageRelated.prototype.getValue = function () {
		const dfd = $.Deferred();

		this.getValidity().done( () => {
			if ( this.initializer ) {
				this.initializer.form.validateForm().done( () => {
					this.initializer.submit();
					dfd.resolve( this.generateData() );
				} ).fail( () => {
					dfd.reject();
				} );
			} else {
				dfd.resolve( this.generateData() );
			}
		} ).fail( () => {
			dfd.reject();
		} );

		return dfd.promise();
	};

	workflows.ui.trigger.PageRelated.prototype.getValidity = function () {
		const dfd = $.Deferred();

		this.name.getValidity().done( () => {
			if ( !this.definition || !this.repository ) {
				this.picker.setValidityFlag( false );
				dfd.reject();
			} else {
				dfd.resolve();
			}
		} ).fail( () => {
			dfd.reject();
		} );

		return dfd.promise();
	};

	workflows.ui.trigger.PageRelated.prototype.storeFormValue = function ( value ) {
		this.value.initData = value;
	};

	workflows.ui.trigger.PageRelated.prototype.generateData = function () {
		workflows.ui.trigger.PageRelated.parent.prototype.generateData.call( this );
		this.value.definition = this.definition;
		this.value.repository = this.repository;

		return this.value;
	};
}( mediaWiki, jQuery ) );

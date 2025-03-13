( function ( mw ) {
	workflows.object.form.GroupThresholdValueElement = function () {};

	OO.inheritClass( workflows.object.form.GroupThresholdValueElement, mw.ext.forms.formElement.InputFormElement );

	workflows.object.form.GroupThresholdValueElement.prototype.getElementConfig = function () {
		const config = workflows.object.form.GroupThresholdValueElement.parent.prototype.getElementConfigInternal.call( this );
		return this.returnConfig( config );
	};

	workflows.object.form.GroupThresholdValueElement.prototype.getType = function () {
		return 'wf_threshold_value';
	};

	workflows.object.form.GroupThresholdValueElement.prototype.getWidgets = function () {
		return {
			view: OO.ui.LabelWidget,
			edit: workflows.ui.widget.GroupThresholdValue
		};
	};

	mw.ext.forms.registry.Type.register( 'wf_threshold_value', new workflows.object.form.GroupThresholdValueElement() );

}( mediaWiki ) );

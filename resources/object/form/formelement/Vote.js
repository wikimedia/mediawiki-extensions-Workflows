( function ( mw, $ ) {
	mw.ext.forms.formElement.Vote = function() {};

	OO.inheritClass( mw.ext.forms.formElement.Vote, mw.ext.forms.formElement.InputFormElement );

	mw.ext.forms.formElement.Vote.prototype.getElementConfig = function() {
		var config = mw.ext.forms.formElement.Vote.parent.prototype.getElementConfigInternal.call( this );
		return this.returnConfig( config );
	};

	mw.ext.forms.formElement.Vote.prototype.getType = function() {
		return "vote_widget";
	};

	mw.ext.forms.formElement.Vote.prototype.getWidgets = function() {
		return {
			view: workflows.ui.widget.Vote,
			edit: workflows.ui.widget.Vote
		};
	};

	mw.ext.forms.registry.Type.register( "vote_widget", new mw.ext.forms.formElement.Vote() );

} )( mediaWiki, jQuery );

( function ( mw, $ ) {
	workflows.object.form.VoteElement = function() {};

	OO.inheritClass( workflows.object.form.VoteElement, mw.ext.forms.formElement.InputFormElement );

	workflows.object.form.VoteElement.prototype.getElementConfig = function() {
		var config = workflows.object.form.VoteElement.parent.prototype.getElementConfigInternal.call( this );
		return this.returnConfig( config );
	};

	workflows.object.form.VoteElement.prototype.getType = function() {
		return "wf_vote";
	};

	workflows.object.form.VoteElement.prototype.getWidgets = function() {
		return {
			view: workflows.ui.widget.Vote,
			edit: workflows.ui.widget.Vote
		};
	};

	mw.ext.forms.registry.Type.register( "vote_widget", new workflows.object.form.VoteElement() );
	mw.ext.forms.registry.Type.register( "wf_vote", new workflows.object.form.VoteElement() );

} )( mediaWiki, jQuery );

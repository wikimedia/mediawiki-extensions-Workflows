( function ( mw, $ ) {
	workflows.object.Element = function( cfg, workflow ) {
		this.id = cfg.id;
		this.name = cfg.name;
		this.incoming = cfg.incoming;
		this.outgoing = cfg.outgoing;
		this.elementName = cfg.elementName;

		this.workflow = workflow;
	};

	OO.initClass( workflows.object.Element );

	workflows.object.Element.prototype.getId = function() {
		return this.id;
	};

	workflows.object.Element.prototype.getName = function() {
		return this.name;
	};

	workflows.object.Element.prototype.getIncoming = function() {
		return this.incoming;
	};

	workflows.object.Element.prototype.getOutgoing = function() {
		return this.outgoing;
	};

	workflows.object.Element.prototype.getElementName = function() {
		return this.elementName;
	};

	workflows.object.Element.prototype.getWorkflow = function() {
		return this.workflow;
	};
} )( mediaWiki, jQuery );

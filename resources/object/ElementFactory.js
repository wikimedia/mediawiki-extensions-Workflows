( function () {
	workflows.object.ElementFactory = function ( elementData, workflow ) {
		this.elementData = elementData;
		this.workflow = workflow;
	};

	OO.initClass( workflows.object.ElementFactory );

	workflows.object.ElementFactory.make = function ( elementData, workflow ) {
		const factory = new workflows.object.ElementFactory( elementData, workflow );
		return factory.makeElement();
	};

	workflows.object.ElementFactory.prototype.makeElement = function () {
		const elementData = this.elementData;
		if ( elementData.elementName.toLowerCase().endsWith( 'task' ) ) {
			if ( elementData.isUserInteractive ) {
				return new workflows.object.UserInteractiveActivity(
					this.getElementData( [
						'userInteractionModule', 'properties', 'status',
						'isInitializer', 'targetUsers', 'description', 'history',
						'displayData', 'rawProperties'
					] ), this.workflow
				);
			} else if ( elementData.isDescribed ) {
				return new workflows.object.DescribedActivity(
					this.getElementData( [
						'properties', 'status', 'description', 'history', 'displayData', 'rawProperties'
					] ), this.workflow
				);
			} else {
				return new workflows.object.Activity(
					this.getElementData( [ 'properties', 'status', 'rawProperties' ] ), this.workflow
				);
			}
		}

		return new workflows.object.Element( this.getElementData(), this.workflow );
	};

	workflows.object.ElementFactory.prototype.getElementData = function ( props ) {
		props = props || [];
		props = props.concat( [ 'id', 'name', 'incoming', 'outgoing', 'elementName', 'data' ] );

		const data = {};
		for ( let i = 0; i < props.length; i++ ) {
			if ( !this.elementData.hasOwnProperty( props[ i ] ) ) {
				continue;
			}
			data[ props[ i ] ] = this.elementData[ props[ i ] ];
		}

		return data;
	};

}() );

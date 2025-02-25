workflows.ui.widget.GroupThresholdValue = function( config ) {
	config = config || {};
	config.required = true;
	workflows.ui.widget.GroupThresholdValue.parent.call( this, config );
	this.timer = null;
	this.group = null;
	this.type = null;
	this.$element.addClass( 'group-threshold-value-widget' );

	this.addHintLayout();
	this.connect( this, {
		change: 'onChange'
	} );
};

OO.inheritClass( workflows.ui.widget.GroupThresholdValue, OO.ui.NumberInputWidget );

workflows.ui.widget.GroupThresholdValue.prototype.setType = function( type ) {
	if ( type === 'user' ) {
		this.setRange( 1, 1000 );
		this.setStep( 1 );
	}
	if ( type === 'percent' ) {
		this.setRange( 1, 100 );
		if ( this.value < 10 ) {
			this.setValue( 10 );
		}
		this.setStep( 5 );
	}
	this.type = type;
	this.updateGroupHint();
};

workflows.ui.widget.GroupThresholdValue.prototype.addHintLayout = function() {
	this.hint = new OO.ui.MessageWidget( {
		type: 'success',
		inline: true
	} );
	this.$element.append( this.hint.$element );
	this.hint.$element.css( { 'margin-top': '10px' } ).hide();
};

workflows.ui.widget.GroupThresholdValue.prototype.onChange = function() {
	this.getValidity().done( function() {
		this.updateGroupHint();
	}.bind( this ) );
};

workflows.ui.widget.GroupThresholdValue.prototype.setGroupName = function( group ) {
	this.group = group;
	this.updateGroupHint();
};

workflows.ui.widget.GroupThresholdValue.prototype.updateHintLabel = function( data ) {
	if ( !data.unit || !data.value ) {
		return;
	}
	var label = '', type = 'notice';
	if ( data.unit === 'user' ) {
		if ( data.value > data.userCount ) {
			type = 'error';
		}
		label = mw.message( 'workflows-ui-group-threshold-hint-user', data.userCount ).text();
	}
	if ( data.unit === 'percent' ) {
		var absNumber = Math.floor( data.userCount * ( data.value / 100 ) );
		if ( absNumber === 0 ) {
			absNumber = 1;
		}
		if ( data.userCount === 0 ) {
			type = 'error';
		}
		if ( absNumber > 50 ) {
			type = 'warning';
		}
		label = mw.message( 'workflows-ui-group-threshold-hint-percent', data.userCount, absNumber ).text();
	}


	this.hint.$element.show();
	this.hint.setType( type );
	if ( this.hint.getLabel() === label ) {
		return;
	}
	this.hint.setLabel( label );
	this.emit( 'layoutChange' )
};

workflows.ui.widget.GroupThresholdValue.prototype.getValue = function() {
	var value = workflows.ui.widget.GroupThresholdValue.parent.prototype.getValue.call( this );
	return parseInt( value );
};

workflows.ui.widget.GroupThresholdValue.prototype.removeHint = function() {
	this.hint.$element.hide();
	this.emit( 'layoutChange' );
};

workflows.ui.widget.GroupThresholdValue.prototype.updateGroupHint = function() {
	if ( !this.group ) {
		this.hint.$element.hide();
		return;
	}
	clearTimeout( this.timer );

	this.timer = setTimeout( function() {
		mws.commonwebapis.group.getByGroupName( this.group ).done( function( data ) {
			if ( data.length === 0 ) {
				this.removeHint();
				return;
			}
			var userCount = data.usercount || null;
			if ( userCount === null ) {
				this.removeHint();
				return;
			}
			this.updateHintLabel( {
				unit: this.type,
				value: this.getValue(),
				userCount: userCount
			} );
		}.bind( this ) ).fail( function() {
			this.removeHint();
		}.bind( this ) );
	}.bind( this ), 1000 );
};


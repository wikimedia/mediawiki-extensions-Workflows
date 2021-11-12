( function( mw, $ ) {
	$( function() {
		// 1. Normal way
		var form = new mw.ext.forms.standalone.Form( {
			definition: {
				items: [
					{
						type: 'text',
						name: 'myTest',
						label: 'Text'
					}
				]
			}
		} );
		form.connect( this,  {
			dataSubmitted: function( data ) {
				console.log( data );
			}
		} );
		// Render must be called after events handlers are registered
		form.render();
		$( '#content' ).append( form.$element );


		// 2 With custom submit button and validation and title
		form = new mw.ext.forms.standalone.Form( {
			definition: {
				buttons: [],
				title: 'My form',
				showTitle: true,
				items: [
					{
						type: 'text',
						name: 'myTest',
						label: 'Text',
						required: true
					}, {
						type: 'button',
						name: 'submit',
						widget_label: 'Custom submit',
						flags: [ 'progressive', 'primary' ],
						listeners: {
							click: function() {
								this.submitForm();
							}
						},
						style: "margin-top: 10px;"
					}
				]
			}
		} );
		form.connect( this,  {
			dataSubmitted: function( data ) {
				console.log( data );
			}
		} );
		form.render();
		$( '#content' ).append( form.$element );

		// 3 custom class
		var myForm = function() {
			myForm.parent.call( this );
		};

		OO.inheritClass( myForm, mw.ext.forms.standalone.Form );

		myForm.prototype.makeItems = function() {
			return [
				{
					type: 'number',
					name: 'myNumber',
					label: 'Some number'
				}
			];
		};

		myForm.prototype.onDataSubmitted = function( data, summary ) {
			myForm.parent.prototype.onDataSubmitted.call( this, data, summary );

			console.log( "DATA IS:" );
			console.log( data );
		};

		form = new myForm();
		$( '#content' ).append( form.$element );

	} );
} )( mediaWiki, jQuery );

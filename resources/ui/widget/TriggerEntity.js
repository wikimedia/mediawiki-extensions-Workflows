( function ( mw ) {
	workflows.ui.widget.TriggerEntity = function ( id, data, typeDesc, cfg ) {
		workflows.ui.widget.TriggerEntity.parent.call( this, {} );

		this.id = id;
		this.data = data;
		this.typeDesc = typeDesc;
		this.editMode = cfg.editMode || false;

		let nameLabel = this.data.name_parsed;
		if ( !this.data.active ) {
			nameLabel = mw.message( 'workflows-ui-trigger-item-inactive', this.data.name_parsed ).text();
		}
		this.$element.append( new OO.ui.LabelWidget( {
			label: nameLabel,
			classes: [ 'trigger-name' ]
		} ).$element );

		this.$element.append( new OO.ui.LabelWidget( {
			label: typeDesc.label,
			classes: [ 'trigger-type-label' ],
			title: typeDesc.desc
		} ).$element );

		if ( this.editMode && this.typeDesc.editor !== null ) {
			const editButton = new OO.ui.ButtonWidget( {
				icon: 'edit',
				framed: false,
				title: mw.message( 'workflows-action-edit-label' ).text(),
				flags: 'destructive'
			} );
			const deleteButton = new OO.ui.ButtonWidget( {
				icon: 'trash',
				framed: false,
				title: mw.message( 'workflows-action-delete-label' ).text()
			} );

			editButton.connect( this, {
				click: function () {
					this.emit( 'edit', this.id, this.data, this.typeDesc );
				}
			} );
			deleteButton.connect( this, {
				click: function () {
					this.emit( 'delete', this.id );
				}
			} );

			this.$element.append(
				new OO.ui.HorizontalLayout( {
					items: [ editButton, deleteButton ],
					classes: [ 'trigger-actions' ]
				} ).$element
			);
		}

		this.$element.addClass( 'workflows-trigger-entity' );
		if ( !this.data.active ) {
			this.$element.addClass( 'trigger-inactive' );
		}
		if ( data.hasOwnProperty( 'active' ) && !data.active ) {
			this.$element.addClass( 'workflows-trigger-inactive' );
		}
		if ( this.editMode ) {
			this.$element.addClass( 'trigger-edit' );
		}
	};

	OO.inheritClass( workflows.ui.widget.TriggerEntity, OO.ui.Widget );

	workflows.ui.widget.TriggerEntity.prototype.getId = function () {
		return this.id;
	};

	workflows.ui.widget.TriggerEntity.prototype.getData = function () {
		return this.data;
	};

	workflows.ui.widget.TriggerEntity.prototype.getTypeDescription = function () {
		return this.typeDesc;
	};

}( mediaWiki ) );

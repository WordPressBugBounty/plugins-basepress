jQuery( 'window' ).ready( function( $ ){

	function restrictionHandler( el ){
		this.mainElement           = el;
		this.selectAll             = el.find( '.basepress-select-restrictions' );
		this.deselectAll           = el.find( '.basepress-deselect-restrictions' );
		this.openPresets           = el.find( '.basepress-edit-restriction-preset' );
		this.presetsEditor         = el.find( '.basepress-restriction-presets-editor' );
		this.closePresets          = el.find( '.restriction-preset-close span.dashicons-no' );
		this.presetsList           = el.find( '.presets-list');
		this.newPresetName         = el.find( 'input[id^="new-preset-name"]' );
		this.saveNewPreset         = el.find( '.save-new-preset' );
		this.ajaxLoader            = el.find( '.preset-ajax-loader' );
		var _this = this;


		this.rolesList = function(){
			return _this.mainElement.find( '.basepress-restriction-role-list input[type="checkbox"]' );
		}

		/**
		 * Select all roles
		 */
		this.selectAll.click( function(e){
			e.preventDefault();
			_this.rolesList().filter('input[type="checkbox"]:enabled').prop( 'checked', true );
		});

		/**
		 * Deselect all roles
		 */
		this.deselectAll.click( function(e){
			e.preventDefault();
			_this.rolesList().filter('input[type="checkbox"]:enabled').prop( 'checked', false );
		});

		/**
		 * Open presets editor
		 */
		this.openPresets.click( function(e){
			e.preventDefault();
			_this.presetsEditor.show();
			_this.get_presets_list();
		});

		/**
		 * Close presets editor
		 */
		this.closePresets.click( function(e){
			e.preventDefault();
			_this.presetsEditor.hide();
		});

		/**
		 * Handle preset actions
		 */
		this.presetsList.on( 'click', '.apply, .update, .delete', function(e){
			e.preventDefault();
			let preset = e.currentTarget.parentElement;
			let slug = $(preset).data('slug');
			let process = $(e.currentTarget).data('action');
			let selection = false;
			if( 'update' == process ){
				selection = _this.rolesList().filter(':checked').map( function(){
					return this.value;
				} ).get().join( ',' );
			}

			_this.ajaxLoader.show();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_restriction_presets_handler',
					nonce: window.basepress_restriction_vars ? window.basepress_restriction_vars.nonce : '',
					process: process,
					data: {
						preset: slug,
						selection: selection
					}
				},
				success: function( response ){
					if( response.process == 'apply' ){
						_this.deselectAll.click();
						_this.rolesList().each( function( index, role ){
							if( response.selection.includes(role.value) ){
								$(role).prop('checked', true);
							}
						} );
						_this.closePresets.click();
					}
					else{
						_this.presetsList.html(response);
					}
				},
				complete: function(  ){
					_this.ajaxLoader.hide();
				}
			});
		});

		/**
		 * Get Presets List
		 */
		this.get_presets_list = function(){

			_this.ajaxLoader.show();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_restriction_presets_handler',
					nonce: window.basepress_restriction_vars ? window.basepress_restriction_vars.nonce : '',
					process: 'get_list',
					data: ''
				},
				success: function( response ){
					_this.presetsList.html(response);
				},
				complete: function(  ){
					_this.ajaxLoader.hide();
				}
			});
		}

		/**
		 * Save new preset
		 */
		this.saveNewPreset.click( function(){
			_this.ajaxLoader.show();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_restriction_presets_handler',
					nonce: window.basepress_restriction_vars ? window.basepress_restriction_vars.nonce : '',
					process: 'save_new_preset',
					data: {
						'name': _this.newPresetName.val(),
						'selection': _this.rolesList().filter(':checked').map( function(){
							return this.value;
						} ).get().join( ',' ) }
				},
				success: function( response ){
					_this.presetsList.html(response);
					_this.newPresetName.val('');
				},
				complete: function(  ){
					_this.ajaxLoader.hide();
				}
			});
		});
	}

	/**
	 * Instantiate all available metaboxes
	 */
	$('.basepress-restriction-roles').each( function(i){
		new restrictionHandler( $(this) );
	});
} );
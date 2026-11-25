jQuery( 'window' ).ready( function( $ ){

	var modal_loaded = false;
	var tab = $( '.basepress-sc-tab.active' ).data( 'tab' );
	var artices_refresh = true;

	/**
	 * GENERAL FUNCTIONS
	 */

	//Open the shortcode modal
	$( '#basepress-add-shortcodes' ).click( function(e){
		if( ! modal_loaded ){
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_shortcode_load_modal',
				},
				success: function( response ){
					$('<link>').attr('rel', 'stylesheet')
						.attr('type', 'text/css')
						.attr('href', response.styleCss)
						.appendTo('head');

					$( '#basepress-shortcodes-wrap').html( response.modal );
					modal_loaded = true;
				}
			});
		}
		$( '#basepress-shortcodes-wrap').show();
	});


	//Close modal
	$( '#basepress-shortcodes-wrap' ).on( 'click','#basepress-modal-close', function(e){
		$( '#basepress-shortcodes-wrap').hide();
		$( '#basepress-shortcodes-wrap form' )[0].reset();
		$( '#basepress-sc-results' ).html( '' );
	} );


	//Toggle tabs
	$( '#basepress-shortcodes-wrap' ).on( 'click','.basepress-sc-tab', function(e){
		tab = $(this).data('tab');
		$( '.basepress-sc-tab, .basepress-sc-tab-content').removeClass( 'active' );
		$( this ).addClass( 'active' );
		$( '#basepress-sc-' + tab ).addClass( 'active' );
	} );

	//Select shortcode field text on focus
	$( '#basepress-sc-articles-shortcode, #basepress-sc-notices-shortcode, #basepress-sc-searchbar-shortcode' ).click( function(){
		$(this).select();
	});

	/**
	 * ARTICLES
	 */

	//Load shortcode sections on kb change
	$( '#basepress-shortcodes-wrap' ).on( 'change', '#basepress-sc-articles-kbs', function(e){
		e.stopPropagation();
		load_sections( this )
	} );

	$( '#basepress-sc-articles-kbs' ).change( function(e){
		e.stopPropagation();
		load_sections( this )
	} );


	//Load the section for the current KB
	function load_sections( _this ){
		let kb = jQuery( _this ).val();
		let sections = $( '#basepress-sc-articles-sections' );
		if( ! kb ){
			artices_refresh = true;
			sections.find("option").not(':first').remove();
			sections.prop("selectedIndex", 0).change();
			sections.prop('disabled', 'disabled');
			$( '#basepress-shortcodes-wrap #basepress-sc-articles-form' ).trigger( 'change' );
			return;
		}

		artices_refresh = false;
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'basepress_shortcode_modal_sections',
				kb: kb
			},
			success: function( response ){
				sections.find("option").not(':first').remove();
				sections.prop("selectedIndex", 0).change();
				sections.append( response );
				sections.prop( 'disabled', false );
				artices_refresh = true;
				render_preview();
				$( '#basepress-shortcodes-wrap #basepress-sc-articles-form' ).trigger( 'change' );
			}
		});
	}


	//Generate shortcode on form change
	$( '#basepress-shortcodes-wrap' ).on( 'change', '#basepress-sc-articles-form', function(){
		if( ! artices_refresh ) return;
		let kb = $( '#basepress-sc-articles-kbs' ).val();
		let section = $( '#basepress-sc-articles-sections' ).val();
		let orderby = $( '#basepress-sc-articles-orderby' ).val();
		let order = $( '#basepress-sc-articles-order' ).val();
		let count = $( '#basepress-sc-articles-count' ).val();
		let nonce = $( '#sc-nonce' ).val();
		let shortcode;
		
		shortcode = '[basepress-articles';

		if( kb ){
			shortcode += ' kb="' + kb + '"';
		}
		
		if( section ){
			shortcode += ' section="' + section +'"';
		}
		
		shortcode += ' orderby="' + orderby +'"';
		shortcode += ' order="' + order +'"';
		shortcode += ' count="' + count +'"';
		shortcode += ']';
		$( '#basepress-sc-articles-shortcode' ).val( shortcode );
		render_preview();
	});


	//Preview Articles
	function render_preview(){
		let postID = $( '#basepress-sc-articles-content' ).data( 'post' );
		let kb = $( '#basepress-sc-articles-kbs' ).val();
		let section = $( '#basepress-sc-articles-sections' ).val();
		let orderby = $( '#basepress-sc-articles-orderby' ).val();
		let order = $( '#basepress-sc-articles-order' ).val();
		let count = $( '#basepress-sc-articles-count' ).val();
		let nonce = $( '#sc-nonce' ).val();

		$( this ).attr( "disabled", "disabled" );
		$( '#basepress-sc-articles-form .spinner' ).css( 'visibility', 'visible' );

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'basepress_shortcode_modal_testresults',
				post: postID,
				kb: kb,
				section: section,
				orderby: orderby,
				order: order,
				count: count,
				nonce: nonce
			},
			success: function( response ){
				$( '#basepress-sc-articles-results' ).html( response );
				$( '#basepress-sc-articles-form .spinner' ).css( 'visibility', 'hidden' );
			}
		} );
	}

	//Add shortcode to content
	$( '#basepress-shortcodes-wrap' ).on( 'click', '#basepress-sc-articles-add', function(){
		if( !tinyMCE.activeEditor ) return;
		let shortcode = $( '#basepress-sc-articles-shortcode' ).val();
		tinymce.activeEditor.insertContent( shortcode );
		$( '#basepress-modal-close' ).click();
	} );


	/**
	 * NOTICES
	 */

	//Generate the shortcode according to selection
	$( '#basepress-shortcodes-wrap' ).on( 'click', '.bpress-notice', function(){
		let noticeStyle = $(this).data( 'style' );
		let shortcode = '[basepress-notice style="' + noticeStyle + '"][/basepress-notice]';
		$( '#basepress-shortcodes-wrap .bpress-notice' ).removeClass( 'selected' );
		$(this).addClass( 'selected' );
		$('#basepress-sc-notices-shortcode').val( shortcode );
	} );

	//Add shortcode to content
	$( '#basepress-shortcodes-wrap' ).on( 'click', '#basepress-sc-notices-add', function(){
		if( !tinyMCE.activeEditor ) return;
		selected = tinyMCE.activeEditor.selection.getContent();
		let shortcode = $('#basepress-sc-notices-shortcode').val();

		if( selected ){
			//If text is selected when button is clicked
			//Wrap shortcode around it.
			content =  shortcode.replace( '[/basepress-notice]', selected + '[/basepress-notice]' );
		}else{
			content =  shortcode;
		}

		tinymce.execCommand('mceInsertContent', false, content);
		$( '#basepress-modal-close' ).click();
	} );


	/**
	 * SEARCH BAR
	 */
	//Load shortcode sections on form change
	$( '#basepress-shortcodes-wrap' ).on( 'change', '#basepress-sc-searchbar-form', function(e){
		e.stopPropagation();
		let kb = $( '#basepress-sc-searchbar-kbs' ).val();
		let width = $( '#basepress-sc-searchbar-width' ).val();

		shortcode = '[basepress-search';
		if( kb ){
			shortcode += ' kb="' + kb + '"';
		}
		if( width ){
			shortcode += ' width="' + width + '"';
		}
		shortcode += ']';
		$( '#basepress-sc-searchbar-shortcode' ).val( shortcode );
	} );

	//Add shortcode to content
	$( '#basepress-shortcodes-wrap' ).on( 'click', '#basepress-sc-searchbar-add', function(){
		if( !tinyMCE.activeEditor ) return;
		let shortcode = $( '#basepress-sc-searchbar-shortcode' ).val();
		tinymce.activeEditor.insertContent( shortcode );
		$( '#basepress-modal-close' ).click();
	} );

});
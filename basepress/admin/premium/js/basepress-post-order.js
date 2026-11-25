jQuery( 'window' ).ready( function(){
	
	
	/*
	 *	Load Section options on product selection
	 */
	jQuery( '#product-select' ).change( function(){
		jQuery( '#ajax-loader' ).show();
		
		var product = jQuery( this ).val();
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
					action: 'basepress_get_sections_order_page',
					product: product
				},
			success: function( response ){
				var sections = jQuery( '#section-select' );
				sections.replaceWith( response );
			},
			error: function( jqXHR, textStatus, errorThrown ){
				console.log( errorThrown );
			},
			complete: function(){
				//Remove old Posts
				jQuery( '#posts-order-table ul' ).html( '' );
				//Reset the section dropdown 
				jQuery( '#section-select' ).val( 0 );
				jQuery( '#ajax-loader' ).hide();
			}
		});
	});
	
	/*
	 * If there is a product already selected load the sections
	 * This is true if we are in single product mode
	 */
	if( jQuery( '#product-select' ).find(":selected").val() != 0 ){
		jQuery( '#product-select' ).change();
	}


	/*
	 *	Load the posts on section selection
	 */
	jQuery( '#basepress-post-order' ).on( 'change','.section-select', function(){
		jQuery( '#ajax-loader' ).show();
		
		var product = jQuery( '#product-select' ).val();
		var section = jQuery( this ).val();
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
					action: 'basepress_get_posts_order_page',
					product: product,
					section: section
				},
			success: function( response ){
				jQuery( '#posts-order-table ul' ).html( response );
				
			},
			error: function( jqXHR, textStatus, errorThrown ){
				console.log( errorThrown );
			},
			complete: function(){
				jQuery( '#ajax-loader' ).hide();
			}
		});
	});
	
	
	/*
	 *	Save posts order
	 */
	jQuery( '#save-postorder' ).click( function(){
		var elements = jQuery( '#posts-order-table li' );
		if( elements.length === 0 ) return;

		var proceed = confirm( window.basepress_order_vars.confirm_save_txt );
		if( ! proceed ) return;

		var order = [];
		
		elements.each( function( index, element ){
			order[ index ] = jQuery( element ).data( 'post' );
		});
		
		jQuery( '#ajax-loader' ).show();
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data:{
				action:	'basepress_update_posts_order',
				order: order,
				nonce: window.basepress_order_vars.nonce,
			},

			success: function( response ){
				if( response.error ){
					console.log( response.data );
				}
			},

			error: function(  jqXHR, textStatus, errorThrown){
				console.log( errorThrown );
			},
			
			complete: function(){
				jQuery( '#ajax-loader' ).hide();
			}
		});
	});

	/*
	 *	Reset posts order
	 */
	jQuery( '#reset-postorder' ).click( function(){
		var elements = jQuery( '#posts-order-table li' );
		if( elements.length === 0 ) return;

		var proceed = confirm( window.basepress_order_vars.confirm_reset_txt );

		if( ! proceed ) return;

		var posts = [];

		elements.each( function( index, element ){
			posts[ index ] = jQuery( element ).data( 'post' );
		});

		posts.sort();

		jQuery( '#ajax-loader' ).show();

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data:{
				action:	'basepress_reset_posts_order',
				posts: posts,
				nonce: window.basepress_order_vars.nonce,
			},


			success: function( response ){
				if( response.error ){
					console.log( response.data );
				}
			},


			error: function(  jqXHR, textStatus, errorThrown){
				console.log( errorThrown );
			},

			complete: function(){
				jQuery( '#ajax-loader' ).hide();
				jQuery( '#section-select' ).change();
			}
		});
	});
	
	/*
	 *	Make posts sortable
	 */
	jQuery( '#posts-order-table ul' ).sortable({
		axis: 'y',
		delay: 150
	});
});
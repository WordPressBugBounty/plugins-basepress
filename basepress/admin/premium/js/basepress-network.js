jQuery( 'document' ).ready( function(){

	jQuery( '#delete_data_network' ).click( function( event ){
		event.preventDefault();
		var sites = jQuery( '#sites' ).val();
		var nonce = jQuery( '#basepress_delete_sites' ).val();
		deleteData( 'basepress_network_delete', sites, nonce );
	});
	
	jQuery( '#delete_data_site' ).click( function( event ){
		event.preventDefault();
		var sites = [jQuery( this ).data( 'site' )];
		var nonce = jQuery( '#basepress_delete_sites' ).val();
		deleteData( 'basepress_network_delete', sites, nonce );
	});
	
	function deleteData( action, sites, nonce){
		jQuery( '#ajax-loader' ).show();
		
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data:{
					action:	action,
					sites: sites,
					nonce: nonce
				},
				
				
				success: function( response ){
					if( response.error ){
						console.log( response.data );
					}
					else{
						console.log( response );
					}
				},
				
				
				error: function(  jqXHR, textStatus, errorThrown){
					console.log( errorThrown );
				},
				
				complete: function(){
					jQuery( '#ajax-loader' ).hide();
				}
		});
	}
}); //jQuery Closure
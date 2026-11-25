jQuery( 'document' ).ready( function($){

	/**
	 * Delete feedback
	 */
	$( '#basepress-feedbacks-container' ).on( 'click', '.basepress-delete-feedback',function (){
		if( confirm( feedbacktext.confirmdelete ) ){
			var ID = $(this).data('feedback');
			ajaxCall( 'delete', ID );
		}
	});

	/**
	 * Archive feedback
	 */
	$( '#basepress-feedbacks-container' ).on( 'click', '.basepress-archive-feedback',function (){
		var ID = $(this).data('feedback');
		ajaxCall( 'archive', ID );
	});

	/**
	 * Restore feedback
	 */
	$( '#basepress-feedbacks-container' ).on( 'click', '.basepress-restore-feedback',function (){
		var ID = $(this).data('feedback');
		ajaxCall( 'restore', ID );
	});

	/**
	 * Handle Ajax calls
	 * @param action
	 * @param ID
	 */
	function ajaxCall( action, ID ){
		var container = $( '#basepress-feedbacks-container' );

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'basepress_manage_feedback',
				feedbackID: ID,
				feedbackAction: action,
				status: container.data( 'status' ),
				paged: container.data( 'paged' ),
				s: container.data( 'search' ),
				nonce: container.data( 'nonce' ),
			},
			success: function( response ){
				response = JSON.parse( response );
				if( response.data.hasOwnProperty('feedbacks') ){
					container.html( response.data.feedbacks );
					container.data( 'search', response.data.vars.search );
					$('.basepress-feedback-status .open-count').html( response.data.counts.publish );
					$('.basepress-feedback-status .trash-count').html( response.data.counts.trash );
				}
				if( response.message ){
					alert( response.message );
				}
			},
			error: function( jqXHR, textStatus, errorThrown ){
				console.log( errorThrown );
			},
			complete: function(){

			}
		});
	}
});
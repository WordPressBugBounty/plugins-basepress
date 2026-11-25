jQuery( 'window' ).ready( function($){

	function insight(){
		this.products = $( '.product-selection' );
		var self = this;

		this.getSelectedProducts = function(){
			var selectedProducts = [];
			self.products.filter( '.selected' ).each(
				function( index, el ){
					selectedProducts.push( $( el ).data( 'product' ) );
				}
			)
			return selectedProducts.join();
		}



		this.getInsights = function(){

			$( '.insight-data-container, #insight-visits-container, #insight-search-container' ).addClass( 'updating' );

			$.ajax( {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_get_insight',
					from: $( "#insight-from-date" ).val(),
					to: $( "#insight-to-date" ).val(),
					products: self.getSelectedProducts(),
					nonce: insightVars.nonce,
				},
				success: function( response ){

					$( '#insight-most-viewed .insight-data-container' ).html( response.mostviewed );
					$( '#insight-most-viewed-time-range .insight-data-container' ).html( response.mostviewedtimerange );
					$( '#insight-best-scores .insight-data-container' ).html( response.bestscores );
					$( '#insight-low-scores .insight-data-container' ).html( response.lowscores );
					$( '#insight-latest-published .insight-data-container' ).html( response.latestpublished );
					self.viewsChart.data.labels = response.visits.labels;
					self.viewsChart.data.datasets = response.visits.datasets;
					self.viewsChart.update();
					$( '#insight-top-searches .insight-data-container' ).html( response.topsearches );
					$( '#insight-top-searches-time-range .insight-data-container' ).html( response.topsearchestimerange );
					$( '#insight-failed-searches .insight-data-container' ).html( response.failedsearches );
					self.searchChart.data.labels = response.searches.labels;
					self.searchChart.data.datasets = response.searches.datasets;
					self.searchChart.update();

					//console.log( response );
				},
				error: function( jqXHR, textStatus, errorThrown ){
					console.log( errorThrown );
				},
				complete: function(){
					$( '.insight-data-container, #insight-visits-container, #insight-search-container' ).removeClass( 'updating' );
				}
			} );
		}


		/**
		 * Update data on Apply dates
		 */
		$( '#insight-apply-date' ).click( function(){
			self.getInsights();
		});


		/**
		 * Toggle selected products
		 */
		this.products.click( function(){
			//If clicked on a product select the product
			if( $(this).data( 'product' ) ){
				self.products.first().removeClass('selected');
				$( this ).toggleClass( 'selected');
			}
			else{
				if( $( this ).hasClass( 'selected' ) ){
					self.products.addClass('selected');
					self.products.first().removeClass('selected');
				}
				else{
					self.products.removeClass('selected');
					self.products.first().addClass('selected');
				}

			}

			if( 0 == self.products.filter( '.selected' ).length ){
				self.products.first().addClass('selected');
			}

		});

		/**
		 * Change date range with presets
		 */
		$( '#insight-week-date, #insight-month-date, #insight-year-date' ).click(
			function(){
				var from = $( this ).data( 'from' );
				var to = $( this ).data( 'to' );
				$( "#insight-from-date" ).val( from );
				$( "#insight-to-date" ).val( to );
			}
		);


		/**
		 * Toggle Views and searches
		 */
		$( '#insight-show-views' ).click( function(){
			$( '#insight-show-searches, #searches-cards' ).removeClass( 'selected' );
			$(this).addClass('selected');
			$('#views-score-cards').addClass('selected');
		});

		$( '#insight-show-searches' ).click( function(){
			$( '#insight-show-views, #views-score-cards' ).removeClass( 'selected' );
			$(this).addClass('selected');
			$('#searches-cards').addClass('selected');
		});

		/**
		 * Toggle Manage Logs
		 */
		$( '#insight-manage-logs' ).click( function(){
			$( '#insight-manage-logs-container' ).toggle();
		});

		$( '#insight-delete-logs' ).click( function( e ){
			e.preventDefault();
			var olderThan = $( "#insight-delete-to-date" ).val();

			if( confirm( insightVars.confirmDelete + ' ' + olderThan + '?' ) ){
				$( '#insight-manage-logs-container .spinner' ).css( 'visibility', 'visible' );

				$.ajax( {
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'basepress_delete_logs',
						olderthan: olderThan,
						nonce: insightVars.nonce,
					},
					success: function( response ){
						console.log( response );
					},
					error: function( jqXHR, textStatus, errorThrown ){
						console.log( errorThrown );
					},
					complete: function(){
						$( '#insight-manage-logs-container .spinner' ).css( 'visibility', 'hidden' );;
					}
				} );
			}
		});


		this.viewsChart = new Chart( $('#insight-visits-chart #visits-chart'), {
			type: 'line',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						scaleLabel: {
							display: true,
							labelString: insightVars.views,
							fontStyle: 'bold'
						},
						gridLines: {
							display:true,
							borderDash: [2, 2],
						},
						ticks: {
							beginAtZero:true,
							min: 0,
							autoSkip: true,
							userCallback: function(label, index, labels) {
								if (Math.floor(label) === label) {
									return label;
								}
							},
						}
					}],
					xAxes: [{
						scaleLabel: {
							display: true,
							labelString: insightVars.time,
							fontStyle: 'bold'
						},
						gridLines: {
							display:true,
							borderDash: [2, 2],
							drawOnChartArea: true
						},
					}],
				},
				elements: {
					line: {
						borderWidth: 1
					},
					point:{
						radius: 0,
						hitRadius: 5,
					}
				},
				legend: {
					position: 'right',
					labels: {
						boxWidth: 12,
					}
				},
			}
		});

		this.searchChart = new Chart( $('#insight-search-chart #search-chart'), {
			type: 'line',
			data: {
				labels: [],
				datasets: []
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						scaleLabel: {
							display: true,
							labelString: insightVars.searches,
							fontStyle: 'bold'
						},
						gridLines: {
							display:true,
							borderDash: [2, 2],
						},
						ticks: {
							beginAtZero:true,
							min: 0,
							autoSkip: true,
							userCallback: function(label, index, labels) {
								if (Math.floor(label) === label) {
									return label;
								}
							},
						}
					}],
					xAxes: [{
						scaleLabel: {
							display: true,
							labelString: insightVars.time,
							fontStyle: 'bold'
						},
						gridLines: {
							display:true,
							borderDash: [2, 2],
							drawOnChartArea: true
						},
					}],
				},
				elements: {
					line: {
						borderWidth: 1
					},
					point:{
						radius: 0,
						hitRadius: 5,
					}
				},
				legend: {
					position: 'right',
					labels: {
						boxWidth: 12,
					}
				},
			}
		});


	}//Insights Object


	/**
	 * Date Picker
	 */
	$( function() {
		var dateFormat = "mm/dd/yy",
			from = $( "#insight-from-date" )
				.datepicker({
					defaultDate: "+1w",
					changeMonth: true,
					changeYear: true,
					dateFormat: 'yy-mm-dd',
					maxDate: 0,
					nextText: '',
					prevText: '',
				})
				.on( "change", function() {
					to.datepicker( "option", "minDate", getDate( this ) );
				}),
			to = $( "#insight-to-date, #insight-delete-to-date" )
				.datepicker({
				defaultDate: "+1w",
				changeMonth: true,
				changeYear: true,
				dateFormat: 'yy-mm-dd',
				maxDate: 0,
				nextText: '',
				prevText: '',
			})
			.on( "change", function() {
				from.datepicker( "option", "maxDate", getDate( this ) );
			});
		$.datepicker.formatDate( 'yy-mm-dd' );

		function getDate( element ) {
			var date;
			try {
				date = $.datepicker.parseDate( 'yy-mm-dd', element.value );
			} catch( error ) {
				date = null;
			}
			return date;
		}
	});

	var bpInsight = new insight();
	bpInsight.getInsights();

}); //jQuery Closure
<?php
/**
 * This is the class that adds the post order page on admin
 */

// Exit if called directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Basepress_Insights' ) ){

	class Basepress_Insights{

		public $page_url = 'edit.php?post_type=knowledgebase&page=basepress_insights';

		/**
		 * Store color palettes for graphs
		 * @var array
		 */
		private $palette = array(
			'31,124,151',
			'82,0,68',
			'204,0,62',
			'255,139,67',
			'236,224,112',
			'72,204,140',
			'60,56,102',
			'148,0,62',
			'255,76,70',
			'255,197,79',
			'169,216,113',
			'0,190,173',
		);

		/**
		 * Keeps tracks of the current color index
		 * @var int
		 */
		private $color_palette_index = 0;

		/**
		 * True if we are getting global insights
		 * @var bool
		 */
		private $global = true;

		/**
		 * Stores the product ids
		 * @var bool
		 */
		private $product_ids = false;

		/**
		 * Stores the product objects relative to the product ids
		 * @var array
		 */
		private $products = array();

		/**
		 * Stores whether the search was already logged
		 * @var bool
		 */
		private $has_logged_search = false;

		/**
		 * Stores the search data to log
		 * @var array
		 */
		private $log_search_data = array();

		/**
		 * Stores default time range
		 * @var
		 */
		private $default_time_range;

		/**
		 * Stores how many results to show per insight
		 * @var
		 */
		private $max_results;

		private $is_single_product_mode = false;

		/**
		 * Basepress_Insights constructor.
		 */
		public function __construct(){
			global $basepress_utils;

			$options = $basepress_utils->get_options();
			if( isset( $options['activate_insights'] )){

				$this->default_time_range = isset( $options['insights_default_time_range'] ) ? $options['insights_default_time_range'] : 'week';
				$this->max_results = isset( $options['insights_max_results'] ) ? $options['insights_max_results'] : 10;
				$this->is_single_product_mode = isset( $options['single_product_mode'] );

				add_action( 'admin_menu', array( $this, 'add_insight_page' ) );

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

				add_action( 'wp_ajax_basepress_get_insight', array( $this, 'basepress_get_insight' ) );

				add_action( 'basepress_post_view_counter', array( $this, 'log_post_views' ), 10, 2 );

				add_action( 'basepress_made_search', array( $this, 'log_main_search' ), 10, 2 );

				add_action( 'wp_ajax_basepress_log_ajax_search', array( $this, 'basepress_log_ajax_search' ) );
				add_action( 'wp_ajax_nopriv_basepress_log_ajax_search', array( $this, 'basepress_log_ajax_search' ) );

				add_filter( 'found_posts', array( $this, 'log_searches' ), 10 );

				add_action( 'wp_ajax_basepress_delete_logs', array( $this, 'basepress_delete_logs' ) );
			}

			add_action( 'basepress_after_settings_validate', array( $this, 'maybe_create_tables' ) );
		}


		/**
		 * Enqueues admin scripts for this screen
		 *
		 * @since 1.8.0
		 *
		 * @param $hook Enqueue admin script for post order screen only
		 */
		public function enqueue_admin_scripts( $hook ) {

			//Enqueue admin script for post order screen only.
			if ( 'knowledgebase_page_basepress_insights' == $hook ) {
				wp_register_script( 'basepress-insight-js', BASEPRESS_URI . 'admin/premium/js/insight.js', array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'basepress-insight-js' );
				wp_localize_script( 'basepress-insight-js', 'insightVars', array(
					'confirmDelete' => esc_html__( 'Are you sure you want to delete logs older than', 'basepress' ),
					'time' => esc_html__( 'Time', 'basepress' ),
					'views' => esc_html__( 'Views', 'basepress' ),
					'searches' => esc_html__( 'Searches', 'basepress' ),
					'nonce' => wp_create_nonce( 'bp-insight-nonce' ),
				));
				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_register_script( 'chartjs', BASEPRESS_URI . 'admin/premium/js/vendor/Chart.bundle.min.js', array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'chartjs' );

				wp_enqueue_style( 'basepress-insight', BASEPRESS_URI . 'admin/premium/css/insight.css', array(), BASEPRESS_VER );
			}
		}



		/**
		 * Adds knowledge base insight menu
		 *
		 * @since 1.8.0
		 */
		public function add_insight_page() {
			//Add the sub menu on plugin menu
			add_submenu_page( 'edit.php?post_type=knowledgebase', 'BasePress ' . esc_html__( 'Statistics', 'basepress' ), esc_html__( 'Statistics', 'basepress' ), 'manage_options', 'basepress_insights', array( $this, 'display_screen' ) );
		}


		/**
		 * Renders the page elements
		 *
		 * @since 1.8.0
		 */
		public function display_screen(){
			?>
			<div class="insight-wrap">
				<h1><?php echo 'BasePress ' . esc_html__( 'Statistics', 'basepress' ); ?></h1>

				<div class="insight-tools">

					<!-- Date Picker -->
					<div id="insight-date-rage" class="insight-card">
						<?php
						$date_today = date( 'Y-m-d' );

						$date_from_week = date( 'Y-m-d', strtotime('-6 days'));

						$date_from_month = date( 'Y-m-d', strtotime('-29 days'));
						$date_from_year = date( 'Y-m-d', strtotime('-364 days'));

						switch( $this->default_time_range  ){
							case 'week':
								$default_date_from = $date_from_week;
								break;
							case 'month':
								$default_date_from = $date_from_month;
								break;
							case 'year':
								$default_date_from = $date_from_year;
								break;
						}
						?>
						<h2 class="insight-card-title"><?php esc_html_e( 'Date Range', 'basepress' ); ?></h2>
						<div class="insight-data-container">
							<div id="date-range-presets">
								<button id="insight-week-date" data-from="<?php echo esc_attr( $date_from_week ); ?>" data-to="<?php echo esc_attr( $date_today ); ?>"><?php esc_html_e( 'Week', 'basepress' ); ?></button>
								<button id="insight-month-date" data-from="<?php echo esc_attr( $date_from_month ); ?>" data-to="<?php echo esc_attr( $date_today ); ?>"><?php esc_html_e( 'Month', 'basepress' ); ?></button>
								<button id="insight-year-date" data-from="<?php echo esc_attr( $date_from_year ); ?>" data-to="<?php echo esc_attr( $date_today ); ?>"><?php esc_html_e( 'Year', 'basepress' ); ?></button>
								<button id="insight-apply-date"><?php esc_html_e( 'Apply', 'basepress' ); ?></button>
							</div>
							<table>
								<tbody>
									<tr>
										<td><label><?php esc_html_e( 'From', 'basepress' ); ?></label></td>
										<td><input type="text" id="insight-from-date" name="insight-from-date" value="<?php echo esc_attr( $default_date_from ); ?>"></td>
									</tr>
									<tr>
										<td><label><?php esc_html_e( 'To', 'basepress' ); ?></label></td>
										<td><input type="text" id="insight-to-date" name="insight-to-date" value="<?php echo esc_attr( $date_today ); ?>"></td>
									</tr>
								</tbody>
							</table>
							<!-- Manage Logs -->

							<p id="insight-manage-logs"><?php esc_html_e( 'Manage Logs', 'basepress' ); ?><span class="dashicons dashicons-arrow-down"></span></p>
							<div id="insight-manage-logs-container">
								<h3><?php esc_html_e( 'Delete Logs', 'basepress' ); ?></h3>
								<table>
									<tbody>
									<tr>
										<td><label><?php esc_html_e( 'Older than', 'basepress' ); ?></label></td>
										<td><input type="text" id="insight-delete-to-date" name="insight-from-date" value="<?php echo esc_attr( $default_date_from ); ?>"></td>
									</tr>
									</tbody>
								</table>
								<p><button id="insight-delete-logs"><?php esc_html_e( 'Delete', 'basepress' ); ?></button><span class="spinner"></span></p>
							</div>

						</div>
					</div>

					<!-- Products List -->
					<?php if( ! $this->is_single_product_mode ) : ?>
					<div id="insight-products" class="insight-card">
						<h2 class="insight-card-title"><?php esc_html_e( 'Knowledge Bases', 'basepress' ); ?></h2>
						<div class="insight-data-container">
						<?php
						$this->get_products_list();
						?>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<!-- Cards -->
				<div class="insight-cards">

					<!-- Views - Searches Selector -->
					<div class="row">
						<div class="col col-1">
							<div id="insight-views-scores" class="insight-card">
								<button id="insight-show-views" class="selected"><?php esc_html_e( 'Views and Scores', 'basepress' ); ?></button>
								<button id="insight-show-searches"><?php esc_html_e( 'Searches', 'basepress' ); ?></button>
							</div>
						</div>
					</div>

					<!-- Views and score cards -->
					<div id="views-score-cards" class="selected">
						<div class="row">
							<div class="col col-1">
								<div id="insight-visits-chart" class="insight-card">
									<h2 class="insight-card-title"><?php esc_html_e( 'Visits by time range', 'basepress' ); ?></h2>
									<div id="insight-visits-container">
										<canvas id="visits-chart"></canvas>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col col-2">
								<div id="insight-most-viewed" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Most Visited', 'basepress' ); ?><span class='dashicons dashicons-visibility'></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>

							<div class="col col-2">
								<div id="insight-most-viewed-time-range" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Most visited by time range', 'basepress' ); ?><span class='dashicons dashicons-visibility'></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col col-2">
								<div id="insight-best-scores" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Highest Score', 'basepress' ); ?><span class="dashicons dashicons-thumbs-up"></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>

							<div class="col col-2">
								<div id="insight-low-scores" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Lowest Score', 'basepress' ); ?><span class="dashicons dashicons-thumbs-down"></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Searches cards -->
					<div id="searches-cards">

						<div class="row">
							<div class="col col-1">
								<div id="insight-search-chart" class="insight-card">
									<h2 class="insight-card-title"><?php esc_html_e( 'Searches by time range', 'basepress' ); ?></h2>
									<div id="insight-search-container">
										<canvas id="search-chart"></canvas>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col col-3">
								<div id="insight-top-searches" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Top Searches', 'basepress' ); ?><span class="dashicons dashicons-search"></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>

							<div class="col col-3">
								<div id="insight-top-searches-time-range" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Top Searches by time range', 'basepress' ); ?><span class="dashicons dashicons-search"></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>

							<div class="col col-3">
								<div id="insight-failed-searches" class="insight-card insight-card-scroll">
									<h2 class="insight-card-title"><?php esc_html_e( 'Failed Searches', 'basepress' ); ?><span class="dashicons dashicons-search"></span></h2>
									<div class="insight-data-container"></div>
								</div>
							</div>
						</div>


					</div>
				</div>
			</div>
			<?php
		}


		/**
		 * Verifies capability and nonce for insight AJAX requests.
		 *
		 * @since 2.17.0.4
		 */
		private function verify_insight_request() {
			if ( ! current_user_can( 'manage_options' ) ) {
				status_header( 403 );
				echo wp_json_encode( array( 'error' => esc_html__( 'You do not have permission to perform this action.', 'basepress' ) ) );
				wp_die();
			}

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bp-insight-nonce' ) ) {
				status_header( 403 );
				echo wp_json_encode( array( 'error' => esc_html__( 'Nonce authentication issue.', 'basepress' ) ) );
				wp_die();
			}
		}

		/**
		 * Get insights via Ajax
		 *
		 * @since 1.8.0
		 */
		public function basepress_get_insight(){
			header('Content-type: application/json');
			$this->verify_insight_request();

			$date_from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : date( 'Y-m-d', strtotime('-6 days'));
			$date_to = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : date( 'Y-m-d' );

			$this->global = isset( $_POST['products'] )
			                && 0 == $_POST['products']
			                && ! $this->is_single_product_mode
				              ? true : false;

			$this->get_products();

			if( ! empty( $this->products )){
				$insights = array();

				//Get views and score data
				$visits_over_time = $this->get_visits_over_time( $date_from, $date_to );
				$most_viewed = $this->get_top_articles_by_views();
				$most_viewed_time_range = $this->get_top_articles_by_views_time_range( $date_from, $date_to );
				$best_scores = $this->get_articles_by_score_high();
				$low_scores = $this->get_articles_by_score_low();

				//Get searches data
				$searches_over_time = $this->get_searches_over_time( $date_from, $date_to );
				$top_searches = $this->get_top_searches();
				$top_searches_time_range = $this->get_top_searches_time_range( $date_from, $date_to );
				$failed_searches = $this->get_failed_searches();

				//Add views and scores data to insights array
				$insights['visits'] = $visits_over_time;
				$insights['mostviewed'] = $most_viewed;
				$insights['mostviewedtimerange'] = $most_viewed_time_range;
				$insights['bestscores'] = $best_scores;
				$insights['lowscores'] = $low_scores;

				//Add searches data to insights array
				$insights['searches'] = $searches_over_time;
				$insights['topsearches'] = $top_searches;
				$insights['topsearchestimerange'] = $top_searches_time_range;
				$insights['failedsearches'] = $failed_searches;

				echo wp_json_encode( $insights );
			}

			wp_die();
		}


		/**
		 * Resets color palette index
		 *
		 * @since 1.8.0
		 */
		private function reset_color_palette_index(){
			$this->color_palette_index = 0;
		}

		/**
		 * Gets the color palette index
		 *
		 * @since 1.8.0
		 *
		 * @return int
		 */
		private function get_color_palette_index(){
			$total_colors = count( $this->palette );

			$index = $this->color_palette_index;
			$this->color_palette_index += 1;

			if( $this->color_palette_index >= $total_colors ){
				$this->reset_color_palette_index();
			}
			return $index;
		}

		/**
		 * Get visits data sets for graph
		 *
		 * @since 1.8.0
		 */
		private function get_visits_over_time( $date_from, $date_to ){
			global $wpdb;

			//rest color palette index
			$this->reset_color_palette_index();

			$views_table = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
			$labels = array();
			$product_ids = $this->global ? array( 0 ) : $this->product_ids;

			$period = new DatePeriod(
				new DateTime($date_from),
				new DateInterval('P1D'),
				new DateTime($date_to.'+1day')
			);

			//Generate days labels for the period
			foreach ( $period as $key => $value ) {
				$labels[] = $value->format('M d');
			}

			$results = array(
				'labels' => $labels,
				'datasets'   => array(),
			);

			$data_index = 0;
			foreach( $product_ids as $product_id ){
				$previous_date = strtotime( $date_from ) - 86400;
				$product = 0 == $product_id ? null : $this->products[ $product_id ];

				$query = "SELECT v.time,SUM(v.hits) AS hits, v.product_id";
				$query .= $wpdb->prepare(
					" FROM %i AS v",
					$views_table
				);
				$query .= $wpdb->prepare(
					" WHERE v.time BETWEEN '%s' AND '%s'",
					$date_from,
					$date_to
				);
				$query .= $product_id ? " AND v.product_id = $product_id" : '';
				$query .= " GROUP BY v.time ORDER BY v.time ASC";

				$views = $wpdb->get_results( $query );

				$results['datasets'][ $data_index ]['label'] = is_null( $product ) ? esc_html__( 'Global', 'basepress' ) : $product->name;
				if( $this->is_single_product_mode ){
					$results['datasets'][ $data_index ]['label'] = '';
				}
				$results['datasets'][$data_index]['data'] = array();
				foreach( $views as $hits ){
					$current_date = strtotime( $hits->time );
					$date_difference = round( ( $current_date - $previous_date ) / 86400 );
					$results['datasets'][$data_index]['data'] = array_pad( $results['datasets'][$data_index]['data'], count( $results['datasets'][$data_index]['data'] ) + $date_difference - 1, 0 );
					$results['datasets'][$data_index]['data'][] = (int)$hits->hits;
					$previous_date = $current_date;
				}

				$date_difference = round( ( strtotime( $date_to ) - $previous_date ) / 86400 );
				$results['datasets'][$data_index]['data'] = array_pad( $results['datasets'][$data_index]['data'], count( $results['datasets'][$data_index]['data'] ) + $date_difference, 0 );

				$results['datasets'][$data_index]['borderColor'] = "rgba(0,0,0,0.5)";
				$results['datasets'][$data_index]['backgroundColor'] = "rgba({$this->palette[$this->get_color_palette_index()]},0.7)";
				$data_index += 1;
			}
			return $results;
		}


		/**
		 * Get Top Viewed articles
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_top_articles_by_views(){

			$args = array(
				'post_type' => 'knowledgebase',
				'meta_query' => array(
					'key'     => 'basepress_views',
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'knowledgebase_cat',
						'field'    => 'term_id',
						'terms'    => $this->product_ids,
					)
				),
				'orderby' => 'basepress_views',
				'order'   => 'DESC',
				'posts_per_page' => $this->max_results,
			);

			$articles = get_posts( $args );

			return $this->get_article_rows( $articles, array( 'views' ) );
		}


		/**
		 * Get Top Viewed articles by date range
		 *
		 * @since 1.8.0
		 *
		 * @param $date_from
		 * @param $date_to
		 * @return string
		 */
		private function get_top_articles_by_views_time_range( $date_from, $date_to ){
			global $wpdb;

			$views_table = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
			$product_ids = $this->global ? array() : $this->product_ids;

			$query = "SELECT p.*, SUM(v.hits) AS total_hits FROM $wpdb->posts AS p";
			$query .= $wpdb->prepare(
				" LEFT JOIN %i AS v ON p.ID = v.post_id",
				$views_table
			);
			$query .= $wpdb->prepare(
				" LEFT JOIN %i AS m ON p.ID = m.post_id AND m.meta_key = 'basepress_views'",
				$wpdb->postmeta
			);
			$query .= " WHERE p.post_type = 'knowledgebase'";
			$query .= $wpdb->prepare(
				" AND v.time BETWEEN '%s' AND '%s'",
				$date_from,
				$date_to
			);
			$query .= $product_ids ? $wpdb->prepare( " AND v.product_id IN ( %s )", implode( ',', $product_ids ) ) : '';
			$query .= " GROUP BY p.ID	ORDER BY total_hits DESC";
			$query .= " LIMIT {$this->max_results}";

			$articles = $wpdb->get_results( $query );

			return $this->get_article_rows( $articles, array( 'views-time-range' ) );
		}


		/**
		 * Get best scoring articles
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_articles_by_score_high(){

			$args = array(
				'post_type' => 'knowledgebase',
				'meta_query' => array(
					'relation' => 'AND',
					'score'    => array(
						'key'     => 'basepress_score',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC'
					),
					'votes_count' => array(
						'key' => 'basepress_votes_count',
						'value' => 0,
						'compare' => '>',
						'type'    => 'NUMERIC'
					)
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'knowledgebase_cat',
						'field'    => 'term_id',
						'terms'    => $this->product_ids,
					)
				),
				'orderby' => array(
					'score' => 'DESC',
					'votes_count' => 'DESC',
				),
				'posts_per_page' => $this->max_results,
			);

			$articles = get_posts( $args );

			return $this->get_article_rows( $articles, array( 'score' ) );
		}


		/**
		 * Get lowest scoring articles
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_articles_by_score_low(){

			$args = array(
				'post_type' => 'knowledgebase',
				'meta_query' => array(
					'relation' => 'AND',
					'score' => array(
						'key'     => 'basepress_score',
						'value'   => 0,
						'compare' => '<'
					),
					'votes_count' => array(
						'key' => 'basepress_votes_count',
						'value' => 0,
						'compare' => '>'
					)
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'knowledgebase_cat',
						'field'    => 'term_id',
						'terms'    => $this->product_ids,
					)
				),
				'orderby' => array(
					'score' => 'ASC',
					'votes_count' => 'DESC',
				),
				'posts_per_page' => $this->max_results,
			);


			$articles = get_posts( $args );

			return $this->get_article_rows( $articles, array( 'score' ) );
		}


		/**
		 * Generate Article rows
		 *
		 * @since 1.8.0
		 *
		 * @param $articles
		 * @param array $fields
		 * @return string
		 */
		private function get_article_rows( $articles, $fields = array() ){
			global $basepress_utils;

			ob_start();

			if( ! empty( $articles ) ){
				echo '<ul class="insight-article-list">';

				foreach( $articles as $article ){
					$score = in_array( 'score', $fields ) ? get_post_meta( $article->ID, 'basepress_score', true ) : false;
					$views = in_array( 'views', $fields ) ? get_post_meta( $article->ID, 'basepress_views', true ) : false;
					$votes = get_post_meta( $article->ID, 'basepress_votes', true );

					$terms = get_the_terms( $article->ID, 'knowledgebase_cat' );
					if( ! empty( $terms ) && ! is_wp_error( $terms ) ){
						$section = $terms[0];
						$section_name = esc_html( $section->name );
						$product = $basepress_utils->get_top_term( $section );
						$product_name = esc_html( $product->name );
						$section_link = esc_url( get_term_link( $section ) );
						$product_link = esc_url( get_term_link( $product ) );
					}
					else{
						$product_link = '';
						$product_name = '';
						$section_link = '';
						$section_name = '';
					}
					$article_link = esc_url( get_permalink( $article->ID ) );

					echo '<li>';
					echo '<div>';
					echo "<a href='{$article_link}' target='_blank'>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo esc_html( $article->post_title );
					echo '</a><br>';
					echo "<span class='article-product'><a href='{$product_link}' target='_blank'>{$product_name}</a>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if( ! empty( $product_name ) && ! empty( $section_name ) ){
						echo "<span class='dashicons dashicons-arrow-right-alt2'></span></span>";
					}
					else{
						echo esc_html__( 'Uncategorised', 'basepress' );
					}
					echo "<span class='article-section'><a href='{$section_link}' target='_blank'>{$section_name}</a></span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</div>';

					echo '<div>';
					if( in_array( 'views', $fields ) ){
						echo "<span class='insight-post-views'>{$views}</span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if( in_array( 'views-time-range', $fields ) ){
						echo "<span class='insight-post-views-time-range'>{$article->total_hits}</span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if( in_array( 'score', $fields ) ){
						echo "<div class='insight-post-score'>{$score}</div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo "<div class='insight-post-votes'>";
						echo "<span class='insight-post-like'><span class='dashicons dashicons-yes'></span>{$votes['like']}</span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo "<span class='insight-post-dislike'><span class='dashicons dashicons-no'></span>{$votes['dislike']}</span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						echo "</div>";
					}
					echo '</div>';
					echo '</li>';
				}
				echo '</ul>';
			}
			else{
				echo '<p>' . esc_html__( 'No results to display here!', 'basepress' ) . '</p>';
			}

			return ob_get_clean();
		}


		/**
		 * Get searches data set for graph
		 *
		 * @since 1.8.0
		 *
		 * @param $date_from
		 * @param $date_to
		 * @return array
		 * @throws Exception
		 */
		private function get_searches_over_time( $date_from, $date_to ){
			global $wpdb;
			//rest color palette index
			$this->reset_color_palette_index();

			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$labels = array();
			$product_ids = $this->global ? array( 0 ) : $this->product_ids;

			$period = new DatePeriod(
				new DateTime($date_from),
				new DateInterval('P1D'),
				new DateTime($date_to.'+1day')
			);

			//Generate days labels for the period
			foreach ( $period as $key => $value ) {
				$labels[] = $value->format('M d');
			}

			$results = array(
				'labels' => $labels,
				'datasets'   => array(),
			);

			$data_index = 0;
			foreach( $product_ids as $product_id ){
				$previous_date = strtotime( $date_from ) - 86400;
				$product = 0 == $product_id ? null : $this->products[ $product_id ];

				$query = "SELECT s.time,SUM(s.hits) AS hits, s.product_id";
				$query .= $wpdb->prepare(
					" FROM %i AS s",
					$search_table
				);
				$query .= $wpdb->prepare(
					" WHERE s.time BETWEEN '%s' AND '%s'",
					$date_from,
					$date_to
				);
				$query .= $product_id ? $wpdb->prepare( " AND s.product_id = %s", $product_id ) : '';
				$query .= " GROUP BY s.time ORDER BY s.time ASC";

				$views = $wpdb->get_results( $query );

				$results['datasets'][$data_index]['label'] = is_null( $product ) ? esc_html__( 'Global', 'basepress' ) : $product->name;
				$results['datasets'][$data_index]['data'] = array();
				foreach( $views as $hits ){
					$current_date = strtotime( $hits->time );
					$date_difference = round( ( $current_date - $previous_date ) / 86400 );
					$results['datasets'][$data_index]['data'] = array_pad( $results['datasets'][$data_index]['data'], count( $results['datasets'][$data_index]['data'] ) + $date_difference - 1, 0 );
					$results['datasets'][$data_index]['data'][] = (int)$hits->hits;
					$previous_date = $current_date;
				}

				$date_difference = round( ( strtotime( $date_to ) - $previous_date ) / 86400 );
				$results['datasets'][$data_index]['data'] = array_pad( $results['datasets'][$data_index]['data'], count( $results['datasets'][$data_index]['data'] ) + $date_difference, 0 );

				$results['datasets'][$data_index]['borderColor'] = "rgba(0,0,0,0.5)";
				$results['datasets'][$data_index]['backgroundColor'] = "rgba({$this->palette[$this->get_color_palette_index()]},0.7)";
				$data_index += 1;
			}
			return $results;
		}


		/**
		 * Get top searches
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_top_searches(){
			global $wpdb;
			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$product_ids = $this->product_ids ? implode( ',', $this->product_ids ) : false;

			$query = "SELECT query, SUM(s.hits) AS total_hits, s.product_id";
			$query .= $wpdb->prepare(
				" FROM %i AS s",
				$search_table
			);
			$query .= " WHERE s.hits <> 0";
			$query .= " AND s.found_posts <> 0";
			$query .= $product_ids ? $wpdb->prepare( " AND s.product_id IN (%s)", $product_ids ) : "";
			$query .= " GROUP BY s.product_id, query ORDER BY total_hits DESC LIMIT {$this->max_results}";

			$searches = $wpdb->get_results( $query );

			return $this->get_searches_rows( $searches );
		}

		/**
		 * Get top searches by time range
		 *
		 * @since 1.8.0
		 *
		 * @param $date_from
		 * @param $date_to
		 * @return string
		 */
		private function get_top_searches_time_range( $date_from, $date_to ){
			global $wpdb;
			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$product_ids = $this->product_ids ? implode( ',', $this->product_ids ) : false;

			$query = "SELECT query, SUM(s.hits) AS total_hits, s.product_id";
			$query .= $wpdb->prepare(
				" FROM %i AS s",
				$search_table
			);
			$query .= " WHERE s.hits <> 0";
			$query .= " AND s.found_posts <> 0";
			$query .= $wpdb->prepare(
				" AND s.time BETWEEN '%s' AND '%s'",
				$date_from,
				$date_to
			);
			$query .= $product_ids ? $wpdb->prepare( " AND s.product_id IN (%s)", $product_ids ) : "";
			$query .= " GROUP BY s.product_id, query ORDER BY total_hits DESC LIMIT {$this->max_results}";

			$searches = $wpdb->get_results( $query );

			return $this->get_searches_rows( $searches );
		}


		/**
		 * Get top failed searches
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_failed_searches(){
			global $wpdb;
			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$product_ids = $this->product_ids ? implode( ',', $this->product_ids ) : false;

			$query = "SELECT query, SUM(s.hits) AS total_hits, s.product_id";
			$query .= $wpdb->prepare(
				" FROM %i AS s",
				$search_table
			);
			$query .= " WHERE s.hits <> 0";
			$query .= " AND s.found_posts = 0";
			$query .= $product_ids ? $wpdb->prepare( " AND s.product_id IN (%s)", $product_ids ) : "";
			$query .= " GROUP BY s.product_id, query ORDER BY total_hits DESC LIMIT {$this->max_results}";

			$searches = $wpdb->get_results( $query );

			return $this->get_searches_rows( $searches );
		}


		/**
		 * Generates HTML for searches results
		 *
		 * @since 1.8.0
		 *
		 * @param $searches
		 * @return string
		 */
		private function get_searches_rows( $searches ){

			ob_start();

			if( ! empty( $searches ) ){
				echo '<ul class="insight-searches-list">';

				foreach( $searches as $search ){

					$product = $search->product_id ? $this->products[ (int)$search->product_id ] : false;
					$product_id = $product ? (int)$search->product_id : false;
					$product_link = $product_id ? get_term_link( $product_id ) : false;

					echo '<li>';
					echo '<div>';
					echo '<span class="insight-search-query">' . $search->query . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<br>';
					if( $product_link ){
						echo "<span class='search-product'><a href='{$product_link}' target='_blank'>{$product->name}</a></span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					else{
						$global_product = esc_html__( 'Global search', 'basepress' );
						echo "<span class='search-product'><a href='#' onclick='return false;' target='_blank'>{$global_product}</a></span>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					echo '</div>';

					echo '<div>';
					echo '<span class="insight-search-hits">' . $search->total_hits . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</div>';
					echo '</li>';
				}
				echo '</ul>';
			}
			else{
				echo '<p>' . esc_html__( 'No results to display here!', 'basepress' ) . '</p>';
			}

			return ob_get_clean();
		}


		/**
		 * Get list of products
		 *
		 * @since 1.8.0
		 */
		private function get_products_list(){

			$products = get_terms(
				array(
					'taxonomy' => 'knowledgebase_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'meta_key'   => 'basepress_position',
					'orderby'    => 'meta_value_num',
					'order'      => 'ASC',
				)
			);

			echo '<ul>';
			echo "<li id='product-selection-all' class='product-selection selected' data-product='0'>" . esc_html__( 'Global', 'basepress' ) . "</li>";
			foreach( $products as $product ){
				echo "<li class='product-selection' data-product='{$product->term_id}'>{$product->name}</li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</ul>';
		}


		/**
		 * Get products objects
		 *
		 * @since 1.8.0
		 */
		private function get_products(){
			global $basepress_utils;

			if( $this->is_single_product_mode ){
				$ids = $basepress_utils->get_active_product_id();
			}
			else{
				$ids = isset( $_POST['products'] ) ? array_map( 'intval', explode( ',', $_POST['products'] ) ) : false; // phpcs:ignore
				$ids = 1 == count( $ids ) && 0 == $ids[0] ? false : $ids;
			}

			$product_terms = get_terms(
				array(
					'taxonomy' => 'knowledgebase_cat',
					'include' => $ids,
					'hide_empty' => true,
					'parent'     => 0,
					'meta_key'   => 'basepress_position',
					'orderby'    => 'meta_value_num',
					'order'      => 'ASC',
				)
			);

			$products = array();
			foreach( $product_terms as $product_term ){
				$products[$product_term->term_id] = $product_term;
			}

			$this->products = $products;
			$this->product_ids = array_keys( $products );
			//If we are fetching global stats we need to add the ID zero as well
			if( empty( $ids ) ){
				$this->product_ids[] = 0;
			}
		}


		/**
		 * Log daily articles' views on DB
		 *
		 * @since 1.8.0
		 *
		 * @param $post_id
		 * @param $product_id
		 */
		public function log_post_views( $post_id, $product_id ){
			global $wpdb;

			if ( $post_id && $product_id ) {
				$post_id    = esc_sql( $post_id );
				$product_id = esc_sql( $product_id );

				$views_table = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
				$current_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i AS v WHERE v.post_id = %d AND v.product_id = %d AND v.time = CURDATE()", $views_table, $post_id, $product_id ) );

				if( $current_log ){
					$wpdb->update(
						$views_table,
						array(
							'product_id' => $current_log->product_id,
							'post_id'    => $current_log->post_id,
							'hits'       => $current_log->hits + 1,
							'time'       => $current_log->time
						),
						array( 'id' => $current_log->id ), //WHERE
						array( '%d', '%d', '%d', '%s' ), //DATA FORMAT
						array( '%d' ) //WHERE FORMAT

					);
				}else{
					$wpdb->insert(
						$views_table,
						array(
							'product_id' => $product_id,
							'post_id'    => $post_id,
							'hits'       => 1,
							'time'       => date( 'Y-m-d' ),
						)
					);
				}
			}
		}


		/**
		 * Cache data for searches for main search query
		 *
		 * @since 1.8.0
		 *
		 * @param $product
		 * @param $terms
		 */
		public function log_main_search( $product, $terms ){
			$this->log_search_data = array( 'product' => $product, 'terms' => $terms );
		}


		/**
		 * Cache data for searches for Ajax search
		 *
		 * @since 1.8.0
		 */
		public function basepress_log_ajax_search(){
			$product = isset( $_GET['product'] ) && ! empty( $_GET['product'] )
				? get_term_by( 'slug', sanitize_text_field( wp_unslash( $_GET['product'] ) ), 'knowledgebase_cat')
				: '';
			$terms = isset( $_GET['terms'] ) ? sanitize_text_field( wp_unslash( $_GET['terms'] ) ) : '';
			$found_posts = isset( $_GET['found_posts'] ) ? wp_unslash( (int)$_GET['found_posts'] ) : 0;

			$this->log_search_data = array( 'product' => $product, 'terms' => $terms );
			$this->log_searches( $found_posts );

			wp_die();
		}


		/**
		 * Public function to log custom searches
		 *
		 * @since 2.14.11
		 *
		 * @param false $product
		 * @param string $search_terms
		 */
		public function set_log_search_data( $product = false, $search_terms = '' ){
			//If there are no search terms bail out
			if( empty( $search_terms ) ){
				return;
			}

			$product = ! empty( $product ) && is_numeric( $product ) ? get_term( $product, 'knowledgebase_cat' ) : 0;
			$this->log_search_data = array( 'product' => $product, 'terms' => $search_terms );
		}


		/**
		 * Log searches
		 *
		 * @since 1.8.0
		 *
		 * @param $found_posts
		 * @return mixed
		 */
		public function log_searches( $found_posts ){
			global $wpdb;

			$is_main_query = ! isset( $GLOBALS['posts'] );

			//Bail out early if we already logged the search or this is not the main query or user is admin
			if( current_user_can('administrator') || $this->has_logged_search || ! $is_main_query || empty( $this->log_search_data ) ){
				//We are using a filter hook so we need to always return the passed parameter
				return $found_posts;
			}

			$this->has_logged_search = true;

			$product = $this->log_search_data['product'];
			$product_id = empty( $product ) ? 0 : $product->term_id;
			$terms = $this->log_search_data['terms'];

			if( ! empty( $terms ) ){
				$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
				$current_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i AS s WHERE s.query = '%s' AND s.product_id = %d AND s.time = CURDATE()", $search_table, $terms, $product_id ) );

				if( $current_log ){

					$wpdb->update(
						$search_table,
						array(
							'product_id' => $current_log->product_id,
							'query'      => $current_log->query,
							'hits'       => $current_log->hits + 1,
							'found_posts' => $found_posts,
							'time'       => $current_log->time
						),
						array( 'id' => $current_log->id ) //WHERE
					);
				}else{

					$wpdb->insert(
						$search_table,
						array(
							'product_id' => $product_id,
							'query'      => $terms,
							'hits'       => 1,
							'found_posts' => $found_posts,
							'time'       => date( 'Y-m-d' ),
						)
					);
				}
			}

			//We are using a filter hook so we need to always return the passed parameter
			return $found_posts;
		}


		/**
		 * Delete old logs records via ajax call
		 *
		 * @since 1.8.0
		 */
		public function basepress_delete_logs(){
			header('Content-type: application/json');
			$this->verify_insight_request();

			global $wpdb;
			$views_table = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$older_than = sanitize_text_field( wp_unslash( $_POST['olderthan'] ) );

			if( $older_than ){

				$wpdb->query(
					$wpdb->prepare( "DELETE FROM %i WHERE time < '%s'", $views_table, $older_than )
				);

				$wpdb->query(
					$wpdb->prepare( "DELETE FROM %i WHERE time < '%s'", $search_table, $older_than )
				);
			}

			wp_die();
		}


		/**
		 * Function to create the tables for Insights
		 * Called on plugins settings save.
		 *
		 * @since 1.8.0
		 */
		public function maybe_create_tables( $options ){
			global $wpdb;

			if( ! isset( $options['activate_insights'] ) ){
				return;
			}

			$search_table_name = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$visits_table_name = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
			$charset_collate = $wpdb->get_charset_collate();

			if($wpdb->get_var("SHOW TABLES LIKE '$search_table_name'") != $search_table_name) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				//table not in database. Create new table
				$sql = "CREATE TABLE $search_table_name (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					product_id bigint(20) unsigned,
					query VARCHAR(191) NOT NULL,
					hits SMALLINT unsigned NOT NULL,
					found_posts SMALLINT unsigned NOT NULL, 
					time DATE NOT NULL,
					UNIQUE KEY id (id),
					KEY (query)
					) $charset_collate;";

				dbDelta( $sql );
			}

			if($wpdb->get_var("SHOW TABLES LIKE '$visits_table_name'") != $visits_table_name) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				//table not in database. Create new table
				$sql = "CREATE TABLE $visits_table_name (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					product_id bigint(20) unsigned NOT NULL,
					post_id bigint(20) unsigned NOT NULL,
					hits smallint unsigned NOT NULL,
					time DATE NOT NULL,
					PRIMARY KEY (id),
					KEY (product_id, post_id)
					) $charset_collate;";

				dbDelta( $sql );
			}
		}

	}

	global $basepress_insights;
	$basepress_insights = new Basepress_Insights;
}

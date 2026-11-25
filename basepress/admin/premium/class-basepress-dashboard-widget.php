<?php
/**
 * This is the class that handles the users feedback
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Dashboard_Widget' ) ) {

	class Basepress_Dashboard_Widget {

		/**
		 * Basepress_Users_Feedback constructor.
		 *
		 * @since 2.0.0
		 */
		public function __construct(){
			add_action( 'wp_dashboard_setup', array( $this, 'render_widget' ) );
			add_action( 'admin_head', array( $this, 'add_css_styles' ) );
		}

		/**
		 * Adds styles for dashboard widgets
		 *
		 * @since 2.0.0
		 */
		public function add_css_styles(){
			?>
			<style>
				.basepress-dw-stats{
					display: table;
					width: calc(100% + 20px);
					border-collapse: separate;
					border-spacing: 10px;
					box-sizing: border-box;
					margin-left: -10px;
				}
				.basepress-dw-stat{
					display: table-cell;
					padding: 1em;
					text-align: center;
					background-color: #eee;
					border-radius: 8px;
					width: 33%;
					max-width: 33%;
				}
				.basepress-dw-stat-number{
					display: block;
					font-size: 2em;
					margin-bottom: 10px;
				}
				.basepress-dw-stat-txt{
					word-break: break-word;
				}
				#basepress-dw-top-searches-title .dashicons-search{
					margin-right: 5px;
				}
				.basepress-dw-top-searches{
					width: 100%;
					text-align: left;
				}
				.basepress-dw-top-searches th:first-of-type{
					width: 80%;
				}
				.basepress-dw-top-searches td{
					padding: 0.3em 0;
					border-top: 1px solid #eee;
				}
				.basepress-dw-top-searches tr:first-of-type td{
					border-top: none;
				}
				.basepress-dw-top-searches td:first-of-type{
					padding-right: 2em;
				}
				.basepress-dw-lowscore-icon{
					color: red;
				}
				.basepress-dw-feedback-icon{
					color: #37d237;
				}
			</style>
			<?php
		}

		/**
		 * Render widget function
		 *
		 * @since 2.0.0
		 */
		public function render_widget(){
			wp_add_dashboard_widget(
				'basepress-dashboard-widget',         // Widget slug.
				esc_html__( 'Knowledge Base', 'basepress' ),         // Title.
				array( $this, 'render_widget_content' ) // Display function.
			);
		}

		/**
		 * Render widget content
		 *
		 * @since 2.0.0
		 */
		public function render_widget_content() {
			global $basepress_utils;
			$options = $basepress_utils->get_options();

			$this->get_kb_stats();

			if( isset( $options['activate_feedback'] ) ){
				$this->get_feedback_info();
			}

			$this->get_low_score_articles();

			if( isset( $options['activate_insights'] ) ){
				$this->get_insight_info();
			}

		}


		/**
		 * Renders Knowledge base statistics: products , sections and articles count
		 *
		 * @since 2.0.0
		 */
		private function get_kb_stats(){
			$products_count = wp_count_terms( 'knowledgebase_cat', array( 'parent' => 0 ) );
			$sections_count = wp_count_terms( 'knowledgebase_cat', array( 'hide_empty' => false ) ) - $products_count;
			$articles_count = wp_count_posts( 'knowledgebase' );
			?>
			<ul class="basepress-dw-stats">
				<li class="basepress-dw-stat">
					<span class="basepress-dw-stat-number"><?php echo esc_html( $products_count ); ?></span>
					<span class="basepress-dw-stat-txt"><?php esc_html_e( 'Knowledge Bases', 'basepress' ); ?></span>
				</li>
				<li class="basepress-dw-stat">
					<span class="basepress-dw-stat-number"><?php echo esc_html( $sections_count ); ?></span>
					<span class="basepress-dw-stat-txt"><?php esc_html_e( 'Sections', 'basepress' ); ?></span>
				</li>
				<li class="basepress-dw-stat">
					<span class="basepress-dw-stat-number"><?php echo esc_html( $articles_count->publish ); ?></span>
					<span class="basepress-dw-stat-txt"><?php esc_html_e( 'Articles', 'basepress' ); ?></span>
				</li>
			</ul>
			<?php

		}


		/**
		 * Renders open feedback info
		 *
		 * @since 2.0.0
		 */
		private function get_feedback_info(){
			global $basepress_feedback;

			$count = wp_count_posts( 'basepress_feedback' );
			?>
			<p>
				<span class="dashicons dashicons-admin-comments basepress-dw-feedback-icon"></span>
				<a href='<?php echo esc_url( $basepress_feedback->page_url ); ?>'>
				<?php echo esc_html( $count->publish ) . ' ' . esc_html__( 'Open Feedback', 'basepress' ); ?>
				</a>
			</p>
			<?php
		}


		/**
		 * Renders count of articles to improve
		 *
		 * @since 2.0.0
		 */
		private function get_low_score_articles(){
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
				'orderby' => array(
					'score' => 'ASC',
					'votes_count' => 'DESC',
				),
				'posts_per_page' => -1,
				'fields' => 'ID'
			);

			$articles = get_posts( $args );
			?>
			<p>
				<span class="dashicons dashicons-no basepress-dw-lowscore-icon"></span>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=knowledgebase&orderby=basepress_score&order=asc' ) ); ?>">
				<?php echo count( $articles ) . ' ' . esc_html__( 'Articles to improve', 'basepress' ); ?>
				</a>
			</p>
			<?php
		}


		/**
		 * Renders Insights info
		 *
		 * @since 2.0.0
		 */
		private function get_insight_info(){
			global $wpdb, $basepress_insights;
			$views_table = $wpdb->prefix . BASEPRESS_VIEWS_TABLE;
			$search_table = $wpdb->prefix . BASEPRESS_SEARCH_TABLE;
			$date = date( 'Y-m-d' );

			//Views Query
			$query = "SELECT SUM(v.hits) AS hits";
			$query .= $wpdb->prepare(
				" FROM %i AS v",
				$views_table
			);
			$query .= $wpdb->prepare(
				" WHERE v.time = '%s'",
				$date
			);
			$query .= " GROUP BY v.time ORDER BY v.time ASC";

			$views = $wpdb->get_results( $query );
			$views_count = isset( $views[0] ) ? $views[0]->hits : 0;
			?>

			<p>
				<span class="dashicons dashicons-visibility"></span>
				<a href='<?php echo esc_url( $basepress_insights->page_url ); ?>'>
				<?php echo ' ' . esc_html( $views_count ) . ' ' . esc_html__( 'Articles viewed today', 'basepress' ); ?>
				</a>
			</p>
			<?php

			//Search Query
			$query_limit = esc_sql( apply_filters( 'basepress_dw_searches_limit', 5 ) );
			$query = "SELECT s.query, SUM(s.hits) AS hits";
			$query .= $wpdb->prepare(
				" FROM %i AS s",
				$search_table
			);
			$query .= $wpdb->prepare(
				" WHERE s.time = '%s'",
				$date
			);
			$query .= " GROUP BY s.query ORDER BY hits DESC LIMIT $query_limit";

			$searches = $wpdb->get_results( $query );

			if( count( $searches ) ){
				?>
				<h3 id="basepress-dw-top-searches-title"><span class="dashicons dashicons-search"></span><?php esc_html_e( 'Popular Searches today', 'basepress' ) ?></h3>
				<table class="basepress-dw-top-searches">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Query', 'basepress' ); ?></th>
						<th><?php esc_html_e( 'Searches', 'basepress' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach( $searches as $search ){
						echo '<tr>';
						echo '<td>' . $search->query . '</td>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td>' . $search->hits . '</td>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
				<?php
			}
			else{
				echo '<h3><span class="dashicons dashicons-search"></span>' . esc_html__( 'No Searches Today', 'basepress' ) . '</h3>';
			}
		}
	}

	new Basepress_Dashboard_Widget();
}

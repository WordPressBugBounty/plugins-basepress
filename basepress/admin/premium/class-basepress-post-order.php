<?php
/**
 * This is the class that adds the post order page on admin
 */

// Exit if called directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Basepress_Post_Order' ) ) {

	class Basepress_Post_Order {

		/**
		 * basepress_post_order constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'add_post_order_page' ) );

			add_action( 'init', array( $this, 'add_ajax_callbacks' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}



		/**
		 * Adds post reordering menu
		 *
		 * @since 1.0.0
		 */
		public function add_post_order_page() {
			//Add the sub menu 'Sections' on knowledgebase post type
			add_submenu_page( 'edit.php?post_type=knowledgebase', 'BasePress ' . esc_html__( 'Articles Order', 'basepress' ), esc_html__( 'Articles Order', 'basepress' ), 'manage_categories', 'basepress_postorder', array( $this, 'display_screen' ) );
		}



		/**
		 * Defines the ajax calls for this screen
		 *
		 * @since 1.0.0
		 */
		public function add_ajax_callbacks() {
			add_action( 'wp_ajax_basepress_get_sections_order_page', array( $this, 'basepress_get_sections_order_page' ) );
			add_action( 'wp_ajax_basepress_get_posts_order_page', array( $this, 'basepress_get_posts_order_page' ) );
			add_action( 'wp_ajax_basepress_update_posts_order', array( $this, 'basepress_update_posts_order' ) );
			add_action( 'wp_ajax_basepress_reset_posts_order', array( $this, 'basepress_reset_posts_order' ) );
		}



		/**
		 * Enqueues admin scripts for this screen
		 *
		 * @since 1.0.0
		 *
		 * @param $hook
		 */
		public function enqueue_admin_scripts( $hook ) {

			//Enqueue admin script for post order screen only
			if ( 'knowledgebase_page_basepress_postorder' == $hook ) {
				wp_register_script( 'basepress-post-order-js', BASEPRESS_URI . 'admin/premium/js/basepress-post-order.js', array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'basepress-post-order-js' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_localize_script(
					'basepress-post-order-js',
					'basepress_order_vars',
					array(
						'confirm_save_txt' => esc_html__( 'Are you sure you want to save the articles order? This action cannot be undone', 'basepress' ),
						'confirm_reset_txt' => esc_html__( 'Are you sure you want to reset the articles order? This action cannot be undone', 'basepress' ),
						'nonce' => wp_create_nonce( 'bp-post-order-nonce' ),
					)
				);
			}
		}



		/**
		 * Generates the page content
		 *
		 * @since 1.0.0
		 */
		public function display_screen() {
			?>
			<div class="wrap" id="basepress-post-order">
				<h1><?php echo esc_html__( 'Knowledge Base Articles order','basepress' ); ?></h1>
				<br>
				<div class="wp-clearfix">
					<select id="product-select" class="alignleft">
						<option value="0" selected="selected" disabled="disabled"><?php echo esc_html__( 'Select Knowledge Base', 'basepress' ); ?></option>
						<?php $this->get_products_list(); ?>
					</select>
					<select id="section-select" class="alignleft">
						<option value="0" selected="selected" disabled="disabled"><?php echo esc_html__( 'Select Section', 'basepress' ); ?></option>
					</select>
					<button id="reset-postorder" class="button button-primary"><?php echo esc_html__( 'Reset Order', 'basepress' ); ?></button>
					<button id="save-postorder" class="button button-primary"><?php echo esc_html__( 'Save Order', 'basepress' ); ?></button>

				</div>
				
				<div id="posts-order-header">
					<div id="post-order-title"><?php echo esc_html__( 'Title', 'basepress' ); ?></div>
					<div id="post-order-author"><?php echo esc_html__( 'Author', 'basepress' ); ?></div>
					<div id="post-order-product"><?php echo esc_html__( 'Knowledge Base', 'basepress' ); ?></div>
					<div id="post-order-section"><?php echo esc_html__( 'Section', 'basepress' ); ?></div>
					<div id="post-order-votes"><?php echo esc_html__( 'Votes', 'basepress' ); ?></div>
					<div id="post-order-score"><?php echo esc_html__( 'Score', 'basepress' ); ?> %</div>
					<div id="post-order-views"><?php echo esc_html__( 'Views', 'basepress' ); ?></div>
					<div id="post-order-date"><?php echo esc_html__( 'Date', 'basepress' ); ?></div>
				</div>
				
				<div id="posts-order-table">
					<ul></ul>
				</div>
				
				<!-- Ajax Loader -->
				<div id="ajax-loader"></div>
				
			</div><!-- Wrap -->
			
		<?php
		}




		/**
		 * Generates the products list
		 *
		 * @since 1.0.0
		 * @updated 1.7.6
		 */
		private function get_products_list() {
			global $basepress_utils;
			
			$active_product_id = $basepress_utils->get_active_product_id();

			//Get all product terms in knowledgebase_cat
			$args = array(
					'taxonomy'   => 'knowledgebase_cat',
					'hide_empty' => false,
					'parent'     => 0,
			);

			$product_terms = get_terms( $args );
			
			
			//Iterate over each product and add it to the select item
			foreach( $product_terms as $product ){
				$selected = ( $active_product_id && $active_product_id == $product->term_id ) ? ' selected' : '';

				echo '<option value="' . esc_attr( $product->term_id ) . '"' . esc_html( $selected ) . '>' . esc_html( $product->name ) . '</option>';
			}
		}


		/*
		 * Ajax call functions
		 */


		/**
		 * Generates the sections list for the chosen product
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function basepress_get_sections_order_page() {
			if ( ! current_user_can( 'manage_categories' ) ) {
				status_header( 403 );
				wp_die();
			}

			$product = isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '';

			$sections_list = array();

			if ( ! $product ) {
				return $sections_list;
			}

			$sections = wp_dropdown_categories(
				array(
					'taxonomy'     => 'knowledgebase_cat',
					'child_of'     => $product,
					'hide_empty'   => 0,
					'echo'         => 0,
					'hierarchical' => 1,
					'class'        => 'section-select',
					'name'         => '',
					'id'           => 'section-select',
					'selected'     => 0,
				)
			);

			$choose_section_text = esc_html__('Choose Section', 'basepress' );
			$sections = preg_replace( '/(<select.*>)/', "$0\n\t<option value='0' disabled>{$choose_section_text}</option><option disabled>─────────</option>", $sections );
			echo $sections; //phpcs:ignore
			wp_die();
		}




		/**
		 * Generates the posts list in the post order page
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function basepress_get_posts_order_page() {
			if ( ! current_user_can( 'manage_categories' ) ) {
				status_header( 403 );
				wp_die();
			}

			global $basepress_utils;

			$product = isset( $_POST['product'] ) ? get_term( sanitize_text_field( wp_unslash( $_POST['product'] ) ), 'knowledgebase_cat' ) : '';
			$section = isset( $_POST['section'] ) ? get_term( sanitize_text_field( wp_unslash( $_POST['section'] ) ), 'knowledgebase_cat' ) : '';
			$date_format = get_option( 'date_format' );
			$posts_order = $basepress_utils->get_posts_order();
			$orderby = $posts_order['orderby'];
			$order = $posts_order['order'];

			$post_list = array();
			//If the post has no product selected we return NULL
			if ( ! $product || ! $section ) {
				return $post_list;
			}

			$args = array(
				'post_type' => 'knowledgebase',
				'orderby'   => $orderby,
				'order'     => $order,
				'tax_query' => array(
					array(
						'taxonomy'         => 'knowledgebase_cat',
						'field'            => 'id',
						'terms'            => $section->term_id,
						'include_children' => false,
					)
				),
				'posts_per_page' => -1
			);

			$posts_query = new WP_Query( $args );

			if ( $posts_query->have_posts() ) {
				while ( $posts_query->have_posts() ) {
					$posts_query->the_post();
					$post_id = get_the_ID();
					$position = get_post_field( 'menu_order', $post_id, true );
					$votes = get_post_meta( $post_id, 'basepress_votes', true );
					$score = get_post_meta( $post_id, 'basepress_score', true );
					$views = get_post_meta( $post_id, 'basepress_views', true );

					if ( ! is_string( $votes ) ) {
						$like = intval( $votes['like'] );
						$dislike = intval( $votes['dislike'] );
					}

					?>
					<li class="post-order-row" data-post="<?php echo esc_attr( $post_id ); ?>" data-pos="<?php echo esc_attr( $position ); ?>">
						<div class="post-order-title"><?php the_title(); ?></div>
						<div class="post-order-author"><?php the_author(); ?></div>
						<div class="post-order-product"><?php echo esc_html( $product->name ); ?></div>
						<div class="post-order-section"><?php echo esc_html( $section->name ); ?></div>
						<div class="post-order-votes">
							<?php echo esc_html( $like ); ?><span class="dashicons dashicons-yes"></span>
							<?php echo esc_html( $dislike ); ?><span class="dashicons dashicons-no-alt"></span>
						</div>
						<div class="post-order-score"><?php echo esc_html( $score ); ?></div>
						<div class="post-order-views"><?php echo esc_html( $views ); ?></div>
						<div class="post-order-date"><?php the_time( $date_format ); ?></div>
					</li>
				<?php
				}
			}
			// Reset Post Data
			wp_reset_postdata();

			wp_die();
		}




		/**
		 * Verifies capability and nonce for post order AJAX requests.
		 *
		 * @since 2.17.0.4
		 */
		private function verify_post_order_request() {
			if ( ! current_user_can( 'manage_categories' ) ) {
				status_header( 403 );
				wp_send_json( array( 'error' => true, 'data' => __( 'You do not have permission to perform this action.', 'basepress' ) ) );
			}

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bp-post-order-nonce' ) ) {
				status_header( 403 );
				wp_send_json( array( 'error' => true, 'data' => __( 'Nonce authentication issue.', 'basepress' ) ) );
			}
		}

		/**
		 * Updates post order
		 *
		 * @since 1.0.0
		 */
		public function basepress_update_posts_order() {
			$this->verify_post_order_request();

			global $wpdb;
			$order = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? array_values( array_filter( array_map( 'absint', $_POST['order'] ) ) ) : array(); //phpcs:ignore
			if ( empty( $order ) ) {
				wp_die();
			}

			//Make order array start at index 1
			array_unshift( $order, 'x' );
			unset( $order[0] );

			$ids          = array_values( $order );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			$wpdb->query( 'SELECT @i:=0' );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET menu_order = ( @i:= @i+1 )
					WHERE ID IN ( {$placeholders} ) ORDER BY FIELD( ID, {$placeholders} );",
					array_merge( $ids, $ids )
				)
			);

			/**
			 * This filter can be used to do further processing after articles order has been saved
			 */
			do_action( 'basepress_article_order_updated', $order );

			wp_die();
		}


		/**
		 * Reset articles order
		 *
		 * @since 2.10.0
		 */
		public function basepress_reset_posts_order() {
			$this->verify_post_order_request();

			global $wpdb;
			$posts = isset( $_POST['posts'] ) && is_array( $_POST['posts'] ) ? array_values( array_filter( array_map( 'absint', $_POST['posts'] ) ) ) : array(); //phpcs:ignore
			if ( empty( $posts ) ) {
				wp_die();
			}

			$placeholders = implode( ',', array_fill( 0, count( $posts ), '%d' ) );
			$list         = implode( ',', $posts );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET menu_order = 0
					WHERE ID IN ( {$placeholders} );",
					$posts
				)
			);

			/**
			 * This filter can be used to do further processing after articles order has been saved
			 */
			do_action( 'basepress_article_order_updated', $list );

			wp_die();
		}
	}//End Class

	new basepress_post_order;
}

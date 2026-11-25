<?php
/**
 * This is the class that handles BasePress network settings on multisite
 */

// Exit if called directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Basepress_Network' ) ) {

	class Basepress_Network {

		/**
		* basepress_network constructor.
		*
		* @since 1.1.0
		*/
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'network_admin_menu', array( $this, 'add_admin_settings_page' ) );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'init', array( $this, 'add_ajax_callbacks' ) );
			}
		}

		/**
		 * Enqueues Admin scripts for multisite
		 *
		 * @since 1.1.0
		 *
		 * @param $screen
		 */
		public function enqueue_admin_scripts( $screen ) {
			$screens = array( 'settings_page_basepress', 'settings_page_basepress_network' );
			if ( in_array( $screen, $screens ) ) {
				wp_register_script( 'basepress-network-js', BASEPRESS_URI . 'admin/premium/js/basepress-network.js', array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'basepress-network-js' );
			}
		}

		/**
		 * Adds ajax call backs for multisite admin page
		 *
		 * @since 1.1.0
		 */
		public function add_ajax_callbacks() {
			add_action( 'wp_ajax_basepress_network_delete', array( $this, 'basepress_network_delete' ) );
		}


		/**
		 * Adds admin settings menu on multisite
		 *
		 * @since 1.1.0
		 */
		public function add_admin_settings_page() {
			add_submenu_page( 'settings.php', 'BasePress', 'BasePress', 'manage_network', 'basepress_network', array( $this, 'display_screen' ) );
		}

		/**
		 * Generate the admin settings page on multisite
		 *
		 * @since 1.1.0
		 */
		public function display_screen() {
			?>
			<div class="wrap">
			<h1>BasePress Multisite</h1>
			<form id="basepress-multisite">
				<?php wp_nonce_field( 'basepress_network_delete', 'basepress_delete_sites' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<?php echo esc_html__( 'Delete Content from site(s)', 'basepress' ); ?>
						</th>
						<td>
							<select id="sites" size="10" multiple style="min-width:250px">
								<?php
								$sites = get_sites();
								foreach ( $sites as $site ) {
									$site_name = get_blog_details( $site->blog_id )->blogname;
									echo '<option value="' . esc_attr( $site->blog_id ) . '">' . esc_html( $site_name ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</form>
			<p class="submit">
				<input type="submit" name="delete_data" id="delete_data_network" class="button button-primary" value="<?php echo esc_html__( 'Submit', 'basepress' ); ?>">
			</p>
			</div>
			<!-- Ajax Loader -->
			<div id="ajax-loader"></div>
			<?php
		}

		/*
		* Ajax Call Function
		*/

		/**
		 * Deletes single or multiple sites knowledge base data
		 *
		 * @since 1.1.0
		 */
		public function basepress_network_delete() {
			if ( ! current_user_can( 'manage_network' ) ) {
				status_header( 403 );
				echo 'Unauthorized';
				wp_die();
			}

			if ( ! isset( $_POST['nonce'] )
				|| ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'basepress_network_delete' ) ) {
				echo 'Nonce error';
				wp_die();
			}

			$sites = isset( $_POST['sites'] ) ? array_filter( array_map( 'absint', (array) $_POST['sites'] ) ) : array();

			if ( empty( $sites ) ) {
				echo 'No Site selected';
				wp_die();
			}

			foreach ( $sites as $site ) {
				switch_to_blog( $site );

				/*
				* Delete all Articles from database
				*/
				$args = array(
					'post_type'              => 'knowledgebase',
					'post_status'            => 'publish',
					'suppress_filters'       => true,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'no_found_rows'          => true,
					'fields'                 => 'ids',
				);

				$wp_query = new WP_Query( $args );

				if ( $wp_query->posts ) {
					foreach ( $wp_query->posts as $post ) {
						wp_delete_post( $post, true );
					}
				}

				/*
				* Delete products and sections
				*/

				$terms = get_terms(
					array(
						'taxonomy'   => 'knowledgebase_cat',
						'hide_empty' => false,
					)
				);

				foreach ( $terms as $term ) {
					wp_delete_term(
						$term->term_id,
						'knowledgebase_cat'
					);
				}

				/*
				* Remove options
				*/
				delete_option( 'basepress_settings' );
				delete_option( 'basepress_ver' );
				delete_option( 'basepress_db_ver' );
				delete_option( 'widget_basepress_products_widget' );
				delete_option( 'widget_basepress_sections_widget' );
				delete_option( 'widget_basepress_related_articles_widget' );
				delete_option( 'widget_basepress_popular_articles_widget' );
				delete_option( 'knowledgebase_cat_children' );
				delete_option( 'basepress_modern_theme' );
				delete_option( 'basepress_run_wizard' );

				restore_current_blog();
			}

			wp_die();
		}
	}

	new Basepress_Network();
}

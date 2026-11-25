<?php
/**
 * This class declares and handles the shortcodes in the editor.
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Shortcode_Editor' ) ) {

	class Basepress_Shortcode_Editor {
		/**
		 * basepress_shortcodes_editor constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'add_shortcode_editor_page' ), 40 );

			//Add post editor button for shortcodes
			add_action( 'media_buttons', array( $this, 'add_shortcode_button' ) );

			//Enqueue script for post editors
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			//Add ajax calls
			add_action( 'wp_ajax_basepress_shortcode_load_modal', array( $this, 'basepress_shortcode_load_modal' ) );
			add_action( 'wp_ajax_basepress_shortcode_modal_sections', array( $this, 'basepress_shortcode_modal_sections' ) );
			add_action( 'wp_ajax_basepress_shortcode_modal_testresults', array( $this, 'basepress_shortcode_modal_testresults' ) );
		}


		/**
		 * Adds shortcode page
		 *
		 * @since 2.0.0
		 */
		public function add_shortcode_editor_page() {
			add_submenu_page( 'basepress', 'BasePress ' . esc_html__( 'Shortcode Editor', 'basepress' ), esc_html__( 'Shortcode Editor', 'basepress' ), 'manage_options', 'basepress_shortcode_editor', array( $this, 'display_shortcode_editor_screen' ) );
		}

		
		/**
		 * Adds a shortcode button to the classic editor
		 *
		 * @since 2.13.0
		 */
		public function add_shortcode_button() {
			echo '<button type="button" id="basepress-add-shortcodes" class="button" data-editor="content">Knowledge Base</button>';
		}


		/**
		 * Enqueues admin scripts for the shortcodes
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts( $hook ) {
			global $basepress_utils;

			if( in_array(
				$hook,
				array(
					'post-new.php',
					'post.php',
					'basepress_page_basepress_shortcode_editor'
				)
			) ){
				$minified = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || isset( $_REQUEST['script_debug'] ) ? '' : 'min.';
				wp_enqueue_script( 'basepress-shortcodes', BASEPRESS_URI . "admin/premium/js/shortcode-editor.{$minified}js", array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_style( 'basepress-shortcodes', BASEPRESS_URI . "admin/premium/css/shortcode-editor.{$minified}css", BASEPRESS_VER, true );
			}

			//Load the theme style on the Shortcode editor page only. The Modal loads it via Ajax if invoked
			if( in_array(
				$hook,
				array(
					'basepress_page_basepress_shortcode_editor'
				)
			) ){
				$stylesheet = apply_filters( 'basepress_theme_style', 'style.css' );
				$theme_css = $basepress_utils->get_theme_file_uri( 'css/' . $stylesheet );
				wp_enqueue_style( 'basepress-theme-style', $theme_css, BASEPRESS_VER, true );
			}

			if( in_array(
				$hook,
				array(
					'post-new.php',
					'post.php',
				)
			) ){
				add_action( 'admin_footer', function(){
					echo '<div id="basepress-shortcodes-wrap" class="modal"></div>';
				}	);
			}
		}


		/**
		 * Ajax callback to load modal
		 *
		 * @since 2.13.0
		 */
		public function basepress_shortcode_load_modal(){
			if ( ! current_user_can( 'edit_posts' ) ) {
				status_header( 403 );
				wp_die();
			}

			global $basepress_utils;

			$stylesheet = apply_filters( 'basepress_theme_style', 'style.css' );
			$theme_css = $basepress_utils->get_theme_file_uri( 'css/' . $stylesheet );

			ob_start();
			?>
			<div class="inner-wrap">
				<h1><?php echo 'BasePress ' . esc_html__( 'Shortcode Editor', 'basepress' ); ?></h1>
					<span class="dashicons dashicons-no" id="basepress-modal-close"></span>
				<?php	$this->get_shortcode_editor_elements( true ); ?>
			</div>
			<?php

			wp_send_json(
				array(
					'modal'    => ob_get_clean(),
					'styleCss' => $theme_css
				)
			);
		}


		/**
		 * Display screen
		 *
		 * @since 2.0.0
		 */
		public function display_shortcode_editor_screen(){
			?>
			<div id="basepress-shortcodes-wrap">
				<div class="inner-wrap">
					<h1><?php echo 'BasePress ' . esc_html__( 'Shortcode Editor', 'basepress' ); ?></h1>
					<div>
						<?php $this->get_shortcode_editor_elements(); ?>
					</div>
				</div>
			</div>
			<?php
		}


		/**
		 * Renders the shortcode editor elements
		 *
		 * @sind 2.13.0
		 *
		 * @param bool $modal
		 */
		public function get_shortcode_editor_elements( $modal = false ){
			?>
			<div id="basepress-sc-tabs">
				<button class="basepress-sc-tab active" data-tab="articles"><?php esc_html_e( 'Articles list', 'basepress' ); ?></button>
				<button class="basepress-sc-tab" data-tab="notices"><?php esc_html_e( 'Notices', 'basepress' ); ?></button>
				<button class="basepress-sc-tab" data-tab="searchbar"><?php esc_html_e( 'Search Bar', 'basepress' ); ?></button>
			</div>
			<div id="basepress-sc-articles" class="basepress-sc-tab-content active"><?php $this->get_articles_form( $modal ); ?></div>
			<div id="basepress-sc-notices" class="basepress-sc-tab-content"><?php $this->get_notices_form( $modal ); ?></div>
			<div id="basepress-sc-searchbar" class="basepress-sc-tab-content"><?php $this->get_searchbar_form( $modal ); ?></div>
			<?php
		}


		/**
		 * Renders the articles shortcode form
		 *
		 * @since 2.13.0
		 */
		public function get_articles_form( $modal ){
			global $basepress_utils;
			$is_single_kb = $basepress_utils->get_option( 'single_product_mode' );
			?>
			<form id="basepress-sc-articles-form">
				<table>
					<tbody>
						<tr>
							<td id="basepress-sc-articles-leftcol">
								<?php if( ! $is_single_kb ) : ?>
								<label for="basepress-sc-articles-kbs"><?php esc_html_e( 'Knowledge Base', 'basepress' ); ?></label>
								<select id="basepress-sc-articles-kbs">
									<option value="" disabled selected><?php esc_html_e( 'Select Knowledge Base', 'basepress' ); ?></option>
									<option value=""><?php esc_html_e( 'All Knowledge Bases', 'basepress' ); ?></option>
									<?php $this->basepress_shortcode_modal_kbs(); ?>
								</select>
								<?php endif; ?>

								<label for="basepress-sc-articles-sections"><?php esc_html_e( 'Section', 'basepress' ); ?></label>
								<?php if( $is_single_kb ) : ?>
								<select id="basepress-sc-articles-sections">
									<option value="" disabled selected><?php esc_html_e( 'Select Section', 'basepress' ); ?></option>
									<option value=""><?php esc_html_e( 'All Sections', 'basepress' ); ?></option>
									<?php $this->basepress_shortcode_modal_sections( true ); ?>
								<?php else : ?>
								<select id="basepress-sc-articles-sections" disabled>
									<option value="" disabled selected><?php esc_html_e( 'All Sections', 'basepress' ); ?></option>
								<?php endif; ?>

								</select>

								<label for="basepress-sc-articles-orderby"><?php esc_html_e( 'Order by', 'basepress' ); ?></label>
								<select id="basepress-sc-articles-orderby">
									<option value="custom" selected><?php esc_html_e( 'Custom', 'basepress' ); ?></option>
									<option value="title"><?php esc_html_e( 'Title', 'basepress' ); ?></option>
									<option value="date"><?php esc_html_e( 'Date', 'basepress' ); ?></option>
									<option value="views"><?php esc_html_e( 'Views', 'basepress' ); ?></option>
									<option value="score"><?php esc_html_e( 'Score', 'basepress' ); ?></option>
								</select>

								<label for="basepress-sc-articles-order"><?php esc_html_e( 'Order', 'basepress' ); ?></label>
								<select id="basepress-sc-articles-order">
									<option value="ASC" selected><?php esc_html_e( 'Ascending', 'basepress' ); ?></option>
									<option value="DESC"><?php esc_html_e( 'Descending', 'basepress' ); ?></option>
								</select>

								<label for="basepress-sc-articles-count"><?php esc_html_e( 'Number of Articles', 'basepress' ); ?></label>
								<input id="basepress-sc-articles-count" type="number" value="5" min="1" max="99">
							</td>
							<td id="basepress-sc-articles-rightcol">
								<label for="basepress-sc-articles-results"><?php esc_html_e( 'Articles Preview', 'basepress' ); ?><span class="spinner"></span></label>
								<div id="basepress-sc-articles-results"></div>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<label for="basepress-sc-articles-shortcode"><?php esc_html_e( 'Articles Shortcode', 'basepress' ); ?></label>
								<input type="text" id="basepress-sc-articles-shortcode" readonly>
							</td>
						</tr>
					</tbody>
					<?php wp_nonce_field( 'bp-sc-nonce', 'sc-nonce' ); ?>
				</table>
				<?php if( $modal ) : ?>
				<button id="basepress-sc-articles-add" class="button button-primary" type="button"><?php esc_html_e( 'Add shortcode', 'basepress' ); ?></button>
				<?php endif; ?>
			</form>
			<?php
		}


		/**
		 * Renders the notices shortcode form
		 *
		 * @since 2.13.0
		 */
		private function get_notices_form( $modal ){
			global $basepress_notices;
			$notice_items = $basepress_notices->notice_items;
			?>
			<div id="basepress-sc-notices-list">
			<?php
			foreach( $notice_items as $notice_name => $notice_label ) {
				echo "<section class='bpress-notice is-style-{$notice_name}' data-style='{$notice_name}'>{$notice_label}</section>"; // phpcs:ignore
			}
			?>
			</div>
			<label for="basepress-sc-notices-shortcode"><?php esc_html_e( 'Notice Shortcode', 'basepress' ); ?></label>
			<input type="text" id="basepress-sc-notices-shortcode" readonly>

			<?php if( $modal ) : ?>
				<button id="basepress-sc-notices-add" class="button button-primary" type="button"><?php esc_html_e( 'Add shortcode', 'basepress' ); ?></button>
			<?php endif; ?>
			<?php
		}


		/**
		 * Renders the search bar shortcode form
		 *
		 * @since 2.13.0
		 */
		private function get_searchbar_form( $modal ){
			global $basepress_utils;
			$is_single_kb = $basepress_utils->get_option( 'single_product_mode' );
			$shortcode = $is_single_kb ? '[basepress-search]' : '';
			?>
			<form id="basepress-sc-searchbar-form">
				<?php if( ! $is_single_kb ) : ?>
				<label for="basepress-sc-searchbar-kbs"><?php esc_html_e( 'Knowledge Base', 'basepress' ); ?></label>
				<select id="basepress-sc-searchbar-kbs">
					<option value="" disabled selected><?php esc_html_e( 'Select Knowledge Base', 'basepress' ); ?></option>
					<option value=""><?php esc_html_e( 'All Knowledge Bases', 'basepress' ); ?></option>
					<?php $this->basepress_shortcode_modal_kbs(); ?>
				</select>
				<?php endif; ?>

				<label for="basepress-sc-searchbar-width"><?php esc_html_e( 'Max Width', 'basepress' ); ?></label>
				<input type="text" id="basepress-sc-searchbar-width">

				<label for="basepress-sc-searchbar-shortcode"><?php esc_html_e( 'Search bar Shortcode', 'basepress' ); ?></label>
				<input type="text" id="basepress-sc-searchbar-shortcode" value="<?php echo esc_attr( $shortcode ); ?>" readonly>

			<?php if( $modal ) : ?>
				<button id="basepress-sc-searchbar-add" class="button button-primary" type="button"><?php esc_html_e( 'Add shortcode', 'basepress' ); ?></button>
			<?php endif; ?>
			</form>
			<?php
		}


		/**
		 * Retrieves the list of KBs for the modal on post and page edit screen
		 *
		 * @since 1.0.0
		 */
		public function basepress_shortcode_modal_kbs() {
			global $wp_version;

			//Get all KBs terms in knowledgebase_cat
			$args = array(
				'taxonomy'   => 'knowledgebase_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'meta_key'   => 'basepress_position',
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			);

			if ( $wp_version <= 4.5 ) {
				$kb_terms = get_terms( 'knowledgebase_cat', $args );
			} else {
				$kb_terms = get_terms( $args );
			}

			foreach ( $kb_terms as $kb ) {
				echo '<option value="' . esc_attr( $kb->slug ) . '">' . esc_html( $kb->name ) . '</option>';
			}

		}


		/**
		 * Retrieves the list of sections for the modal on post and page edit screen
		 *
		 * @since 1.0.0
		 */
		public function basepress_shortcode_modal_sections( $is_single_kb = false) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				if ( wp_doing_ajax() ) {
					status_header( 403 );
					wp_die();
				}
				return '';
			}

			global $wp_version, $basepress_utils;

			$kb_slug = isset( $_POST['kb'] ) ? sanitize_text_field( wp_unslash( $_POST['kb'] ) ) : '';

			if( $is_single_kb ){
				$active_kb_id = $basepress_utils->get_active_product_id();
				if( $active_kb_id ){
					$active_kb = get_term( $active_kb_id, 'knowledgebase_cat');
					$kb_slug = ! is_wp_error( $active_kb ) ? $active_kb->slug : '';
				}
			}

			if( empty( $kb_slug ) ){
				return '';
			}

			$kb = get_term_by( 'slug', $kb_slug, 'knowledgebase_cat' );

			//Get all kbs terms in knowledgebase_cat
			$args = array(
				'taxonomy'   => 'knowledgebase_cat',
				'hide_empty' => false,
				'parent'     => $kb->term_id,
				'meta_key'   => 'basepress_position',
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			);

			if ( $wp_version <= 4.5 ) {
				$sections_terms = get_terms( 'knowledgebase_cat', $args );
			} else {
				$sections_terms = get_terms( $args );
			}

			foreach ( $sections_terms as $section ) {
				echo '<option value="' . esc_attr( $section->slug ) . '">' . esc_html( $section->name ) . '</option>';

				$sub_sections = get_term_children( $section->term_id, 'knowledgebase_cat' );
				foreach ( $sub_sections as $sub_section ) {
					$term = get_term( $sub_section, 'knowledgebase_cat' );
					echo '<option value="' . esc_attr( $term->slug ) . '">— ' . esc_html( $term->name ) . '</option>';
				}
			}

			if( wp_doing_ajax() ){
				wp_die();
			}
		}




		/**
		 * Generates the results of the shortcode for test purposes
		 *
		 * @since 1.0.0
		 */
		public function basepress_shortcode_modal_testresults() {
			if ( ! current_user_can( 'edit_posts' ) ) {
				status_header( 403 );
				wp_die();
			}

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_title_for_query( wp_unslash( $_POST['nonce'] ) ), 'bp-sc-nonce' ) ) {
				wp_die();
			}

			$atts = array(
				'product' => isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '',
				'kb'      => isset( $_POST['kb'] ) ? sanitize_text_field( wp_unslash( $_POST['kb'] ) ) : ( isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '' ),
				'section' => isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '',
				'count'   => isset( $_POST['count'] ) ? sanitize_text_field( wp_unslash( $_POST['count'] ) ) : '',
				'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : '',
				'order'   => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '',
			);

			$post_id = isset( $_POST['post'] ) ? sanitize_text_field( wp_unslash( $_POST['post'] ) ) : '';
			echo $this->show_article_list( $atts, $post_id ); // WPCS: XSS ok.
			wp_die();
		}




		/**
		 * Generates the post list shortcode
		 *
		 * @since 1.0.0
		 *
		 * @param $atts
		 * @param $post_id
		 * @return string
		 */
		public function show_article_list( $atts, $post_id ) {
			global $wpdb, $basepress_utils;

			$output = '';
			$terms = '';

			$a = shortcode_atts( array(
				'product' => '',
				'kb'      => '',
				'section' => '',
				'count'   => 5,
				'orderby' => 'views',
				'order'   => 'asc',
			), $atts );

			//Backwards compatibility with old products attribute
			$kb = $a['kb'] ? $a['kb'] : $a['product'];

			if( empty( $kb ) && $basepress_utils->get_option( 'single_product_mode' ) ){
				$kb_id = $basepress_utils->get_active_product_id();
				$kb_term = get_term( $kb_id, 'knowledgebase_cat' );
				$kb = is_a( $kb_term, 'WP_Term' )?  $kb_term->slug : '';
			}

			$parent = $a['section'] ? $a['section'] : $kb;

			if( $parent ){
				$terms_parent = get_term_by( 'slug', $parent, 'knowledgebase_cat' );
				$terms_parent = $terms_parent && ! is_wp_error( $terms_parent ) ? $terms_parent->term_id : 0;
				$terms_ids = get_term_children( $terms_parent, 'knowledgebase_cat' );
				if( ! empty( $terms_parent ) ){
					$terms_ids[] = $terms_parent;
				}

				$terms_args = array(
					'taxonomy'   => 'knowledgebase_cat',
					'hide_empty' => false,
					'fields'     => 'tt_ids',
					'include'    => $terms_ids
				);

				/**
				 * Filter the arguments used to query the taxonomy terms
				 */
				$terms_args = apply_filters( 'basepress_shortcode_terms_args', $terms_args );

				$terms = get_terms( $terms_args );
				$terms = ! empty( $terms ) ? implode( ',', $terms ) : '';
			}

			$count          = $a['count'];
			$orderby_clause = '';
			$order          = $a['order'];
			$meta_key_1     = '';
			$meta_key_2     = '';

			switch( $a['orderby'] ){
				case 'custom':
					$orderby_clause = "menu_order $order, post_date $order";
					break;
				case 'title':
					$orderby_clause = "post_title $order, post_date $order";
					break;
				case 'date':
					$orderby_clause = "post_date $order";
					break;
				case 'views':
					$join_key_1 = 'views';
					$join_key_2 = 'score';
					$meta_key_1 = 'basepress_views';
					$meta_key_2 = 'basepress_score';
					$orderby_clause = "IFNULL($join_key_1.meta_value+0, 0) $order, IFNULL($join_key_2.meta_value+0, 0) $order";
					break;
				case 'score':
					$join_key_1 = 'score';
					$join_key_2 = 'views';
					$meta_key_1 = 'basepress_score';
					$meta_key_2 = 'basepress_views';
					$orderby_clause = "IFNULL($join_key_1.meta_value+0, 0) $order, IFNULL($join_key_2.meta_value+0, 0) $order";
					break;
			}

			$not_in = isset( $post_id ) && $post_id ? $post_id : 0;

			$query_pieces = array(
				'select' => "SELECT {$wpdb->posts}.*	FROM {$wpdb->posts} ",
				'join'  => "",
				'where'  => " WHERE 1=1",
				'groupby' => " GROUP BY {$wpdb->posts}.ID",
				'orderby'   => " ORDER BY $orderby_clause",
				'limit'   => " LIMIT 0, {$count}"
			);

			if( $terms ){
				$query_pieces['join'] .= "LEFT JOIN {$wpdb->term_relationships} AS tr ON ( {$wpdb->posts}.ID = tr.object_id )";
				$query_pieces['where'] .= " AND tr.term_taxonomy_id IN ($terms)";
			}

			if( in_array( $a['orderby'], array( 'views', 'score') ) ){
				$query_pieces['join'] .= " LEFT JOIN {$wpdb->postmeta} AS $join_key_1 ON ({$wpdb->posts}.ID = $join_key_1.post_id AND $join_key_1.meta_key = '$meta_key_1')";
				$query_pieces['join'] .= " LEFT JOIN {$wpdb->postmeta} AS $join_key_2 ON ( {$wpdb->posts}.ID = $join_key_2.post_id AND $join_key_2.meta_key = '$meta_key_2')";
			}
			$query_pieces['where'] .= " AND {$wpdb->posts}.post_type = 'knowledgebase'";
			$query_pieces['where'] .= " AND {$wpdb->posts}.ID NOT IN ($not_in)";
			$query_pieces['where'] .= " AND {$wpdb->posts}.post_status = 'publish'";

			$query_pieces = apply_filters( 'basepress_article_shortcode_args', $query_pieces, $atts, $post_id );

			/**
			 * Filter to modify the query pieces to query the popular articles
			 */
			$query_pieces = apply_filters( 'basepress_shortcode_query_pieces', $query_pieces, $atts );

			$query = $query_pieces['select'] . $query_pieces['join'] . $query_pieces['where'] . $query_pieces['groupby'] . $query_pieces['orderby'] . $query_pieces['limit'];

			$articles = $wpdb->get_results( $query );

			// loop over query
			if ( ! empty( $articles ) && ! is_wp_error( $articles ) ){

				ob_start();
				do_action( 'basepress_shortcode_render', $articles );
				$output = ob_get_clean();

				if( empty( $output ) ){
					$output = '<ul class="bpress-article-list">';
					foreach( $articles as $article ){
						$output .= '<li class="bpress-article-list-item">';
						$output .= '<a href="' . get_the_permalink( $article ) . '" title="' . the_title_attribute( array( 'echo' => false, 'post' => $article ) ) . '">';
						$output .= get_the_title( $article ) . '</a>';
						$output .= '</li>';

					}
					$output .= '</ul>';
				}
			}
			return $output;
		}
	}//End Class

	global $basepress_shortcode_editor;
	$basepress_shortcode_editor = new Basepress_Shortcode_Editor;
}
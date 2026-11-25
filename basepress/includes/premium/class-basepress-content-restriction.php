<?php
/**
 * This is the class to handle all aspect of content restriction
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Content_Restriction' ) ) {

	#[\AllowDynamicProperties]
	class Basepress_Content_Restriction {

		/**
		 * Stores the current user role(s)
		 * @var null
		 */
		private $user_role = null;

		/**
		 * Stores the articles count for the sections
		 * @var array
		 */
		private $sections_count = array();

		/**
		 * Stores the list of sections ids divided in allowed and restricted
		 * @var null
		 */
		public $tax_terms_restrictions = null;

		/**
		 * Stores WP_Tax_Query results used for general WordPress queries
		 * @var null
		 */
		public $wp_tax_query = null;

		/**
		 * Stores the word boundaries used in DB REGEX
		 * @var array|null
		 */
		public $word_boundary = null;

		/**
		 * Stores the restriction roles for the current product or section during save_term_restrictions()
		 * @var null
		 */
		public $saved_term_meta = array();

		public function __construct() {
			global $basepress_utils;
			$options = $basepress_utils->get_options();
			if ( isset( $options['activate_restrictions'] ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'restriction_metabox_scripts' ) );

				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post', array( $this, 'save_meta_box' ) );

				//Add restriction roles filter dropdown on post list table
				add_action( 'restrict_manage_posts', array( $this, 'filter_dropdown' ), 20 );
				//Add admin columns
				add_filter( 'manage_knowledgebase_posts_columns' , array( $this, 'add_custom_columns' ) );
				//Manage custom columns
				add_action( 'manage_knowledgebase_posts_custom_column' , array( $this, 'manage_custom_columns' ), 10, 2 );

				//Products edit screen
				add_action( 'basepress_after_new_product_fields', array( $this, 'render_restriction_roles_box' ) );
				add_action( 'basepress_after_edit_product_fields', array( $this, 'render_restriction_roles_box' ) );
				add_action( 'basepress_product_added', array( $this, 'save_term_restrictions' ), 10 );
				add_action( 'basepress_product_updated', array( $this, 'save_term_restrictions' ), 10 );
				add_filter( 'basepress_product_data_args_edit', array( $this, 'get_term_restriction_data' ) );

				//Sections edit screen
				add_action( 'basepress_after_new_section_fields', array( $this, 'render_restriction_roles_box' ) );
				add_action( 'basepress_after_edit_section_fields', array( $this, 'render_restriction_roles_box' ) );
				add_action( 'basepress_section_added', array( $this, 'save_term_restrictions' ), 10 );
				add_action( 'basepress_section_updated', array( $this, 'save_term_restrictions' ), 10 );
				add_filter( 'basepress_section_data_args_edit', array( $this, 'get_term_restriction_data' ) );

				if( ( isset( $options['restriction_redirected_pages'] ) && 'none' != $options['restriction_redirected_pages'] )
					|| ! isset( $options['restriction_redirected_pages'] ) ){

					//Articles queries
					add_filter( 'basepress_multi_section_query', array( $this, 'meta_query' ) );
					add_action( 'basepress_single_section_query', array( $this, 'single_section_query' ) );
					add_action( 'basepress_tag_query', array( $this, 'tag_query' ) );

					//Searches
					add_filter( 'basepress_search_query_pieces', array( $this, 'query_pieces' ) );
					add_filter( 'basepress_search_tax_terms', array( $this, 'allowed_search_tax_terms' ), 10, 2 );

					//Related articles widget
					add_filter( 'basepress_related_articles_args', array( $this, 'meta_query' ) );

					//Adjacent articles
					add_filter( 'basepress_adjacent_articles_args', array( $this, 'query_pieces' ) );

					//Taxonomy filter
					add_filter( 'basepress_products_terms_args', array( $this, 'tax_query' ) );
					add_filter( 'basepress_sections_terms_args', array( $this, 'tax_query' ) );
					add_filter( 'get_section_post_count_args', array( $this, 'section_post_count' ), 10, 2 );
					add_filter( 'basepress_search_tax_ids_args', array( $this, 'tax_query' ) );

					//Products Widget
					add_filter( 'basepress_products_widget_terms_args', array( $this, 'tax_query' ) );

					//Sections Widget
					add_filter( 'basepress_section_widget_items', array( $this, 'remove_section_widget_items' ) );
					add_filter( 'basepress_section_widget_terms_args', array( $this, 'tax_query' ) );

					//Popular articles widget
					add_filter( 'basepress_popular_articles_terms_args', array( $this, 'tax_query' ) );
					add_filter( 'basepress_popular_articles_query_pieces', array( $this, 'query_pieces' ) );

					//Tags Widget
					add_filter( 'basepress_tags_widget_query_args', array( $this, 'tags_widget_query_args') );

					//Accordion Nav Widget
					add_filter( 'basepress_nav_term_args', array( $this, 'nav_widget_term_args' ) );
					add_filter( 'basepress_nav_post_args', array( $this, 'meta_query' ) );

					//Shortcode for articles
					add_filter( 'basepress_shortcode_terms_args', array( $this, 'tax_query' ) );
					add_filter( 'basepress_shortcode_query_pieces', array( $this, 'query_pieces' ) );
				}

				//Restrict articles on default WP queries
				add_filter( 'posts_clauses', array( $this, 'wp_query_pieces' ) );

				//Redirects
				add_action( 'template_redirect', array( $this, 'request_redirect' ) );

				//Search snippets
				add_filter( 'basepress_search_snippet', array( $this, 'search_snippet' ), 10, 2 );

				add_action( 'wp_ajax_basepress_restriction_presets_handler', array( $this, 'basepress_restriction_presets_handler' ) );

				//Set word boundaries to use in DB regex
				$word_boundary_before = $basepress_utils->get_regex_word_boundary();
				$word_boundary_after = '[[:<:]]' == $word_boundary_before ? '[[:>:]]' : $word_boundary_before;
				$this->word_boundary = array( 'before' => $word_boundary_before, 'after' => $word_boundary_after );
			}
		}


		/**
		 * Enqueues the scripts to handle the sections metabox for articles
		 *
		 * @since 1.0.0
		 */
		public function restriction_metabox_scripts( $hook ) {
			if ( in_array( $hook, array('post.php', 'post-new.php', 'knowledgebase_page_basepress_sections', 'knowledgebase_page_basepress_manage_kbs' ) ) ){
				$screen = get_current_screen();
				if( is_object( $screen ) && 'knowledgebase' == $screen->post_type ){
					wp_enqueue_script( 'basepress-post-restriction-js', BASEPRESS_URI . 'admin/premium/js/basepress-restriction-metabox.js', array( 'jquery' ), BASEPRESS_VER, true );
					wp_localize_script(
						'basepress-post-restriction-js',
						'basepress_restriction_vars',
						array(
							'nonce' => wp_create_nonce( 'bp-restriction-presets-nonce' ),
						)
					);
					wp_enqueue_style( 'basepress-post-restriction-css', BASEPRESS_URI . 'admin/premium/css/content-restriction.css', array(), BASEPRESS_VER );
				}
			}
		}



		/**
		 * Returns an array of arrays with the list of terms IDs that are allowed or restricted to current user
		 *
		 * @since 2.5.0
		 *
		 * @param $product
		 * @return array|null
		 */
		private function get_tax_terms_restrictions( $product ){

			if( $this->tax_terms_restrictions ){
				return $this->tax_terms_restrictions;
			}

			global $wpdb;

			//Get user roles
			$user_roles = $this->get_user_role();

			if( empty( $user_roles ) ){
				return false;
			}

			//Set the product ID
			$product_id = false;

			if( ! empty( $product ) ){
				if( isset( $product->id ) ){
					$product_id = $product->id;
				}
				if( ! $product_id && isset( $product->term_id ) ){
					$product_id = $product->term_id;
				}
			}

			//Get all the sections filtered by current product if present
			$term_ids = get_terms(
				array(
					'taxonomy' => 'knowledgebase_cat',
					'child_of' => $product_id ? $product_id : 0,
					'fields'   => 'ids',
				)
			);

			if( empty( $term_ids ) || is_wp_error( $term_ids ) ){
				return false;
			}
			
			//Add the product term ID to the array of terms
			if( $product_id ){
				$term_ids[] = $product_id;
			}

			//Get an array of terms with the restriction metadata from DB
			$term_ids = array_unique( $term_ids );
			$term_ids = implode( ',', $term_ids );
			$terms_clause = "AND t.term_id IN ($term_ids)";

			$terms = $wpdb->get_results(
				"
					SELECT t.term_id, tt.term_taxonomy_id, tt.parent, tm.meta_value as roles, t.name
					FROM $wpdb->terms AS t
					LEFT JOIN $wpdb->term_taxonomy as tt
					ON (t.term_id = tt.term_id)
					LEFT JOIN $wpdb->termmeta AS tm
					ON (tm.term_id = t.term_id AND tm.meta_key = 'basepress_restriction_roles')
					WHERE tt.taxonomy = 'knowledgebase_cat'
					{$terms_clause}
					ORDER BY tt.parent, t.term_id;
					",
				OBJECT_K );

			//Prepare the default values for the allowed and restricted terms arrays;
			$restricted_terms = array( 'term_ids' => array(), 'term_taxonomy_ids' => array() );
			$allowed_terms = array( 'term_ids' => array(), 'term_taxonomy_ids' => array() );

			//Loop thought the array of terms and set their restrictions
			foreach( $terms as $term ){
				$_term = $term;

				do{
					if( $_term->roles ){
						$term_roles = explode( ',', $_term->roles );
						$macthing_roles = array_intersect( $user_roles, $term_roles );
						$restricted = ! (bool)count( $macthing_roles );

						if( $restricted ){
							$restricted_terms['term_ids'][] = $term->term_id;
							$restricted_terms['term_taxonomy_ids'][] = $term->term_taxonomy_id;
							break;
						}
					}
					$_term = isset( $terms[$_term->parent] ) ? $terms[$_term->parent] : null;
				} while( $_term );
			}

			//Compute allowed terms arrays
			$allowed_terms['term_ids'] = array_diff( array_keys( $terms ), $restricted_terms['term_ids'] );

			//Compute allowed term_taxonomy_ids
			$term_taxonomy_ids = wp_list_pluck( $terms, 'term_taxonomy_id' );
			$allowed_terms['term_taxonomy_ids'] = array_diff( $term_taxonomy_ids, $restricted_terms['term_taxonomy_ids'] );

			//Cache the restrictions for future requests
			$this->tax_terms_restrictions = array( 'allowed' => $allowed_terms, 'restricted' => $restricted_terms );;

			return $this->tax_terms_restrictions;
		}

		/**
		 * Returns an array of term IDs that are allowed to the current user
		 *
		 * @since 2.5.0
		 *
		 * @param $sections_ids
		 * @param $product
		 * @return mixed
		 */
		public function allowed_search_tax_terms( $sections_ids, $product ){
			$tax_terms_restrictions = $this->get_tax_terms_restrictions( $product );
			if( $tax_terms_restrictions ){
				$term_ids = $tax_terms_restrictions['allowed']['term_taxonomy_ids'];
				sort( $term_ids );
			}
			else{
				$term_ids = false;
			}

			return $term_ids;
		}


		/**
		 * Returns an array of term IDs that are restricted to the current user
		 *
		 * @since 2.5.0
		 *
		 * @param $sections_ids
		 * @param $product
		 * @return mixed
		 */
		public function restricted_tax_terms( $product ){
			$tax_terms_restrictions = $this->get_tax_terms_restrictions( $product );
			if( $tax_terms_restrictions ){
				$term_ids = $tax_terms_restrictions['restricted']['term_ids'];
				sort( $term_ids );
			}
			else{
				$term_ids = false;
			}

			return $term_ids;
		}


		/**
		 * Adds filter dropdown for role visibility
		 *
		 * @since 2.3.2
		 */
		public function filter_dropdown( $post_type ){
			global $wp_roles, $basepress_utils;

			if( 'knowledgebase' != $post_type ) return;

			$options = $basepress_utils->get_options();

			//Restriction roles dropdown
			$roles = $wp_roles->get_names();
			$roles['guest'] = 'Guest';
			$restriction_role = isset( $_REQUEST['kb_restriction_role'] ) ? sanitize_title_for_query( wp_unslash( $_REQUEST['kb_restriction_role'] ) ) : false;
			if( isset( $options['order_roles_alpha'] ) ){
				natsort( $roles );
			}

			echo '<select name="kb_restriction_role" id="kb_restriction_role">';
			$selected = ! $restriction_role ? " selected='selected'" : '';
			echo '<option value=""' . esc_attr( $selected ) . '>'. esc_html__( 'Visible to anyone', 'basepress' ) . '</option>\n';
			foreach( $roles as $role => $role_name ){
				echo '<option value="' . esc_attr( $role )  . '"' . selected( $role, $restriction_role, false ) . '>' . esc_html( translate_user_role( $role_name ) ) . '</option>';
			}
			echo '</select>';

			//Include tax terms restrictions dropdown
			$include_terms_restriction = isset( $_REQUEST['kb_include_terms_restrictions'] ) ? intval( $_REQUEST['kb_include_terms_restrictions'] ) : false;
			echo '<select name="kb_include_terms_restrictions" id="kb_include_terms_restrictions">';
			echo '<option value="" disabled>' . esc_html__( 'Filter by:', 'basepress' ) . '</option>';
			echo '<option value="0"' . selected( $include_terms_restriction, 0, false ) . '>' . esc_html__( 'Articles roles only', 'basepress' ) . '</option>';
			echo '<option value="1"' . selected( $include_terms_restriction, 1, false ) . '>' . esc_html__( 'All hierarchy roles', 'basepress' ) . '</option>';
			echo '</select>';
		}


		/**
		 * Adds admin columns for restriction roles
		 *
		 * @since 2.3.2
		 *
		 * @param $columns
		 * @return array
		 */
		public function add_custom_columns( $columns ){
			global $basepress_utils;
			$options = $basepress_utils->get_options();

			if( isset( $options['show_roles_admin_columns'] ) ){
				$last_column = array_slice( $columns, count( $columns ) - 1, 1, true );
				array_pop( $columns );
				$columns['basepress-roles'] = esc_html__( 'Visible to:', 'basepress' );
				$columns = array_merge( $columns, $last_column );
			}
			return $columns;
		}


		/**
		 * Renders the list of roles for each article
		 *
		 * @since 2.3.2
		 *
		 * @param $column
		 * @param $post_id
		 */
		public function manage_custom_columns( $column, $post_id ){
			global $wp_roles, $basepress_utils;

			$options = $basepress_utils->get_options();

			if( isset( $options['show_roles_admin_columns'] ) ){
				$roles = $wp_roles->get_names();
				$roles['guest'] = 'Guest';

				switch( $column ){
					case 'basepress-roles':
						$post_roles = get_post_meta( $post_id, 'basepress_restriction_roles', true );
						if( ! empty( $post_roles ) ){
							$post_roles = explode( ',', $post_roles );
							if( isset( $options['order_roles_alpha'] ) ){
								natsort( $post_roles );
							}

							$separator = '';
							foreach( $post_roles as $post_role ){
								if( ! isset( $roles[$post_role] ) ) continue;
								$sort_link = add_query_arg( 'kb_restriction_role', $post_role );
								$role_name = translate_user_role( $roles[$post_role] );
								echo esc_html( $separator ) . '<a href="' . esc_url( $sort_link ) . '">'. esc_html( $role_name ) . '</a>';
								if( empty( $separator) ){
									$separator = ', ';
								}
							}
						}
						else{
							esc_html_e( 'Unset. Visible to all.','basepress');
						}
						break;
				}
			}
		}


		/**
		 * Adds the restriction metabox to articles
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $post_type
		 * @return mixed
		 */
		public function add_meta_box( $post_type ) {
			if ( 'knowledgebase' == $post_type ) {
				add_meta_box(
					'basepress_restriction',
					esc_html__( 'Visible to:', 'basepress' ),
					array( $this, 'render_meta_box' ),
					$post_type,
					'side',
					'default'
				);
			}
		}



		/**
		 * Saves the metabox restriction data for the articles
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $post_id
		 * @return mixed
		 */
		public function save_meta_box( $post_id ) {

			//We need to verify that this came from our screen and with proper authorization,
			//because save_post can be triggered at other times.

			// Check if our nonce is set.
			if ( ! isset( $_POST['basepress_restriction_meta_nonce'] ) ) {
				return $post_id;
			}

			$nonce = sanitize_key( $_POST['basepress_restriction_meta_nonce'] );

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'basepress_restriction_meta' ) ) {
				return $post_id;
			}

			//If this is an autosave, our form has not been submitted,
			//so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Check the user's permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			// OK, it's safe for us to save the data now.
			// Update the meta field.
			$restriction_roles = isset( $_POST['basepress_restriction_roles'] ) ? $_POST['basepress_restriction_roles'] : array(); // phpcs:ignore
			array_unshift( $restriction_roles, 'administrator' );
			update_post_meta( $post_id, 'basepress_restriction_roles', implode( ',', $restriction_roles ) );
		}


		/**
		 * Renders the restriction metabox for articles
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $post
		 * @return mixed
		 */
		public function render_meta_box( $post ) {
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'basepress_restriction_meta', 'basepress_restriction_meta_nonce' );

			// Use get_post_meta to retrieve an existing value from the database.
			$restriction_roles = get_post_meta( $post->ID, 'basepress_restriction_roles', true );

			$this->render_restriction_roles_box( 'article_edit_screen', $restriction_roles );
		}



		/**
		 * Renders the restriction roles list used for KBs or sections
		 *
		 * @since 2.5.0
		 */
		public function render_restriction_roles_box( $context, $restriction_roles = '' ){
			$metabox_classes = '';
			$context = $context ? $context : current_action();
			switch( $context ){
				case 'basepress_after_new_product_fields':
				case 'basepress_after_new_section_fields':
					echo '<div id="basepress-new-term-user-roles">';
					break;
				case 'basepress_after_edit_product_fields':
				case 'basepress_after_edit_section_fields':
					echo '<div id="basepress-edit-term-user-roles">';
					break;
				case 'article_edit_screen':
					echo '<div id="basepress-article-user-roles">';
					break;
			}

			//The article metabox already has a title so we can skip this
			if( 'article_edit_screen' != $context ){
				echo '<h2>' . esc_html__( 'Visible to:', 'basepress' ) . '</h2>';
				$metabox_classes = ' term_metabox';
			}


			echo "<div class='basepress-restriction-roles{$metabox_classes}'>"; // phpcs:ignore
			echo '<button class="basepress-select-restrictions button button-primary button-small">' . esc_html__( 'Select All', 'basepress' ) . '</button>';
			echo '<button class="basepress-deselect-restrictions button button-primary button-small">' . esc_html__( 'Deselect All', 'basepress' ) . '</button>';
			echo '<button class="basepress-edit-restriction-preset button button-primary button-small">' . esc_html__( 'Presets', 'basepress' ) . '</button>';
			$this->render_restriction_roles_list( $restriction_roles );
			$this->render_presets_editor( $context );
			echo '</div>';
			echo '</div>';
		}

		/**
		 * Renders the presets editor for the metabox
		 */
		function render_presets_editor( $context ){
			$class_suffix = '';
			switch( $context ){
				case 'basepress_after_new_product_fields':
					$class_suffix = '-new-product';
					break;
				case 'basepress_after_edit_product_fields':
					$class_suffix = '-edit-product';
					break;
				case 'basepress_after_new_section_fields':
					$class_suffix = '-new-section';
					break;
				case 'basepress_after_edit_section_fields':
					$class_suffix = '-edit-section';
					break;
			}
			?>
			<div class="basepress-restriction-presets-editor">
				<div class="restriction-preset-close"><span class="dashicons dashicons-no"></span></div>
				<label for="new-preset-name<?php echo esc_html( $class_suffix ); ?>"><?php esc_html_e( 'Save current selection as:', 'basepress' ); ?></label>
				<div class="new-preset-fields">
					<input type="text" name="new-preset-name" value="" id="new-preset-name<?php echo esc_html( $class_suffix ); ?>">
					<input type="button" class="save-new-preset" value="<?php esc_html_e( 'Save', 'basepress' );?>">
				</div>
				<div class="presets-list"></div>
				<div class="preset-ajax-loader"></div>
			</div>
			<?php
		}


		/**
		 * Renders the restriction roles list
		 *
		 * @since 2.5.0
		 *
		 * @param $selected
		 * @param $field_name
		 */
		private function render_restriction_roles_list( $selected ){
			global $wp_roles, $basepress_utils;

			$options = $basepress_utils->get_options();

			$roles = $wp_roles->get_names();
			$roles['guest'] = 'Guest';

			$selected = ! empty( $selected ) ? explode( ',', $selected ) : $selected;

			if( isset( $options['order_roles_alpha'] ) ){
				natsort( $roles );
			}

			echo '<div class="basepress-restriction-role-list">';

			foreach ( $roles as $role_value => $role_name ) {
				$disabled = '';
				if ( '' != $selected ) {
					$checked = in_array( $role_value, $selected ) ? ' checked="checked"' : '';
				} else {
					$checked = ' checked="checked"';
				}
				if ( 'administrator' == $role_value ) {
					$disabled = ' disabled="disabled"';
					$checked = ' checked="checked"';
				}
				echo '<p><input type="checkbox" name="basepress_restriction_roles[]" value="' . esc_attr( $role_value ) . '"' . esc_html( $checked ) . esc_html( $disabled ) . '>' . esc_html( translate_user_role( $role_name ) ) . '</p>';
			}
			echo '</div>';
		}


		/**
		 * Handles the Restriction Presets
		 *
		 * @since 2.11.0
		 */
		public function basepress_restriction_presets_handler(){
			if ( ! current_user_can( 'edit_posts' ) ) {
				status_header( 403 );
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'basepress' ) );
			}

			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'bp-restriction-presets-nonce' ) ) {
				status_header( 403 );
				wp_die( esc_html__( 'Nonce authentication issue.', 'basepress' ) );
			}

			$presets = maybe_unserialize( get_option( 'basepress_restriction_presets', array() ) );
			$process = isset( $_REQUEST['process'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['process'] ) ) : false;
			$notice = '';
			$failed_notice = esc_html__( 'The request could not be processed. Please try again.', 'basepress' );

			switch( $process ){
				case 'save_new_preset':
					$new_name = isset( $_REQUEST['data']['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['name'] ) ) : false;
					$selection = isset( $_REQUEST['data']['selection'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['selection'] ) ) : false;
					if( $new_name && $selection ){
						$name_slug = sanitize_title( $new_name );
						if( ! isset( $presets[$name_slug] ) ){
							$presets[ $name_slug ] = array( 'name' => $new_name, 'selection' => $selection );
							if(	update_option( 'basepress_restriction_presets', $presets ) ){
								$notice = sprintf( esc_html__( 'The %s preset was saved.', 'basepress' ), "<b>{$new_name}</b>" );
							}
							else{
								$notice = $failed_notice;
							}
						}
						else{
							$notice = sprintf( esc_html__( 'The %s preset already exists.', 'basepress' ), "<b>{$new_name}</b>" );
						}
					}
					break;

				case 'update':
					$preset_slug = isset( $_REQUEST['data']['preset'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['preset'] ) ) : false;
					$selection = isset( $_REQUEST['data']['selection'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['selection'] ) ) : false;
					if( $preset_slug && $selection && isset( $presets[$preset_slug] ) ){
						$presets[$preset_slug]['selection'] = $selection;
						if( update_option( 'basepress_restriction_presets', $presets ) ){
							$notice = sprintf( esc_html__( 'The preset %s was updated.', 'basepress' ), "<b>{$presets[$preset_slug]['name']}</b>" );
						}
						else{
							$notice = $failed_notice;
						}
					}
					else{
						$notice = sprintf( esc_html__( 'The preset %s could not be updated.', 'basepress' ), "<b>{$presets[$preset_slug]['name']}</b>" );
					}
					break;

				case 'delete':
					$preset_slug = isset( $_REQUEST['data']['preset'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['preset'] ) ) : false;
					if( $preset_slug && isset( $presets[$preset_slug] ) ){
						$deleted_preset_name = $presets[$preset_slug]['name'];
						unset( $presets[$preset_slug] );
						if( update_option( 'basepress_restriction_presets', $presets ) ){
							$notice = sprintf( esc_html__( 'The preset %s was deleted.', 'basepress' ), "<b>{$deleted_preset_name}</b>" );
						}
						else{
							$notice = $failed_notice;
						}
					}
					break;

				case 'apply':
					$preset_slug = isset( $_REQUEST['data']['preset'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['data']['preset'] ) ) : false;
					if( $preset_slug && isset( $presets[$preset_slug] ) ){
						wp_send_json( array( 'process' => 'apply', 'selection' => $presets[$preset_slug]['selection'] ) );
					}
					break;
			}

			if( ! empty( $notice ) ){
				echo '<p class="basepress-preset-notice"><span class="dashicons dashicons-info"></span>' . $notice . '</p>'; // WPCS: XSS ok.
			}

			if( ! empty( $presets ) ){
				ksort( $presets );
				foreach( $presets as $slug => $data ){
					echo '<div class="basepress_restriction_preset">';
					echo "<div class='preset-name'>{$data['name']}</div>"; // phpcs:ignore
					echo "<div class='preset-actions' data-slug='{$slug}'>"; // phpcs:ignore
					echo '<span class="dashicons dashicons-controls-play apply" title="' . esc_html__( 'Apply Preset', 'basepress' ) . '" data-action="apply"></span>';
					echo '<span class="dashicons dashicons-update update" title="' . esc_html__( 'Update Preset', 'basepress' ) . '" data-action="update"></span>';
					echo '<span class="dashicons dashicons-trash delete" title="' . esc_html__( 'Delete Preset', 'basepress' ) . '" data-action="delete"></span>';
					echo '</div>';
					echo '</div>';
				}
			}
			wp_die();
		}


		/**
		 * Saves the KBs and sections restrictions
		 *
		 * @since 2.5.0
		 *
		 * @param $term
		 */
		public function save_term_restrictions( $term ){
			if( $term instanceof WP_Term ){
				$form = array();

				//Extract the form data we received via ajax
				parse_str( $_POST['form'], $form ); // phpcs:ignore

				if( isset( $form['basepress_restriction_roles'] ) ){
					array_unshift( $form['basepress_restriction_roles'], 'administrator' );
					$restriction_roles = implode( ',', $form['basepress_restriction_roles'] );
				}
				else{
					$restriction_roles = 'administrator';
				}
				if( ! empty( $restriction_roles ) ){
					update_term_meta(
						$term->term_id,
						'basepress_restriction_roles',
						$restriction_roles
					);
				}

				$this->saved_term_meta['term_id'] = $term->term_id;
				$this->saved_term_meta['restriction_roles'] = $restriction_roles;
			}
		}


		/**
		 * Gets the list of restrictions for Products and Sections edit screen
		 *
		 * @since 2.5.0
		 *
		 * @param $args
		 * @return mixed
		 */
		public function get_term_restriction_data( $args ){
			$restriction_meta = get_term_meta( $args['id'], 'basepress_restriction_roles', true );
			if( ! empty( $restriction_meta ) ){
				ob_start();
				$this->render_restriction_roles_list( $restriction_meta, 'basepress_restriction_roles' );
				$args['restriction_roles'] = ob_get_clean();
			}
			return $args;
		}



		/**
		 * Returns the extra args to query sections
		 *
		 * @since 1.6.0
		 *
		 * @return mixed
		 */
		public function meta_query_args() {
			$meta_query = '';
			$user_roles = $this->get_user_role();

			if( $user_roles ){
				$word_boundary_before = '\\\\b' == $this->word_boundary['before'] ? '\\b' : $this->word_boundary['before'];
				$word_boundary_after = '\\\\b' == $this->word_boundary['after'] ? '\\b' : $this->word_boundary['after'];

				$user_role = $word_boundary_before . implode( "$word_boundary_after|$word_boundary_before", $user_roles ) . $word_boundary_after;
				$meta_query = array(
					'relation' => 'OR',
					array(
						'key'     => 'basepress_restriction_roles',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'basepress_restriction_roles',
						'value'   => $user_role,
						'compare' => 'REGEXP',
					),
				);
			}
			return $meta_query;
		}


		/**
		 * Adds meta_query arguments to query args
		 *
		 * @since 2.6.5
		 * @param $args
		 * @return mixed
		 */
		public function meta_query( $args ) {
			$args['meta_query'] = $this->meta_query_args();
			return $args;
		}



		/**
		 * Adds tax_query arguments to query args
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $args
		 * @return mixed
		 */
		public function tax_query( $args ) {
			$product = $this->get_product();
			$restricted_terms = $this->restricted_tax_terms( $product );
			if( ! empty( $restricted_terms ) ){
				$restricted_terms = implode( ',', $restricted_terms );
				$args['exclude'] = $restricted_terms;
			}

			return $args;
		}


		/**
		 * Modifies the query for single section articles to restrict content
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $query
		 * @return mixed
		 */
		public function single_section_query( $query ) {
			$meta_query = $this->meta_query_args();
			$query->set( 'meta_query', $meta_query );
		}


		/**
		 * Modifies the query to get the articles for a tag
		 *
		 * @since 2.7.0
		 *
		 * @param $query
		 */
		public function tag_query( $query ){
			$product = $this->get_product();
			$tax_terms_restrictions = $this->get_tax_terms_restrictions( $product );
			$allowed_terms = $tax_terms_restrictions ? $tax_terms_restrictions['allowed']['term_taxonomy_ids'] : false;

			$meta_query = $this->meta_query_args();

			$query->set( 'meta_query', $meta_query );

			if( ! empty( $allowed_terms ) ){
				$allowed_terms = implode( ',', $allowed_terms );
				$args = array(
					array(
						'taxonomy' => 'knowledgebase_cat',
						'field'    => 'term_taxonomy_id',
						'terms'    => $allowed_terms,
						'include_children' => false,
					)
				);

				$query->set( 'tax_query', $args );
			}
		}

		/**
		 * Modifies the query for the Tag Widget
		 *
		 * @since 2.7.0
		 *
		 * @param $args
		 * @return mixed
		 */
		public function tags_widget_query_args( $args ){
			global $wpdb;

			$tax_terms_restrictions = $this->get_tax_terms_restrictions( $args['product'] );
			//Get allowed terms IDs
			$args['terms'] = $tax_terms_restrictions ? $tax_terms_restrictions['allowed']['term_ids'] : $args['terms'];

			//Add the query JOINT for the restriction postmeta
			$args['extra_join'] = "LEFT JOIN {$wpdb->postmeta} AS pm1 ON p1.ID = pm1.post_id AND pm1.meta_key = 'basepress_restriction_roles'";
			$user_role = $this->get_user_role();

			//Add the query WHERE for restricted articles
			if( ! empty( $user_role ) ){
				$word_boundary_before = $this->word_boundary['before'];
				$word_boundary_after = $this->word_boundary['after'];
				$user_role_clause = $word_boundary_before . implode( "$word_boundary_after|$word_boundary_before", $user_role ) .$word_boundary_after;
				$args['extra_where'] = "AND ( pm1.post_id IS NULL OR pm1.meta_value REGEXP '$user_role_clause' )";
			}
			return $args;
		}


		/**
		 * Modifies $wpdb queries to filter restricted content
		 *
		 * @since 1.6.0
		 *
		 * @param mixed $pieces
		 * @return mixed
		 */
		public function query_pieces( $pieces ) {
			global $wpdb, $basepress_utils;

			$user_role = $this->get_user_role();
			if( empty( $user_role ) ){
				return $pieces;
			}

			$product = $this->get_product();

			$restricted_terms = $this->restricted_tax_terms( $product );
			$word_boundary_before = $this->word_boundary['before'];
			$word_boundary_after = $this->word_boundary['after'];
			$user_role_clause = $word_boundary_before . implode( "$word_boundary_after|$word_boundary_before", $user_role ) .$word_boundary_after;

			$pieces['join'] .= " LEFT JOIN $wpdb->postmeta AS roles ON ($wpdb->posts.ID = roles.post_id AND roles.meta_key = 'basepress_restriction_roles')";
			$pieces['where'] .= " AND (roles.meta_value REGEXP '$user_role_clause' OR roles.post_id IS NULL)";

			//We can skip the tax query for frontend searches as searches are already restricted with other methods
			if( ! empty( $restricted_terms )
				&& ! ( $basepress_utils->is_search || $basepress_utils->is_global_search )
				&& ! ( isset( $_REQUEST['kb_include_terms_restrictions'] ) && ! (int)$_REQUEST['kb_include_terms_restrictions'] ) ){

				if( ! $this->wp_tax_query ){
					$this->wp_tax_query = new WP_Tax_Query(
						array(
							array(
								'taxonomy'         => 'knowledgebase_cat',
								'field'            => 'term_id',
								'terms'            => $restricted_terms,
								'include_children' => false,
								'operator'         => 'NOT IN'
							)
						)
					);
				}
				$tq_sql = $this->wp_tax_query->get_sql(
					$wpdb->posts,
					'ID'
				);

				$pieces['join'] .= ' ' . $tq_sql['join'];
				$pieces['where'] .= ' ' . $tq_sql['where'];
			}
			return $pieces;
		}


		/**
		 * Prevents restricted content on WordPress default search and queries
		 *
		 * @since 2.5.0
		 *
		 * @param $pieces
		 * @return mixed
		 */
		public function wp_query_pieces( $pieces ){
			global $basepress_utils;

			if( $basepress_utils->is_knowledgebase ){
				return $pieces;
			}

			if( is_admin() && ! is_search() ){
				return $pieces;
			}

			$options = $basepress_utils->get_options();

			if( ( ! isset( $options['exclude_from_wp_search'] ) && ! is_admin() ) || ( is_admin() && is_search() && $_GET[ 'post_type' ] === 'knowledgebase' ) ){
				$pieces = $this->query_pieces( $pieces );
			}

			return $pieces;
		}



		/**
		 * Gets correct posts count on section according to restrictions.
		 * It will take into account children section's count as well if $include_children is true
		 *
		 * @since 2.3.0
		 *
		 * @param $count
		 * @param $section
		 * @param $include_children
		 * @return int
		 */
		public function section_post_count( $count, $section, $include_children = true ) {

			if( isset( $this->sections_count[$section->term_id] ) ){
				return $this->sections_count[$section->term_id];
			}
			$restricted_count = 0;

			if( $include_children ){
				$sections = get_term_children( $section->term_id, 'knowledgebase_cat' );
			}
			else{
				$sections = array();
			}
			$sections[] = $section->term_id;
			foreach( $sections as $section_id ){
				$post_count_args = array(
					'post_type'       => 'knowledgebase',
					'posts_per_page'	=> -1,
					'fields'          => 'ids',
					'tax_query'       => array(
						array(
							'taxonomy' => 'knowledgebase_cat',
							'field'    => 'term_id',
							'terms'    => $section_id,
							'include_children' => false,
						)
					),
					'meta_query' => $this->meta_query_args()
				);

				$posts_count_query = new WP_Query( $post_count_args );
				$restricted_count += $posts_count_query->found_posts;
				$this->sections_count[$section_id] = $posts_count_query->found_posts;
			}

			return $restricted_count;
		}



		/**
		 * Filters the list of sections in the sections widget to remove the ones with restricted articles
		 *
		 * @since 1.7.1
		 *
		 * @param mixed $sections
		 * @return mixed
		 */
		public function remove_section_widget_items( $sections ) {
			$items = array();

			//Create an array of section items with post counts corrected according to user restrictions
			foreach ( $sections as $section ) {
				$section->count = $this->section_post_count( $section->count, $section, false );
				$items[ $section->term_id ] = array(
					'term_id' => $section->term_id,
					'parent' => $section->parent,
					'count' => $section->count,
					'children_count' => 0,
				);
			}

			//Iterate over each section and add its count to its parent children count
			foreach ( $items as $item ) {
				$item_parent = $item['parent'];

				//Recursively climb up the hierarchy and add the articles count
				while ( isset( $items[ $item_parent ] ) ) {
					$items[ $item_parent ]['children_count'] += $item['count'];
					$item_parent = $items[ $item_parent ]['parent'];
				}
			}

			//Create a new sections array removing all sections according to user restrictions
			$new_sections = array();
			foreach ( $sections as $section ) {
				//if the section or any of its children as visible articles add it to the new list
				if ( $items[ $section->term_id ]['count'] + $items[ $section->term_id ]['children_count'] ) {
					$new_sections[] = $section;
				}
			}

			return $new_sections;
		}


		/**
		 * Filter the arguments for the Terms in the Accordion Nav Widget
		 *
		 * @since 2.9.0
		 *
		 * @param $args
		 * @return mixed
		 */
		public function nav_widget_term_args( $args ){
			$product = $this->get_product();
			$restricted_terms = $this->restricted_tax_terms( $product );
			$args['exclude'] = implode( ',', $restricted_terms );
			return $args;
		}



		/**
		 * Redirects requests for restricted content
		 *
		 * @since 2.5.0
		 *
		 * @param $request
		 * @return mixed
		 */
		public function request_redirect( $request ){
			global $basepress_utils, $wp_query, $post;

			if( $basepress_utils->is_knowledgebase ){
				$options = $basepress_utils->get_options();
				$product = $basepress_utils->get_product();
				$restricted_tax_terms = $this->restricted_tax_terms( $product );
				$restricted_article = false;
				$restricted = false;

				$queried_object = get_queried_object();

				//Restrict products, sections and searches
				if( is_a( $queried_object, 'WP_Term' ) ){
					if( isset( $options['restriction_redirected_pages'] ) && 'none' == $options['restriction_redirected_pages'] ){
						return $restricted;
					}

					if( $basepress_utils->is_product || $basepress_utils->is_section || $basepress_utils->is_tag || $basepress_utils->is_search ){
						if( in_array( $queried_object->term_id, $restricted_tax_terms  ) ){
							$restricted = true;
						}
					}
				}

				//Restrict articles
				if( is_a( $queried_object, 'WP_Post' ) ){
					$user_roles = $this->get_user_role();
					$article_restriction_roles = get_post_meta( $queried_object->ID, 'basepress_restriction_roles', true );

					//Check if the article is restricted
					if( $article_restriction_roles ){
						$article_restriction_roles = explode( ',', $article_restriction_roles );
						$restricted_article = ! array_intersect( $user_roles, $article_restriction_roles );
					}
					//If the article is not restricted check the product and section
					if( ! $restricted_article ){
						$section = wp_get_post_terms( $post->ID, 'knowledgebase_cat', array( 'fields' => 'ids') );
						if( ! is_wp_error( $section) && ! empty( $section ) ){
							$restricted_article = in_array( $section[0], $restricted_tax_terms );
						}
					}

					//Redirect the article or load the restricted template according to settings
					$options = $basepress_utils->get_options();
					if( $restricted_article ){
						$restriction_redirected_pages = isset( $options['restriction_redirected_pages'] ) ? $options['restriction_redirected_pages'] : 'all';
						if( $restriction_redirected_pages != 'all' ){
							add_filter( 'basepress_page_template', function(){ return 'restricted'; } );
						}
						else{
							$restricted = true;
						}
					}
				}

				//Set the redirection
				if( $restricted ){
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					$basepress_utils->is_kb_404 = true;

					if( $basepress_utils->is_single_product_mode ){
						if( in_array( $product->id, $restricted_tax_terms ) ){
							add_filter( 'wp_nav_menu_objects', array( $this, 'hide_menu_item' ), 999, 1 );
						}
					}
				}
			}
			return $request;
		}


		/**
		 * Hide menu Item
		 *
		 * @since 2.5.0
		 * @param $menu_items
		 * @return mixed
		 */
		public function hide_menu_item( $menu_items ){
			global $basepress_utils;
			$options = $basepress_utils->get_options();

			if( isset( $options['entry_page'] ) ){
				$entry_page = (int)$options['entry_page'];

				foreach( $menu_items as $index => $menu_item ){
					if( $entry_page == (int)$menu_item->object_id ){
						unset( $menu_items[$index] );
					}
				}
			}

			return $menu_items;
		}


		/**
		 * Filters the search snippets to limit content visibility
		 *
		 * @since 2.6.0
		 *
		 * @param $snippet
		 * @param $article
		 * @return string
		 */
		public function search_snippet( $snippet, $article ){
			global $basepress_utils;
			$user_roles = $this->get_user_role();
			$article_restriction_roles = get_post_meta( $article->ID, 'basepress_restriction_roles', true );

			//Check if the article is restricted
			if( $article_restriction_roles ){
				$article_restriction_roles = explode( ',', $article_restriction_roles );
				$restricted_article = ! array_intersect( $user_roles, $article_restriction_roles );
				if( $restricted_article ){
					$options = $basepress_utils->get_options();
					$snippet = isset( $options['restricted_search_snippet_notice'] ) ? $options['restricted_search_snippet_notice'] : '';
				}
			}
			return $snippet;
		}


		/**
		 * Gets the product depending on the loaded page
		 *
		 * @since 2.5.0
		 *
		 * @return array|false|WP_Term
		 */
		public function get_product(){
			global $basepress_utils;

			if( is_admin() && isset( $_REQUEST['kb_product'] ) ){
				$product = get_term_by( 'id', sanitize_title_for_query( wp_unslash( $_REQUEST['kb_product'] ) ), 'knowledgebase_cat' );
			}
			else{
				$product = $basepress_utils->get_product();
			}

			return $product;
		}


		/**
		 * Gets the user role
		 *
		 * @since 1.6.0
		 *
		 * @return mixed
		 */
		public function get_user_role() {
			//If we don't have a user role already cached get it now
			if( ! $this->user_role ){

				//If this is an admin query for articles list use the passed user role
				if( is_admin() && isset( $_REQUEST['kb_restriction_role'] ) ){
					$user_role = ! empty( $_REQUEST['kb_restriction_role'] ) ? array( sanitize_title_for_query( wp_unslash( $_REQUEST['kb_restriction_role'] ) ) ) : false;
				}
				else{

					$user = wp_get_current_user();
					if( $user->ID && ! empty( $user->roles ) ){
						$user_role = $user->roles;
					}else{
						$user_role = array( 'guest' );
					}
				}
				$this->user_role = $user_role;
			}

			return $this->user_role;
		}
	}// Class end

	global $basepress_content_restriction;
	$basepress_content_restriction = new Basepress_Content_Restriction;

}

<?php
/**
 * This is the class that manages the theme icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( ! class_exists( 'basepress_icons_manager' ) ){

	class basepress_icons_manager{

		/**
		 * List of icons sets.
		 *
		 * @var array
		 */
		public static $icon_sets = array(
			'sections',
			'post',
			'breadcrumbs',
			'votes',
			'pagination',
			'postmeta',
			'notices'
		);

		/**
		 * Icons classes
		 *
		 * @var array
		 */
		private $icons_classes = array();

		private $icons = array();

		/**
		 * basepress_icons_manager constructor.
		 */
		public function __construct(){
			
			add_action( 'admin_menu', array( $this, 'add_icons_page' ), 30 );
			
			add_action( 'init', array( $this, 'add_ajax_callbacks' ));
			
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ));
		}


		/**
		 * Add the Icons Manager page to the admin
		 */
		public function add_icons_page(){
			
			//Add the sub menu on plugin menu
			add_submenu_page( 'basepress', 'BasePress ' . esc_html__( 'Icons Manager', 'basepress' ), esc_html__( 'Icons Manager', 'basepress' ), 'manage_options', 'basepress_icons_manager', array( $this, 'display_screen' ) );
			
		}

		/**
		 * Adds Ajax callbacks
		 */
		public function add_ajax_callbacks(){
			if( is_admin() && current_user_can( 'manage_options' ) ){
				add_action( 'wp_ajax_basepress_load_icons', array( $this, 'basepress_load_icons' ) );
				add_action( 'wp_ajax_basepress_save_icons_option', array( $this, 'basepress_save_icons_option' ) );
				add_action( 'wp_ajax_basepress_restore_default_icons', array( $this, 'basepress_restore_default_icons' ) );
			}
		}


		/**
		 * Adds scripts used in Icons Manager page
		 *
		 * @param $hook
		 */
		public function enqueue_admin_scripts( $hook ){
			if( 'basepress_page_basepress_icons_manager' == $hook ){
				wp_register_script( 'basepress-icons-manager', plugins_url( 'js/basepress-icons-manager.js', __FILE__ ), array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'basepress-icons-manager' );
				wp_enqueue_script( 'jquery-ui-sortable' );
			}
			
		}


		/**
		 * Renders the page elements
		 */
		public function display_screen(){
			?>
			<div class="wrap">
				<h1><?php echo 'BasePress ' . esc_html__( 'Icons Manager', 'basepress' ); ?></h1>
				<div class="basepress-icons-manager-options">
					<form id="icons-manager-data">

						<div class="basepress-card">

							<div id="basepress-icons-advanced">
								<label for="css-uri"><?php echo esc_html__( 'Css URI', 'basepress' ); ?></label>
								<input type="text" id="css-uri" name="css-uri" value="" placeholder="">

								<label for="extra-classes"><?php echo esc_html__( 'Extra Classes', 'basepress' ); ?></label>
								<input type="text" id="icons-extra-classes" name="extra-classes" value="" placeholder="">

								<input type="button" id="load-icons" value="<?php echo esc_html__( 'Load Icons', 'basepress' ); ?>" class="button action">

								<input type="checkbox" id="skip-enqueue" name="skip-enqueue" value="0">
								<label for="skip-enqueue"><?php echo esc_html__( "Don't load icons font on front end", 'basepress' ); ?></label>
							</div>

							<select id="icons-context">
								<option value="post" selected><?php echo esc_html__( 'Articles Icons', 'basepress' ); ?></option>
								<option value="sections"><?php echo esc_html__( 'Sections Icons', 'basepress' ); ?></option>
								<option value="breadcrumbs"><?php echo esc_html__( 'Breadcrumbs Icons', 'basepress' ); ?></option>
								<option value="votes"><?php echo esc_html__( 'Votes Icons', 'basepress' ); ?></option>
								<option value="pagination"><?php echo esc_html__( 'Pagination Icons', 'basepress' ); ?></option>
								<option value="postmeta"><?php echo esc_html__( 'Article Meta Icons', 'basepress' ); ?></option>
								<option value="notices"><?php echo esc_html__( 'Notices Icons', 'basepress' ); ?></option>
							</select>
							<input type="button" id="sort-icons" value="<?php echo esc_html__( 'Sort Icons', 'basepress' ); ?>" class="button action">
							<input type="button" id="restore-icons" value="<?php echo esc_html__( 'Restore Saved Icons', 'basepress' ); ?>" class="button action">
							<input type="button" id="restore-default-icons" value="<?php echo esc_html__( 'Restore Default Icons', 'basepress' ); ?>" class="button action">

							<?php wp_nonce_field( 'bp-icon-nonce', 'nonce' ); ?>
							<input type="button" id="save-icons" value="<?php echo esc_html__( 'Save Icons', 'basepress' ); ?>" class="button button-primary action">
						</div>
					</form>
				</div>
				<div id="notices"></div>
				<div class="basepress-card">
					<div id="icons-context-title"></div>
					<ul id="icons-list">
					</ul>
				</div>
				<!-- Ajax Loader -->
				<div id="ajax-loader"></div>
			</div>
		<?php
		}


		/**
		 * Main function responsible of retrieving the icons list and generating the content for display
		 */
		public function basepress_load_icons(){
			header('Content-type: application/json');

			global $basepress_utils;

			$get_saved_icons = $_POST['get-saved-icons'] == 'true';
			$icons_html = '';
			$error = false;

			if( $get_saved_icons ){
				$icons_options = get_option( 'basepress_icons_sets' );
				$css_uri = $icons_options ? $icons_options['form']['css-uri'] : '';
				$extra_classes = $icons_options ? $icons_options['form']['extra-classes'] : '';
				$is_default_font = empty( $css_uri ) ? true : false;
				$is_cdn = $this->is_url_cdn( $css_uri );
			}
			else{
				$css_uri = sanitize_text_field( wp_unslash( $_POST['form']['css-uri'] ) );
				$extra_classes = sanitize_text_field(wp_unslash( $_POST['form']['extra-classes'] ) );
				$is_default_font = empty( $css_uri ) ? true : false;
				$is_cdn = $this->is_url_cdn( $css_uri );

			}

			if( $is_cdn ){
				$icons_css = $css_uri;
				$icons_css_uri = $css_uri;
			}
			else{
				$icons_css = $is_default_font ? $basepress_utils->get_default_icons_css_path() : get_template_directory() . '/' . $css_uri;
				$icons_css_uri = $is_default_font ? $basepress_utils->get_default_icons_css_uri() : get_template_directory_uri() . '/' . $css_uri;
			}

			if( file_exists( $icons_css ) || $is_cdn ){
				$this->parse_icons_css( $icons_css );

				ob_start();
				$this->render_icons( $extra_classes );
				$icons_html = ob_get_clean();

				if( $get_saved_icons ){
					$icons_data = $this->basepress_get_saved_icons_data( $icons_options );
				}
				else{
					$icons_data = $this->get_empty_icons_data();
				}
			}
			else{
				$icons_data = $this->get_empty_icons_data();
				$error = esc_html__( 'The icons css file could not be found!', 'basepress');
			}

			echo wp_json_encode(
				[
					'form' => array(
						'css_uri' => $css_uri,
						'extra_classes' => $extra_classes
					),
					'icons_classes' => $this->icons_classes,
					'icons_html' => $icons_html,
					'icons_css' => $icons_css_uri,
					'icons_options' => $icons_data,
					'error' => $error
				]
			);

			wp_die();

		}

		/**
		 * Generates an array of icon sets with empty values
		 *
		 * @return array
		 */
		static function get_empty_icons_data(){
			$empty_icons_data = array();

			foreach( self::$icon_sets as $icon_set ){
				$empty_icons_data[ $icon_set ] = array( 'icon' => array(), 'default' => '' );
			}

			return $empty_icons_data;
		}

		/**
		 * Retrieves the saved icons data if available or the default from the icons.xml file otherwise
		 *
		 * @param $icons_options
		 * @return array
		 */
		public function basepress_get_saved_icons_data( $icons_options ){
			global $basepress_utils;

			if( ! $icons_options ){
				$icons_data = array();
				$icons_xml = $basepress_utils->get_default_icons_xml_path();
				if( $icons_xml ){
					$icons_sets = simplexml_load_file( $icons_xml );

					foreach( $icons_sets as $icons_set => $icons ){
						$icons = json_decode( wp_json_encode( $icons ) );
						$icons = $this->removeUnusedIconsOptions( $icons );
						$icons_data[ $icons_set ] = array(
							'icon' => (array)$icons->icon,
							'default'  => isset( $icons->default ) ? $icons->default : ''
						);
					}
				}
			}
			else{
				$icons_data = $icons_options['icons-sets'];
			}
			return $icons_data;
		}


		/**
		 * Extracts the list of icons classes from css files
		 *
		 * @param $icons_css
		 */
		public function parse_icons_css( $icons_css ){
			if( $icons_css ){
				$icons_css_relative = str_replace( get_home_path(), home_url( '/' ), $icons_css );
				$icons_css_response = wp_safe_remote_get( $icons_css_relative );
				$parsed_file = wp_remote_retrieve_body( $icons_css_response );

				$regex = apply_filters( 'basepress_icons_manager_regex', "/\.([\w-]+):before\s*{\s*content:.*?\s*}/" );

				preg_match_all( $regex, $parsed_file, $matches );

				$this->icons = $matches[1];
				sort( $this->icons );
			}
			else{
				$this->icons = array();
			}
		}


		/**
		 * Renders the icon elements for display
		 *
		 * @param string $extra_classes
		 */
		public function render_icons( $extra_classes = '' ){
			if( !empty( $this->icons )){
				$index = 1;
				
				$extra_classes = $extra_classes ? $extra_classes . ' ' : '';

				foreach( $this->icons as $icon ){

					$this->icons_classes[] = $extra_classes . $icon;
					echo '<li class="manager-icon" data-icon="' . esc_attr( $extra_classes . $icon ) . '" title="' . esc_attr( $icon ) . '">';
					echo '<span class="manager-icon-index">' . esc_html( $index++ ) .'</span>';
					echo '<span aria-hidden="true" class="'. esc_attr( $extra_classes . $icon ) . '"></span>';
					echo '</li>';
					
				}
			}
		}


		/**
		 * Removes icons that are in the xml file but are not used in the current css file.
		 * This is added for backward compatibility with the xml file used in previous versions.
		 *
		 * @param $icons
		 * @return mixed
		 */
		private function removeUnusedIconsOptions( $icons ){
			if( ! empty( $icons ) && isset( $icons->icon ) ){
				$last_icon_index = count( (array)$icons->icon ) - 1;
				for( $i = $last_icon_index; $i >= 0; $i-- ){
				    $_icon = is_array( $icons->icon) ? $icons->icon[$i] : $icons->icon;
					if( ! in_array( $_icon, $this->icons ) ){
						if( is_array( $icons->icon ) ){
							array_splice( $icons->icon, $i, 1 );
						}
						elseif( is_string( $icons->icon ) ){
							$icons->icon = '';
						}
					}
				}
			}
			return $icons;
		}


		/**
		 * Saves icons options on database
		 */
		public function basepress_save_icons_option(){
			header('Content-type: application/json');

			global $basepress_utils;

			$icons_sets = $this::$icon_sets;
			$icons_options = json_decode( stripslashes( $_POST['iconsOptions'] ), true ); // phpcs:ignore
			$form = $_POST['form']; // phpcs:ignore

			//nonce verification
			if ( ! isset( $form['nonce'] ) || ! wp_verify_nonce( $form['nonce'], 'bp-icon-nonce' ) ) {
				header( 'Content-type: application/json' );
				echo wp_json_encode( array(
					'error' => true,
					'data'  => 'Nonce authentication issue',
				));
				wp_die();
			}

			foreach( $icons_sets as $icons_set ){
				if( ! isset( $icons_options[$icons_set]['icon'] ) ){
					$icons_options[$icons_set]['icon'] = array();
				}
				if( ! isset( $icons_options[$icons_set]['default'] ) ){
					$icons_options[$icons_set]['default'] = '';
				}
			}

			$new_icons_options = array( 'form' => $form, 'icons-sets' => $icons_options );
			$css_uri = isset( $form ) ? sanitize_text_field( $form['css-uri'] ) : '';
			$is_default_font = empty( $css_uri ) ? true : false;
			$is_cdn = $this->is_url_cdn( $css_uri );

			$icons_css = $is_default_font ? $basepress_utils->get_default_icons_css_path() : get_template_directory() . '/' . $css_uri;

			$old_icons_options = get_option( 'basepress_icons_sets' );
			$is_option_modified = $new_icons_options !== $old_icons_options || maybe_serialize( $new_icons_options ) !== maybe_serialize( $old_icons_options );

			if( $is_option_modified && ( file_exists( $icons_css ) || $is_cdn )){

				$updated = update_option( 'basepress_icons_sets', $new_icons_options );
			}

			if( ! $is_option_modified || $updated ){
				$notice = esc_html__( 'The icons options have been saved.', 'basepress' );
				echo wp_json_encode( ['success' => $notice] );
			}
			else{
				$notice = esc_html__( 'The icons options could not be saved. Please try again.', 'basepress' );
				echo wp_json_encode( ['error' => $notice] );
			}
			wp_die();
		}

		/**
		 * Deletes the icons options from database.
		 * This would automatically restore the icons to the xml file.
		 */
		public function basepress_restore_default_icons(){
			header('Content-type: application/json');

			$old_option = get_option( 'basepress_icons_sets' );
			$deleted = delete_option( 'basepress_icons_sets' );

			if( $deleted || ! $old_option ){
				$notice = esc_html__( 'The icons options have been restored to default.', 'basepress' );
				echo wp_json_encode( ['success' => $notice] );
			}
			else{
				$notice = esc_html__( 'The icons options could not be restored to default. Please try again.', 'basepress' );
				echo wp_json_encode( ['error' => $notice] );
			}
			wp_die();
		}

		/**
		 * @param $css_uri
		 * @return bool
		 */
		private function is_url_cdn( $css_uri ){
			return filter_var( $css_uri, FILTER_VALIDATE_URL ) !== false;
		}
	}

	new basepress_icons_manager;
}
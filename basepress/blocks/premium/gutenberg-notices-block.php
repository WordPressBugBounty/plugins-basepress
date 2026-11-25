<?php
/**
 * This is the class that adds the Gutenberg Notices block
 */

// Exit if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Notices_block' ) ){

	class Basepress_Notices_block{

		/**
		 * Basepress_Notices_block constructor.
		 *
		 * @since 2.12.0
		 */
		public function __construct(){
			add_action( 'init', array( $this, 'register_block' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
			add_filter( 'render_block', array( $this, 'render_block' ), 10, 2 );
		}


		/**
		 * Register the block
		 *
		 * @since 2.12.0
		 */
		public function register_block(){
			global $basepress_notices;

			wp_register_script(
				'basepress-kb-notices-block-editor',
				plugins_url( 'js/basepress-notices-block.js', __FILE__ ),
				array(
					'wp-i18n',
					'wp-blocks',
					'wp-editor',
					'wp-element',
				),
				filemtime( __DIR__ . "/js/basepress-notices-block.js" ),
				true
			);

			$languages = BASEPRESS_DIR . '/languages/';
			wp_set_script_translations( 'basepress-kb-notices-block-editor', 'basepress', $languages );

			register_block_type(
				'basepress-kb/notices-block',
				array(
					'editor_script' => 'basepress-kb-notices-block-editor',
				)
			);

			foreach( $basepress_notices->notice_items as $name => $label ){
				/*
				 * The block Editor automatically adds a default style so we don't need to register it.
				 * We can add it to the array after registration for all other uses
				 */
				if( 'default' == $name ){
					continue;
				}

				if( function_exists( 'register_block_style' ) ){
					register_block_style( 'basepress-kb/notices-block', array( 'name' => $name, 'label' => $label ) );
				}
				else{
					add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				}
			}

		}


		/**
		 * Register REST API route for block
		 *
		 * @since 2.12.0
		 */
		public function register_rest_routes(){
			register_rest_route( 'basepress_kb/v1', '/notice_settings', array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_notice_settings' ),
				'permission_callback' => function( WP_REST_Request $request ) {
					return current_user_can( 'edit_posts' );
				},
			) );
		}


		/**
		 * Get the notice settings
		 *
		 * @since 2.12.0
		 *
		 * @return array
		 */
		public function get_notice_settings(){
			global $basepress_utils;

			$stylesheet = apply_filters( 'basepress_theme_style', 'style.css' );
			$theme_css = $basepress_utils->get_theme_file_uri( 'css/' . $stylesheet );

			return array(
				'styleCss'   => $theme_css,
			);
		}


		/**
		 * Renders the notice block in the frontend
		 *
		 * @since 2.12.0
		 *
		 * @param $block_content
		 * @param $block
		 * @return false|string
		 */
		public function render_block( $block_content, $block ) {
			if ( 'basepress-kb/notices-block' !== $block['blockName'] ) {
				return $block_content;
			}

			global $basepress_notices;

			$style = 'default';

			if( isset( $block['attrs']['className'] ) ){
				preg_match( '/\bis-style-(.+?)\b/', $block['attrs']['className'], $has_style );
				if( ! empty( $has_style ) && isset( $has_style[1] ) && ! empty( $has_style[1] ) ){
					$style = $has_style[1];
				}
			}

			$content = str_replace( "\n", '', $block_content );
			$opening_tag = str_replace( "\n", '', $block['innerContent'][0] );
			$closing_tag = str_replace( "\n", '', end( $block['innerContent'] ) );

			$content = substr( $content, strlen( $opening_tag ) );
			$content = substr( $content, 0,strlen( $content ) - strlen( $closing_tag ) );

			return $basepress_notices->render_notice( $style, $content );
		}


		public function admin_notices(){
			if( apply_filters( 'basepress_hide_notices', false ) ){
				return;
			}
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'BasePress Notices block requires at least WordPress 5.3 to work.', 'basepress' ); ?></p>
			</div>
			<?php
		}
	}

	global $basepress_notices_block;
	$basepress_notices_block = new Basepress_Notices_block();
}

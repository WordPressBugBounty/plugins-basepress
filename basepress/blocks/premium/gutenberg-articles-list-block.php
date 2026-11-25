<?php
/**
 * This is the class that adds the Gutenberg articles list block
 */

// Exit if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Articles_list_block' ) ){

	class Basepress_Articles_list_block{

		/**
		 * Basepress_Articles_list_block constructor.
		 *
		 * @since 2.1.0
		 */
		public function __construct(){
			add_action( 'init', array( $this, 'register_block' ) );
		}



		/**
		 * Register the block
		 *
		 * @since 2.1.0
		 */
		public function register_block(){

			wp_register_script(
				'basepress-kb-articles-list-editor',
				plugins_url( 'js/basepress-articles-list-block.js', __FILE__ ),
				array(
					'wp-i18n',
					'wp-blocks',
					'wp-element',
					'wp-components',
					'wp-editor'
				),
				filemtime( __DIR__ . "/js/basepress-articles-list-block.js" ),
				true
			);

			$languages = BASEPRESS_DIR . '/languages/';
			wp_set_script_translations( 'basepress-kb-articles-list-editor', 'basepress', $languages );

			register_block_type(
				'basepress-kb/articles-list-block',
				array(
					'editor_script'   => 'basepress-kb-articles-list-editor',
					'render_callback' => array( $this, 'block_render' ),
					'attributes'      => array(
						'product' => array(
							'type' => 'string',
						),
						'section' => array(
							'type' => 'string',
						),
						'orderby' => array(
							'type' => 'string',
							'default' => 'custom'
						),
						'order'   => array(
							'type' => 'string',
							'default' => 'asc',
						),
						'count'   => array(
							'type' => 'number',
							'default' => 5,
						)
					)
				)
			);
		}


		/**
		 * Render the block
		 *
		 * @since 2.1.0
		 *
		 * @param $atts
		 * @return string
		 */
		public function block_render( $atts ){
			global $post, $basepress_shortcode_editor;

			if( empty( $atts ) || empty( $post ) ){
				return '';
			}

			$section = isset( $atts['section'] ) && is_numeric( $atts['section'] ) ? get_term( (int)$atts['section'], 'knowledgebase_cat' ) : '';
			$section_slug = ! empty( $section ) && ! is_wp_error( $section ) ? $section->slug : '';
			$product = empty( $section_slug ) && isset( $atts['product'] ) && is_numeric( $atts['product'] ) ? get_term( (int)$atts['product'], 'knowledgebase_cat' ) : '';
			$product_slug = ! empty( $product ) && ! is_wp_error( $product ) ? $product->slug : '';
			$atts['product'] = $product_slug;
			$atts['section'] = $section_slug;
			return $basepress_shortcode_editor->show_article_list( $atts, $post->ID );
		}
	}
	new Basepress_Articles_list_block();
}

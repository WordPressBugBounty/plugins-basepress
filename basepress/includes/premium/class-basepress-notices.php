<?php
/**
 * This is the class that renders the notices
 */

// Exit if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Notices' ) ){

	class Basepress_Notices{

		public $notice_items = array();

		/**
		 * Basepress_Notices_block constructor.
		 *
		 * @since 2.12.0
		 */
		public function __construct(){
			// Add the shortcode
			add_action( 'init', array( $this, 'init' ) );
		}


		public function init(){
			add_shortcode( 'basepress-notice', array( $this, 'render_shortcode' ) );

			$notice_items = apply_filters(
				'basepress_notices',
				array(
					'info'    => esc_html__( 'Info', 'basepress' ),
					'success' => esc_html__( 'Success', 'basepress' ),
					'warning' => esc_html__( 'Warning', 'basepress' ),
					'danger'  => esc_html__( 'Danger', 'basepress' )
				)
			);

			$notice_items = array_merge( array( 'default' => esc_html__( 'Default', 'basepress' ) ), $notice_items );

			$this->notice_items = $notice_items;
		}


		/**
		 * Renders the notice shortcode
		 *
		 * @since 2.12.0
		 *
		 * @param $atts
		 * @param $content
		 * @return false|string
		 */
		public function render_shortcode( $atts, $content ){
			$style = isset( $atts['style'] ) ? $atts['style'] : 'default';

			return $this->render_notice( $style, do_shortcode( $content ) );
		}


		/**
		 * Renders the notice
		 *
		 * @since 2.12.0
		 *
		 * @param $style
		 * @param $content
		 * @return false|string
		 */
		public function render_notice( $style, $content ){
			global $basepress_utils;

			$show_title = (bool)$basepress_utils->get_option( 'show_notice_titles' );
			$show_icon = (bool)$basepress_utils->get_option( 'show_notice_icons' );
			$title = '';
			$icon = '';
			$extra_classes = array();

			if( $show_title ){
				$titles = $basepress_utils->get_option( "notice_titles" );
				$title = ! empty( $titles ) && isset( $titles[ $style ] ) ? $titles[ $style ] : '';
			}

			if( $show_icon ){
				$icons = isset( $basepress_utils->icons->notices ) ? $basepress_utils->icons->notices : false;
				//If there are not icons we should skip this
				if( empty( $icons->icon ) ){
					$show_icon = false;
				}
				else{
					$icon_index = array_search( $style, array_keys( $this->notice_items ) );
					$icon = false !== $icon_index && ! empty( $icons ) ? $icons->icon[ $icon_index ] : '';
				}
			}

			/**
			 * Allow devs to filter the notice data before use
			 */
			$notice_data = apply_filters( 'basepress_notice_data', array(
				'style'      => $style,
				'content'    => $content,
				'title'      => $title,
				'icon'       => $icon,
				'show_title' => $show_title,
				'show_icon'  => $show_icon
			) );

			/**
			 * Allow devs to render the notice via an action
			 */
			ob_start();

			do_action( 'basepress_notice_output', $notice_data );

			$custom_output = ob_get_clean();

			if( ! empty( $custom_output ) ){
				return $custom_output;
			}

			/**
			 * If there is no custom rendering we use our default one
			 */

			if( $notice_data['show_title'] ){
				$extra_classes[] = 'has-title';
			}

			if( $notice_data['show_icon'] ){
				$extra_classes[] = 'has-icon';
			}

			$extra_classes = empty( $extra_classes ) ? '' : ' ' . implode( ' ', $extra_classes );
			$opening_tag = "<section class='bpress-notice{$extra_classes} is-style-{$style}'>";
			$closing_tag = '</section>';

			ob_start();

			//Render opening parent element
			echo $opening_tag; // wpcs: XSS ok.

			//Render block icon
			if( $notice_data['show_icon'] && ! empty( $notice_data['icon'] ) ){
				echo '<div class="bpress-notice-icon">';
				echo '<span class="' . esc_attr( $notice_data['icon'] ) . '"></span>';
				echo '</div>';
			}

			//Render block title
			if( $notice_data['show_title'] && ! empty( $notice_data['title'] ) ){
				echo '<div class="bpress-notice-title">' . wp_kses_post( $notice_data['title'] ) . '</div>';
			}

			//Render block content
			echo '<div class="bpress-notice-content">';
			echo wp_kses_post(  $notice_data['content'] );
			echo '</div>';

			//Render closing parent element
			echo $closing_tag; // wpcs: XSS ok.

			return ob_get_clean();
		}
	}

	global $basepress_notices;
	$basepress_notices = new Basepress_Notices();
}

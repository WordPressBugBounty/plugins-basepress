<?php
/**
 * This is the class that handles widgets visibility
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Widget_Visibility' ) ) {

	class Basepress_Widget_Visibility {

		public $widget_ids = array(
			'basepress_products_widget',
			'basepress_sections_widget',
			'basepress_nav_widget',
			'basepress_popular_articles_widget',
			'basepress_tag_cloud',
		);

		public $visibility_pages = array();


		/**
		 * basepress_widgets constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action( 'in_widget_form', array( $this, 'add_widgets_limit' ), 10, 3  );

			add_filter( 'widget_update_callback', array( $this, 'save_widget_limits' ), 10, 4 );

			add_filter( 'widget_display_callback', array( $this, 'limit_widget_display' ), 10, 3 );

			$this->visibility_pages = array(
				'product'       => esc_html__( 'Main sections', 'basepress' ),
				'section'       => esc_html__( 'Single section', 'basepress' ),
				'tag'           => esc_html__( 'Tags', 'basepress' ),
				'article'       => esc_html__( 'Articles', 'basepress' ),
				'search'        => esc_html__( 'Search results', 'basepress' ),
				'global_search' => esc_html__( 'Global search results', 'basepress' ),
				'other'         => esc_html__( 'Other pages', 'basepress' )
			);
		}


		public function add_widgets_limit( $widget, $return, $instance ){
			if( in_array( $widget->id_base, $this->widget_ids ) ){
				$this->render_limit_fields( $instance, $widget );
			}
		}

		public function save_widget_limits( $instance, $new_instance, $old_instance, $widget ){
			if( in_array( $widget->id_base, $this->widget_ids ) ){
				$instance['visible_in'] = ! empty( $new_instance['visible_in'] ) ? $new_instance['visible_in'] : array_keys( $this->visibility_pages );
			}
			return $instance;
		}

		public function limit_widget_display( $instance, $widget, $args ){
			if( in_array( $widget->id_base, $this->widget_ids ) ){
				if( isset( $instance['visible_in'] ) && ! empty( $instance['visible_in'] ) ){
					global $basepress_utils;

					//If "Other pages" is selected we allow the widget accordingly
					if( in_array( 'other', $instance['visible_in'] ) ){
						if( ! $basepress_utils->is_knowledgebase || $basepress_utils->is_products_page )
						return $instance;
					}

					$is_page = array();
					foreach( $instance['visible_in'] as $page ){
						if( 'other' == $page ){
							continue;
						}
						$variable_name = "is_{$page}";
						$is_page[$variable_name] = $basepress_utils->$variable_name;
						if( $basepress_utils->$variable_name ){
							return $instance;
						}
					}
					return false;
				}
			}
			return $instance;
		}


		public function render_limit_fields( $instance,  &$widget ){
			echo '<hr>';

			echo '<h4>' . esc_html__( 'Visible in:', 'basepress' ) . '</h4>';
			echo '<p>';

			$visible_in = ! empty( $instance['visible_in'] ) ? $instance['visible_in'] : array_keys( $this->visibility_pages );
			$field_name = esc_attr( $widget->get_field_name( 'visible_in' ) );
			$field_id = esc_attr( $widget->get_field_id( 'visible_in' ) );

			$index = 0;
			foreach( $this->visibility_pages as $page => $label ){
				$label = esc_attr__( $label, 'basepress' );
				$checked = in_array( $page, $visible_in ) ? ' checked' : '';

				echo "<input class='checkbox' id='{$field_id}-{$index}' name='{$field_name}[]' type='checkbox' value='{$page}'{$checked}>"; // phpcs:ignore Standard.Category.SniffName.ErrorCode
				echo "<label for='{$field_name}-{$index}'>{$label}</label>"; // phpcs:ignore Standard.Category.SniffName.ErrorCode
				echo '<br>';
				$index++;
			}

			echo '</p>';
		}
	} // End of Class

	new Basepress_Widget_Visibility();
}

<?php
/**
 * This is the class that creates and handles the Table of content widget
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Basepress_TOC_Widget extends WP_Widget {

	/**
	 * Basepress_TOC_Widget constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		parent::__construct(
			'basepress_toc_widget', // Base ID
			esc_html__( 'knowledge Base - Table of Content', 'basepress' ), // Name
			array(
				'description' => esc_html__( 'Generates a Table of content for the current article.', 'basepress' ),
			)
		);
	}




	/**
	 * Front-end display of widget.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		//Execute Widget only if it is a single knowledge base article
		if ( is_singular( 'knowledgebase' ) ) {

			if ( isset( $GLOBALS['basepress-widget-toc'] ) ) {
				echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				if ( ! empty( $instance['title'] ) ) {
					echo $args['before_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo esc_html( apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance ) );
					echo $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				$highlight = isset( $instance['highlighting'] ) && ! empty( $instance['highlighting'] ) ? ' data-highlight="true"' : '';

				echo "<div class='bpress-toc-list'{$highlight}>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wp_kses_post( $this->get_toc( $instance ) );
				echo '</div>';

				echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}


	/**
	 * Back-end widget form.
	 *
	 * @since 1.2.0
	 *
	 * @param array $instance
	 * @return string|void
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$highlighting = ! empty( $instance['highlighting'] ) ? $instance['highlighting'] : '';

		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'basepress' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<input class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'highlighting' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'highlighting' ) ); ?>" type="checkbox" value="1" <?php checked( $highlighting, 1 ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'highlighting' ) ); ?>"><?php esc_attr_e( 'Highlight items on page scroll', 'basepress' ); ?></label>
		</p>
		<?php
	}




	/**
	 * Sanitizes widget form values as they are saved.
	 *
	 * @since 1.2.0
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['highlighting'] = ( ! empty( $new_instance['highlighting'] ) ) ? strip_tags( $new_instance['highlighting'] ) : '';

		return $instance;
	}




	/**
	 * Generates the Table of content from $GLOBALS
	 *
	 * @since 1.2.0
	 *
	 * @param $instance
	 */
	private function get_toc( $instance ) {
		if ( isset( $GLOBALS['basepress-widget-toc'] ) ) {
			echo $GLOBALS['basepress-widget-toc']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}

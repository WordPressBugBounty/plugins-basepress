<?php
/**
 * Tags cloud Widget
 */

class Basepress_Tags_Widget extends WP_Widget {

	/**
	 * Sets up a new Tag Cloud widget instance.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description'                 => esc_html__( 'A cloud of your most used Knowledge Base tags.', 'basepress' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'basepress_tag_cloud', esc_html__( 'Knowledge Base - Tag Cloud', 'basepress' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Tag Cloud widget instance.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		global $basepress_utils;

		//If the widget is called outside of the knowledge base pages return
		if ( ! $basepress_utils->is_knowledgebase ) {
			return;
		}

		//Fetch the list of popular articles
		$tags = $this->get_knowledgebase_tags( $instance );

		//Render the widget only if we have some articles
		if ( '' != $tags ) {
			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo esc_html( apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance ) );
				echo $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo wp_kses_post( $tags );

			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}


	/**
	 * Sanitizes widget form values as they are saved.
	 *
	 * @since 2.7.0
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = array();
		$instance['title']    = sanitize_text_field( $new_instance['title'] );
		$instance['count']    = ! empty( $new_instance['count'] ) ? 1 : 0;
		return $instance;
	}


	/**
	 * Outputs the Tag Cloud widget settings form.
	 *
	 * @since 2.7.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title_id          = $this->get_field_id( 'title' );
		$count             = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$instance['title'] = ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

		echo '<p><label for="' . esc_attr( $title_id ) . '">' . esc_html__( 'Title:' ) . '</label>
			<input type="text" class="widefat" id="' . esc_attr( $title_id ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" value="' . esc_attr( $instance['title'] ) . '" />
		</p>';

		printf(
			'<p><input type="checkbox" class="checkbox" id="%1$s" name="%2$s"%3$s /> <label for="%1$s">%4$s</label></p>',
			esc_attr( $this->get_field_id( 'count' ) ),
			esc_attr( $this->get_field_name( 'count' ) ),
			checked( $count, true, false ),
			esc_html__( 'Show tag counts' )
		);
	}

	/**
	 * Generates the related articles list
	 *
	 * @since 2.7.0
	 *
	 * @param $instance
	 * @return string
	 */
	private function get_knowledgebase_tags( $instance ) {

		global $basepress_utils, $wpdb;

		$product = $basepress_utils->get_product();

		if( empty( $product->id ) ){
			return false;
		}

		$terms = get_term_children( $product->id, 'knowledgebase_cat' );

		if( empty( $terms ) ) return;

		/**
		 * Filters the list of terms to filter the tags
		 */
		$query_args = array(
			'terms'       => $terms,
			'extra_join'  => '',
			'extra_where' => '',
			'product'     => $product
		);

		/**
		 * Filters the arguments for the tag widget query
		 */
		$query_args = apply_filters( 'basepress_tags_widget_query_args', $query_args );

		$terms = implode( ',', $query_args['terms'] );

		$query = "SELECT DISTINCT terms2.term_id AS tag_id, p1.ID
              FROM
		            $wpdb->posts AS p1
		            LEFT JOIN $wpdb->term_relationships AS r1 ON p1.ID = r1.object_ID
		            LEFT JOIN $wpdb->term_taxonomy AS t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
		            LEFT JOIN $wpdb->terms AS terms1 ON t1.term_id = terms1.term_id
		         ";
		$query .= ' ' . $query_args['extra_join'] . ',';

		$query .= "$wpdb->posts AS p2
		            LEFT JOIN $wpdb->term_relationships AS r2 ON p2.ID = r2.object_ID
		            LEFT JOIN $wpdb->term_taxonomy AS t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
		            LEFT JOIN $wpdb->terms AS terms2 ON t2.term_id = terms2.term_id
		          ";

		$query .= "WHERE
		            p1.post_type = 'knowledgebase'
		            AND t1.taxonomy = 'knowledgebase_cat'
		            AND p1.post_status = 'publish'
		            AND terms1.term_id IN ($terms)
		            AND p2.post_type = 'knowledgebase'
		            AND t2.taxonomy = 'knowledgebase_tag'
		            AND p2.post_status = 'publish'";

		$query .= ' ' . $query_args['extra_where'] . ' ';

		$query .= " AND p1.ID = p2.ID";

		$query .= " ORDER by tag_id";

		$tags = $wpdb->get_results( $query );

		if( empty( $tags ) ){
			return '';
		}

		$tags = wp_list_pluck( $tags, 'tag_id' );

		$show_count = ! empty( $instance['count'] );

		/**
		 * Filter the Tags count to limit it to the current knowledge base
		 */
		add_filter( 'tag_cloud_sort', function( $_tags, $args ) use( $tags ){
			if( ! isset( $args['basepress_tags' ] ) ){
				return;
			}

			$tag_counts = array_count_values( $tags );
			if( ! empty( $tag_counts ) ){
				foreach( $_tags as $index => $_tag ){
					$_tags[$index]->count = $tag_counts[$_tag->term_id];
				}
			}
			return $_tags;
		}, 10, 2 );

		//Get the final tag cloud
		$tag_cloud = wp_tag_cloud(
			apply_filters(
				'basepress_widget_tag_cloud_args',
				array(
					'taxonomy'   => 'knowledgebase_tag',
					'show_count' => $show_count,
					'include'    => $tags,
					'format'     => 'list',
					'largest'    => 1,
					'smallest'   => 1,
					'unit'       => 'em',
					'echo'       => false,
					'basepress_tags' => true,
				),
				$instance
			)
		);

		return $tag_cloud;
	}
}
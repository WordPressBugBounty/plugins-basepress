<?php
/**
 * This is the class that creates and handles the popular articles widget
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Basepress_Popular_Articles_Widget extends WP_Widget {

	/**
	 * Basepress_Popular_Articles_Widget constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			'basepress_popular_articles_widget', // Base ID
			esc_html__( 'Knowledge Base - Popular Articles', 'basepress' ), // Name
			array(
				'description' => esc_html__( 'Lists articles with most views and/or best score for the current product in the Knowledge Base', 'basepress' ),
			)
		);
	}



	/**
	 * Front-end display of widget.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		if( defined( 'REST_REQUEST' ) ){
			return '';
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo esc_html( apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance ) );
			echo $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		wp_kses_post( $this->get_articles_list( $instance ) );

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Back-end widget form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance
	 * @return string|void
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$count = ! empty( $instance['count'] ) ? $instance['count'] : '';
		$order_by = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'views';

		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'basepress' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_attr_e( 'Number of articles to show:', 'basepress' ); ?></label> 
		<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" value="<?php echo esc_attr( $count ); ?>" size="3">
		</p>

		<p>
			<input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" value="views" <?php checked( $order_by, 'views' ); ?>><?php esc_html_e( 'Views', 'basepress' ); ?><br>
			<input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" value="score" <?php checked( $order_by, 'score' ); ?>><?php esc_html_e( 'Score', 'basepress' ); ?>
		</p>
		<?php
	}



	/**
	 * Sanitizes widget form values as they are saved.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? wp_strip_all_tags( $new_instance['count'] ) : '';
		$instance['orderby'] = ! empty( $new_instance['orderby'] ) ? $new_instance['orderby'] : 'views';

		return $instance;
	}



	/**
	 * Generates the popular articles list
	 *
	 * @since 1.0.0
	 *
	 * @param $instance
	 */
	private function get_articles_list( $instance ) {
		global $basepress_utils, $post, $wpdb;

		$options = $basepress_utils->get_options();
		$product = $basepress_utils->get_product();
		$orderby_1 = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'views';
		$orderby_2 = $orderby_1 == 'views' ? 'score' : 'views';
		$not_in = '0';
		$count = isset( $instance['count'] ) && ! empty( $instance['count'] ) ? $instance['count'] : 99;
		$post_icons = $basepress_utils->icons->post;
		$show_icons = basepress_show_post_icon();
		$classes = '';

		//Gets the terms. Product id if it exists or all sections ids instead.
		$terms_parent = $product->id ? $product->id : 0;
		$terms_args = array(
			'taxonomy' => 'knowledgebase_cat',
			'hide_empty' => false,
			'fields'      => 'tt_ids',
			'child_of'     => $terms_parent
		);

		/**
		 * Filter the arguments used to query the taxonomy terms
		 */
		$terms_args = apply_filters( 'basepress_popular_articles_terms_args', $terms_args );

		$terms = get_terms( $terms_args );

		if( empty( $terms ) ) return;

		$terms = ! empty( $terms ) ? implode( ',', $terms ) : '';

		//Exclude current post if it is not an archive
		if ( isset( $post->ID ) && ! is_archive() ) {
			$not_in = $post->ID;
		}

		//Set the order for the query
		switch( $orderby_1 ){
			case 'views':
				$meta_key_1 = 'basepress_views';
				$meta_key_2 = 'basepress_score';
				break;
			case 'score':
				$meta_key_1 = 'basepress_score';
				$meta_key_2 = 'basepress_views';
				break;
		}

		$query_pieces = array(
			'select' => "SELECT {$wpdb->posts}.* FROM {$wpdb->posts} ",
			'join'  => "",
			'where'  => " WHERE 1=1",
			'groupby' => " GROUP BY {$wpdb->posts}.ID",
			'orderby'   => " ORDER BY IFNULL($orderby_1.meta_value+0, 0) DESC, IFNULL($orderby_2.meta_value+0, 0) DESC",
			'limit'   => " LIMIT 0, {$count}"
		);

		$query_pieces['join'] .= "LEFT JOIN {$wpdb->term_relationships} AS tr ON ( {$wpdb->posts}.ID = tr.object_id )";
		$query_pieces['join'] .= " LEFT JOIN {$wpdb->postmeta} AS $orderby_1 ON ({$wpdb->posts}.ID = $orderby_1.post_id AND $orderby_1.meta_key = '$meta_key_1')";
		$query_pieces['join'] .= " LEFT JOIN {$wpdb->postmeta} AS $orderby_2 ON ( {$wpdb->posts}.ID = $orderby_2.post_id AND $orderby_2.meta_key = '$meta_key_2')";
		$query_pieces['where'] .= $wpdb->prepare( " AND {$wpdb->posts}.ID NOT IN (%s)", $not_in );
		$query_pieces['where'] .= " AND tr.term_taxonomy_id IN ($terms)";
		$query_pieces['where'] .= " AND {$wpdb->posts}.post_status = 'publish'";
		$query_pieces['where'] .= " AND $orderby_1.meta_value > 0";
		$query_pieces['where'] .= " AND $orderby_2.meta_value >= 0";

		/**
		 * Filter to modify the query pieces to query the popular articles
		 */
		$query_pieces = apply_filters( 'basepress_popular_articles_query_pieces', $query_pieces, $instance );

		$query = $query_pieces['select'] . $query_pieces['join'] . $query_pieces['where'] . $query_pieces['groupby'] . $query_pieces['orderby'] . $query_pieces['limit'];

		$popular = $wpdb->get_results( $query );

		// loop over query
		if ( ! empty( $popular ) && ! is_wp_error( $popular ) ) {
			echo '<ul class="bpress-widget-list">';
			foreach( $popular as $post ) {
				setup_postdata( $post );

				$post_icon = '';
				if ( $show_icons ) {
					$post_icon = get_post_meta( get_the_ID(), 'basepress_post_icon', true );
					$post_icon = $post_icon ? $post_icon : $post_icons->default;
					$classes = ' show-icon';
				}

				echo '<li class="bpress-widget-item most-viewed' . esc_attr( $classes ) . '">';

				if ( isset( $options['show_post_icon'] ) ) {
					echo '<span aria-hidden="true" class="' . esc_attr( $post_icon ) . ' bpress-widget-icon"></span>';
				}
				echo '<a href="' . esc_url( get_the_permalink() ) . '" title="' . the_title_attribute( array( 'echo' => false ) ) . '">';
				echo esc_html( get_the_title() ) . '</a>';
				echo '</li>';

			};
			echo '</ul>';
		};
		// Reset Post Data
		wp_reset_postdata();

	}
}
?>
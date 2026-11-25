<?php
/**
 * This is the class to handle all aspect of articles navigation
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Adjacent_Articles' ) ) {

	class Basepress_Adjacent_Articles {

		private $icons;

		private $is_custom_order;

		public function __construct() {

		}



		/**
		 * Get Previous and Next articles
		 *
		 * @since 1.2.0
		 * @updated 1.6.0, 1.7.5
		 *
		 * @param bool $previous
		 * @return bool|void
		 */
		public function get_adjacent_articles( $previous = true ) {
			global $basepress_utils, $post, $wpdb;

			if ( is_preview() ) {
				return;
			}

			$icons = $basepress_utils->icons;
			$options = $basepress_utils->get_options();

			//Get the Section ID
			$section = get_the_terms( $post->ID, 'knowledgebase_cat' );
			if( ! $section ){
				return;
			}
			$section_id = $section[0]->term_taxonomy_id;

			//Determine if the articles in the section have a custom order
			if( ! isset( $this->is_custom_order ) ){
				$this->is_custom_order = $wpdb->get_results(
					"SELECT ID
				FROM $wpdb->posts
				LEFT JOIN $wpdb->term_relationships AS t ON $wpdb->posts.ID = t.object_id
				WHERE t.term_taxonomy_id = $section_id
				AND $wpdb->posts.post_type = 'knowledgebase'
				AND $wpdb->posts.post_status = 'publish'
				AND $wpdb->posts.menu_order <> 0
				LIMIT 1
				"
				);
			}

			if( empty( $this->is_custom_order ) ){

				//Determine previous and next position
				$post_orderby = isset( $options['posts_orderby'] ) && ! empty( $options['posts_orderby'] ) ? $options['posts_orderby'] : 'date_asc';

				switch( $post_orderby ){
					case 'date_asc':
						$compare = $previous ? '<' : '>';
						$compare_item = "$wpdb->posts.post_date";
						$compare_value = $post->post_date;
						$order = $previous ? 'DESC' : 'ASC';
						break;
					case 'date_desc' :
						$compare = $previous ? '>' : '<';
						$compare_item = "$wpdb->posts.post_date";
						$compare_value = $post->post_date;
						$order = $previous ? 'ASC' : 'DESC';
						break;
					case 'alpha_asc':
						$compare = $previous ? '<' : '>';
						$compare_item = "$wpdb->posts.post_title";
						$compare_value = $post->post_title;
						$order = $previous ? 'DESC' : 'ASC';
						break;
					case 'alpha_desc':
						$compare = $previous ? '>' : '<';
						$compare_item = "$wpdb->posts.post_title";
						$compare_value = $post->post_title;
						$order = $previous ? 'ASC' : 'DESC';
						break;
				}
			}
			else{
				$compare = $previous ? '<' : '>';
				$compare_item = "$wpdb->posts.menu_order";
				$compare_value = $post->menu_order;
				$order = $previous ? 'DESC' : 'ASC';
			}

			$orderby = "ORDER BY {$compare_item} {$order}";

			//Query database for previous or next post
			$query_pieces = array(
				'join'  => "LEFT JOIN $wpdb->term_relationships AS t ON $wpdb->posts.ID = t.object_id",
				'where' => "
					WHERE t.term_taxonomy_id=$section_id
					AND $wpdb->posts.post_type = 'knowledgebase'
					AND $wpdb->posts.post_status = 'publish'
					AND ( $compare_item {$compare} '" . addslashes( $compare_value ) . "' )
				",
				"orderby" => "$orderby",
				"order" => $order
			);

			/**
			 * Filter to modify the adjacent articles query
			 */
			$query_pieces = apply_filters( 'basepress_adjacent_articles_args', $query_pieces, $previous );

			$query = "
				SELECT $wpdb->posts.*
				FROM $wpdb->posts
				{$query_pieces['join']}
				{$query_pieces['where']}
				{$query_pieces['orderby']}
				LIMIT 1
			";

			$results = $wpdb->get_results( $query );

			//If there are posts
			if ( $results ) {

				$adjacent_article = $results[0];
				$post_icon = get_post_meta( $adjacent_article->ID, 'basepress_post_icon', true );
				$adjacent_article->icon = $post_icon ? $post_icon : $icons->post->default;

				return $adjacent_article;
			}

			return false;
		}
	}// End Class


	global $basepress_adjacent_articles;
	$basepress_adjacent_articles = new Basepress_Adjacent_Articles;

}

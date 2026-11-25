<?php
/**
 * This is the class that creates and handles the Table fo Contents for articles body and widget
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Table_Of_Content' ) ) {

	#[\AllowDynamicProperties]
	class Basepress_Table_Of_Content {

		private $is_already_done = false;

		private $current_page = 1;

		private $skip_toc = true;

		/**
		 * basepress_toc constructor.
		 *
		 * @since 1.2.0
		 * 
		 * @updated 1.7.4
		 */
		public function __construct() {
			global $basepress_utils;

			$this->options = $basepress_utils->get_options();

			if ( is_active_widget( false, false, 'basepress_toc_widget', true )
			|| isset( $this->options['show_toc'] ) ) {

				if ( ! is_admin() ) {
					add_filter( 'content_pagination', array( $this, 'table_of_contents' ), 10, 2 );
					add_filter( 'the_content', array( $this, 'add_header_anchors' ), 99 );
				}

				if ( is_admin() ){
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ));
					add_action( 'save_post', array( $this, 'save_meta_box' ));
				}
			}

			if ( ! is_admin() ) {
				$current_post_id = $this->get_early_postid();
				if ( ! empty( $current_post_id ) ) {
					$basepress_toc_toggle_value = get_post_meta( $current_post_id, 'basepress_toc_toggle', true );
					if( ( 1 != $basepress_utils->get_option( 'show_toc' ) && 1 == $basepress_utils->get_option( 'show_toc_totop' ) )
						|| ( 1 == $basepress_utils->get_option( 'show_toc' ) && 1 == $basepress_utils->get_option( 'show_toc_totop' ) && ! empty( $basepress_toc_toggle_value ) ) ){
						add_filter( 'content_pagination', array( $this, 'table_of_contents_without_toc' ), 10, 2 );
						add_filter( 'the_content', array( $this, 'add_header_anchors_tag_without_toc' ), 5 );
						add_filter( 'the_content', array( $this, 'add_header_anchors_without_toc' ), 98 );
					}
				}

				if( ! empty( $basepress_utils->get_option( 'show_copy_anchors_links' ) ) ){
					add_filter( 'the_content', array( $this, 'add_hyperlink_buttons' ), 99 );
				}
			}
		}

		/**
		 * Note: This function will only work on SINGULAR posts/pages/post types.
		 */
		public function get_early_postid() {
			$url        = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . ( array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : '' ) . ( array_key_exists( 'REQUEST_URI', $_SERVER ) ? $_SERVER['REQUEST_URI'] : '' );
			$link_array = explode( '/', rtrim( $url, '/' ) );
			$page_slug  = end( $link_array );
			$post_data  = get_page_by_path( $page_slug, OBJECT, 'knowledgebase' );
			if ( ! empty( $post_data ) ) {
				return $post_data->ID;
			}
			return 0;
		}

		public function table_of_contents_without_toc( $pages, $post ) {
			global $posts, $basepress_utils;

			$this->skip_toc = get_post_meta( $post->ID, 'basepress_toc_toggle', true );
			if ( ! $this->skip_toc || 1 == $basepress_utils->get_option( 'show_toc_totop' ) ) {
				$this->skip_toc = true;
			}
			//Exit if the TOC is disabled for this article
			if( $this->skip_toc ){
				return $pages;
			}

			return $pages;
		}

		/**
		 * Generate Table of Content and new modified post content with links for TOC
		 *
		 * @since 1.2.0
		 *
		 * @param $pages
		 * @param $post
		 * @return mixed
		 */
		public function table_of_contents( $pages, $post ) {
			global $posts;

			if ( ! is_singular( 'knowledgebase' ) || $this->is_already_done ) {
				return $pages;
			}

			$is_main_post = $posts[0]->ID == $post->ID;

			//Execute only if this is knowledge base post and the content is not empty
			if ( 'knowledgebase' == $post->post_type
				&& '' != $post->post_content
				&& $is_main_post
				&& ! isset( $GLOBALS['basepress-widget-toc'] ) ) {

				$this->skip_toc = get_post_meta( $post->ID, 'basepress_toc_toggle', true );
				
				//Exit if the TOC is disabled for this article
				if( $this->skip_toc ){
					return $pages;
				}

				$toc = ''; //Stores ToC for article body
				$widget_toc = ''; //Stores ToC for widget
				$list_tag = isset( $this->options['show_toc_numbers'] ) ? 'ol' : 'ul';
				$post_permalink = get_permalink( $post->ID );

				$last_level = -1;
				$page_index = 1;
				$current_page = $this->current_page = ! isset( $GLOBALS['page'] ) || ! is_numeric( $GLOBALS['page'] ) ? 1 : (int)$GLOBALS['page'];

				//Iterates over each page
				foreach ( $pages as $page ) {
					//Set the page url for the ToC link according to permalink structure
					//If the link refers to a title in the current page leave empty
					if ( get_option( 'permalink_structure' ) ) {
						$page_url = $page_index == $current_page ? '' : trailingslashit( $post_permalink ) . $page_index . '/';
					} else {
						$page_url = $page_index == $current_page ? '' : trailingslashit( $post_permalink ) . '&page=' . $page_index;
					}

					//Render blocks before getting the headings
					if( function_exists( 'has_blocks' ) && has_blocks( $page ) ){
						$page = do_blocks( $page );
					}

					//Collect all headings
					$headings = $this->get_headings_from_content( $page );

					if ( count( $headings[0] ) == 0 ) {
						break;
					}
					//Create an index of found matches for the 'for' loop
					$index = count( $headings[0] ) - 1;

					/*
						* Create nested list for TOC
						*/
					for ( $i = 0; $i <= $index; $i++ ) {
						$item_index = $i + 1;
						$data_index = "{$page_index}.{$item_index}";
						//Get the heading level from the matches
						$level = $headings[1][ $i ][0];

						//Add an item on the table of content
						if ( $level > $last_level ) {
							$toc .= '<' . $list_tag . '>';
							$widget_toc .= '<' . $list_tag . '>';
						} else {
							$toc .= str_repeat( '</li></' . $list_tag . '>', $last_level - $level );
							$toc .= '</li>';
							$widget_toc .= str_repeat( '</li></' . $list_tag . '>', $last_level - $level );
							$widget_toc .= '</li>';
						}
						$toc .= '<li>';
						$widget_toc .= '<li>';

						//We use strip_tags to remove possible HTML tags inside the headings that would invalidate the links
						$heading = $this->get_slug_from_heading( $headings[2][ $i ][0], $i + 1 );

						$toc .= '<a href="' . $page_url . '#' . $heading . '" data-index="' . $data_index . '">' . wp_strip_all_tags( $headings[2][ $i ][0] ) . '</a>';
						$widget_toc .= '<a href="' . $page_url . '#' . $heading . '" class="bpress-widget-item" data-index="' . $data_index . '">' . wp_strip_all_tags( $headings[2][ $i ][0] ) . '</a>';

						$last_level = $level;
					}

					$page_index += 1;
				}

				//If there were some headings close the list
				if ( count( $headings[0] ) != 0 ) {
					$toc .= '</li></ol>';
					$widget_toc .= '</li></ol>';
				}

				//If we have the ToC save it in globals
				if ( isset( $this->options['show_toc'] ) && '' != $toc ) {
					$GLOBALS['basepress-toc'] = $toc;
				}
				if ( '' != $widget_toc ) {
					$GLOBALS['basepress-widget-toc'] = $widget_toc;
				}
				$this->is_already_done = true;
			}

			return $pages;
		}



		/**
		 * Add the ToC anchors to the article's content
		 *
		 * @since 2.10.6
		 *
		 * @param $content
		 * @return mixed|string
		 */
		function add_header_anchors( $content ){

			if( ! $this->is_already_done || ! is_singular( 'knowledgebase') || ! in_the_loop() || $this->skip_toc ){
				return $content;
			}

			$page_index = $this->current_page;

			//Collect all headings
			$headings = $this->get_headings_from_content( $content );

			if( ! empty( $headings[0] ) ){
				$headings_count = count( $headings[0] );
				$heading_index = $headings_count - 1;

				$totop_text = isset( $this->options['toc_totop_text'] ) ? $this->options['toc_totop_text'] : '';
				$to_top_elements = isset( $this->options['show_toc_totop'] ) ? '<div class="bpress-totop-wrap"><a href="#bp-toc-top" class="bpress-totop">' . $totop_text . '</a></div>' : '';

				for( $i = $heading_index; $i >= 0; $i-- ){
					$item_index = $i + 1;
					$data_index = "{$page_index}.{$item_index}";

					//get the position of the heading in the original content
					$start_pos = $headings[0][ $i ][1];

					$to_top = $i > 0 ? $to_top_elements : '';

					//Get the heading text
					$heading = $this->get_slug_from_heading( $headings[2][ $i ][0], $i + 1 );

					//then add the anchor

					$anchor = $to_top . '<a name="' . $heading . '" class="bpress-anchor-link" data-index="' . $data_index . '"></a>';
					$content = substr_replace( $content, $anchor, $start_pos, 0 );
				}

				$content .= $to_top_elements;

				//Add an extra anchor at the bottom to find end of page
				$content .= '<div class="bpress-anchor"><a class="bpress-anchor-link" ></a></div>';
			}

			return $content;
		}

		/**
		 * Add the ToC anchors tag to the article's content
		 *
		 * @since 2.10.13
		 *
		 * @param $content
		 * @return mixed|string
		 */
		function add_header_anchors_tag_without_toc( $content ) {
			$anchor_tag = '<a name="bp-toc-top" ></a>';
			$content = $anchor_tag . $content;
			return $content;
		}


		/**
		 * Add the ToC anchors to the article's content
		 *
		 * @since 2.10.13
		 *
		 * @param $content
		 * @return mixed|string
		 */
		function add_header_anchors_without_toc( $content ){

			if( ! is_singular( 'knowledgebase') || ! in_the_loop() || ! $this->skip_toc ){
				return $content;
			}

			$page_index = $this->current_page;

			//Collect all headings
			$headings = $this->get_headings_from_content( $content );

			if( ! empty( $headings[0] ) ){
				$headings_count = count( $headings[0] );
				$heading_index = $headings_count - 1;

				$totop_text = isset( $this->options['toc_totop_text'] ) ? $this->options['toc_totop_text'] : '';
				$to_top_elements = isset( $this->options['show_toc_totop'] ) ? '<div class="bpress-totop-wrap"><a href="#bp-toc-top" class="bpress-totop">' . $totop_text . '</a></div>' : '';

				for( $i = $heading_index; $i >= 0; $i-- ){
					$item_index = $i + 1;
					$data_index = "{$page_index}.{$item_index}";

					//get the position of the heading in the original content
					$start_pos = $headings[0][ $i ][1];

					$to_top = $i > 0 ? $to_top_elements : '';

					//Get the heading text
					$heading = $this->get_slug_from_heading( $headings[2][ $i ][0], $i + 1 );

					//then add the anchor
					$anchor = $to_top . '<a name="' . $heading . '" class="bpress-anchor-link" data-index="' . $data_index . '"></a>';
					$content = substr_replace( $content, $anchor, $start_pos, 0 );
				}

				$content .= $to_top_elements;

				//Add an extra anchor at the bottom to find end of page
				$content .= '<div class="bpress-anchor"><a class="bpress-anchor-link" ></a></div>';
			}

			return $content;
		}


		/**
		 * Adds the Toc Metabox to knowledgebase posts
		 *
		 * @since 1.7.4
		 *
		 * @param mixed $post_type
		 * @return mixed 
		 */
		public function add_meta_box( $post_type ){
			if( $post_type == 'knowledgebase' ) {
				
				//Add BasePress section( sub category ) selector
				add_meta_box(
					'basepress_toc_toggle',
					esc_html__( 'Table of Contents', 'basepress' ),
					array( $this, 'render_meta_box' ),
					$post_type,
					'side',
					'default'
				);
			}
		}


		
		/**
		 * Renders the ToC metabox on the edit post screen
		 *
		 * @since 1.7.4
		 *
		 * @param mixed $post
		 * @return mixed
		 */
		public function render_meta_box( $post ) {
			
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'basepress_toc_toggle_meta', 'basepress_toc_toggle_nonce' );
	
			// Use get_post_meta to retrieve an existing value from the database.
			$value = get_post_meta( $post->ID, 'basepress_toc_toggle', true );
	
			echo '<div id="basepress-toc-toggle">';
			echo '<input type="checkbox" class="basepress-toc-toggle" name="basepress_toc_toggle" value="1" ' . checked( $value, 1, false ) . '></input>';
			echo esc_html__( 'Disable on this article', 'basepress' );
			echo '</div>';
		}



		/**
		 * Saves the metabox value if present
		 *
		 * @since 1.7.4
		 *
		 * @param mixed $post_id
		 * @return mixed
		 */
		public function save_meta_box( $post_id ){
			
			//We need to verify that this came from our screen and with proper authorization,
			//because save_post can be triggered at other times.
	
			// Check if our nonce is set.
			if( ! isset( $_POST['basepress_toc_toggle_nonce'] )){
				return $post_id;
			}
	
			$nonce = sanitize_key( $_POST['basepress_toc_toggle_nonce'] );
	
			// Verify that the nonce is valid.
			if( ! wp_verify_nonce( $nonce, 'basepress_toc_toggle_meta' )){
				 return $post_id;
			}
	
			//If this is an autosave, our form has not been submitted,
			//so we don't want to do anything.
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
				return $post_id;
			}
	
			// Check the user's permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
	
	
			//OK, it's safe for us to save the data now.
			// Update the meta field.
			if( isset( $_POST['basepress_toc_toggle'] )){
				update_post_meta( $post_id, 'basepress_toc_toggle', sanitize_text_field( wp_unslash( $_POST['basepress_toc_toggle'] ) ));
			}
			else{
				delete_post_meta( $post_id, 'basepress_toc_toggle' );
			}
		}


		/**
		 * Add hiperlink buttons to the article headings
		 *
		 * @since 2.15.0
		 *
		 * @param $content
		 * @return string
		 */
		public function add_hyperlink_buttons( $content ){
			global $basepress_utils;

			if( ! is_singular( 'knowledgebase') || ! in_the_loop() ){
				return $content;
			}

			// $content = str_replace( array( "\n", "\r" ), ' ', $content );
			preg_match_all( '/<h([1-6])(?:.*)>(.*)<\/h[1-6]>/Ui', $content, $headings, PREG_OFFSET_CAPTURE );

			if( ! empty( $headings[0] ) ){
				$headings_count = count( $headings[0] );
				$index = $headings_count - 1;

				for( $i = $index; $i >= 0; $i-- ){
					//get the position of the heading in the original content
					$start_pos = $headings[0][ $i ][1];

					//Get the heading text
					$heading = $this->get_slug_from_heading( $headings[2][ $i ][0], $i + 1 );

					$link_content = apply_filters( 'basepress_anchor_link_content', '#' );

					$content = substr_replace( $content, "<a href='#{$heading}' class='bpress-copy-link' aria-hidden='true'>{$link_content}</a>", $start_pos + strlen( $headings[0][ $i ][0] ) - 5, 0 );
				}
			}

			$tooltip_copy_text = $basepress_utils->get_option( 'copy_anchors_links_copy_text' ) ?: esc_html__( 'Copy Link', 'basepress' );
			$tooltip_copied_text = $basepress_utils->get_option( 'copy_anchors_links_copied_text' ) ?: esc_html__( 'Link Copied', 'basepress' );

			$tooltip = "<span class='bpress-hl-tooltip hidden' data-bpress-copy-hl='{$tooltip_copy_text}' data-bpress-copied-hl='{$tooltip_copied_text}'></span>";

			return $content . $tooltip;
		}



		/**
		 * Extract headings from content
		 *
		 * @since 2.14.4
		 *
		 * @param $content
		 * @return mixed
		 */
		public function get_headings_from_content( $content ){
			// $content = str_replace( array( "\n", "\r" ), ' ', $content );
			preg_match_all( '/<h([1-6])(?:.*)>(.*)<\/h[1-6]>/Ui', $content, $headings, PREG_OFFSET_CAPTURE );
			return $headings;
		}


		/**
		 * Cleans headings to be used as anchors
		 *
		 * @since 1.2.0
		 *
		 * @param $heading
		 * @return mixed|string
		 */
		public function get_slug_from_heading( $heading, $index ) {

			$toc_text = apply_filters( 'basepress_toc_anchors_text', false, $heading, $index );

			if( is_string( $toc_text ) ){
				return $toc_text;
			}

			//Remove possible HTML tags inside headings
			$heading = wp_strip_all_tags( $heading );

			//Trim white spaces
			$heading = trim( $heading );

			//Lower case everything
			$heading = function_exists( 'mb_stringtolower' ) ? mb_strtolower( $heading ) : strtolower( $heading );

			//Replace dots with underscores
			$heading = function_exists( 'mb_str_replace' ) ? mb_str_replace( '.', '_', $heading ) : str_replace( '.', '_', $heading );

			//Make alphanumeric (removes all other characters)
			if( function_exists( 'mb_regex_encoding' ) && function_exists( 'mb_convert_encoding' ) ){
				mb_regex_encoding('UTF-8');
				$heading = mb_convert_encoding( $heading, 'UTF-8' );
			}
			$heading = preg_replace( '/[^\w\s_-]+/u', '', $heading );

			$heading = str_replace('8217','',$heading);

			//Convert whitespaces and underscore to dash
			$heading = preg_replace( '/[\s_]/', '-', $heading );

			return $heading;
		}

	}//End Class
	
	new Basepress_Table_Of_Content;
}

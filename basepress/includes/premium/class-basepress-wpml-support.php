<?php
/**
 * This class handles WPML compatibility. It is called only if WPML is active.
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_WPML_Support' ) ) {

	class Basepress_WPML_Support {


		/**
		 * basepress_wpml_support constructor.
		 *
		 * @since 1.5.0
		 */
		public function __construct() {

			add_filter( 'basepress_entry_page', array( $this, 'set_entry_page' ) );

			add_filter( 'basepress_settings_entry_page_save', array( $this, 'set_entry_page_default_language' ) );

			add_action( 'created_knowledgebase_cat', array( $this, 'created_knowledgebase_cat' ), 10, 2 );

			add_action( 'basepress_product_updated', array( $this, 'after_product_update' ), 20, 4 );

			add_action( 'basepress_section_updated', array( $this, 'after_section_update' ), 20, 4 );

			add_action( 'basepress_product_order_updated', array( $this, 'update_items_order' ), 10 );

			add_action( 'basepress_section_order_updated', array( $this, 'update_items_order' ), 10 );

			add_action( 'basepress_article_order_updated', array( $this, 'update_articles_order' ), 10 );

			add_filter( 'basepress_votes_reset', array( $this, 'votes_views_reset' ), 10 );

			add_filter( 'basepress_views_reset', array( $this, 'votes_views_reset' ), 10 );

			add_action( 'basepress_search_fields', array( $this, 'add_search_language_field' ) );

			add_filter( 'basepress_rewrite_rules_kb_slug', array( $this, 'kb_slug_multilingual' ), 10, 2 );

			add_filter( 'basepress_kb_slug', array( $this, 'kb_slug_search_multilingual' ), 10, 2 );

			/**
			 * commented because: https://wpml.org/forums/topic/i-use-basepress-in-multi-knowledge-base-mode-with-wpml/
			 */
			// add_filter( 'icl_ls_languages', array( $this, 'fix_ls_languages_url' ) );

			add_filter( 'basepress_settings_product_terms', array( $this, 'get_product_terms_original_language'));

			add_filter( 'request', array( $this, 'translate_product_in_searches' ) );

			add_action( 'wp_ajax_nopriv_basepress_smart_search', array( $this, 'force_live_search_lang' ), 5 );
			add_action( 'wp_ajax_basepress_smart_search', array( $this, 'force_live_search_lang' ), 5 );

		}

		/**
		 * Translate the product slug in the request for single KB mode
		 *
		 * @since 2.8.0
		 *
		 * @param $request
		 * @return mixed
		 */
		public function translate_product_in_searches( $request ){
			global $basepress_utils;

			if( $basepress_utils->is_knowledgebase && $basepress_utils->is_search && $basepress_utils->is_single_product_mode ){
				$term = get_term_by( 'slug', $request['knowledgebase_cat'], 'knowledgebase_cat' );
				$request['knowledgebase_cat'] = $term->slug;
			}
			return $request;
		}
		

		/**
		 * Fires after WPML creates a translation for a Product or Section
		 *
		 * @since 1.5.0
		 * @updated 1.6.0
		 *
		 * @param $created_term_id
		 */
		public function created_knowledgebase_cat( $term_id, $term_taxonomy_id ) {

			if ( 'wpml_save_term' == $_POST['action'] ) {
				$language_details = apply_filters(
					'wpml_element_language_details',
					null,
					array(
						'element_id' => $term_taxonomy_id,
						'element_type' => 'knowledgebase_cat',
					)
				);
				$original_term_id = apply_filters(
					'wpml_object_id', $term_id,
					'knowledgebase_cat',
					false,
					$language_details->source_language_code
				);

				if ( $original_term_id ) {
					do_action( 'wpml_switch_language', $language_details->source_language_code );
					$original_term = get_term( $original_term_id, 'knowledgebase_cat' );
					do_action( 'wpml_switch_language', $language_details->language_code );
					$term_metadatas = get_term_meta( $original_term_id, '', true );

					$original_parent = $original_term->parent;
					$new_parent = apply_filters(
						'wpml_object_id',
						$original_parent,
						'knowledgebase_cat',
						false,
						$language_details->language_code
					);

					$new_parent = $new_parent ? $new_parent : 0;

					wp_update_term( $term_id, 'knowledgebase_cat', array( 'parent' => $new_parent ) );

					foreach ( $term_metadatas as $key => $value ) {
						if ( 'image' == $key || 'sections_style' == $key ) {
							$value[0] = maybe_unserialize( $value[0] );
						}
						update_term_meta( $term_id, $key, $value[0] );
					}
				}
			}
		}



		/**
		 * Synchronizes product meta data with all translations on product update
		 *
		 * @since 1.5.0
		 *
		 * @param $term
		 * @param $term_image
		 * @param $sections_style
		 * @param $term_position
		 */
		public function after_product_update( $term, $term_image, $sections_style, $term_position ) {
			$languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
			$term_ids = array();

			//Get the translated term ids
			foreach ( $languages as $language ) {
				$new_id = apply_filters( 'wpml_object_id', $term->term_id, 'knowledgebase_cat', false, $language['language_code'] );
				if ( $new_id && $new_id != $term->term_id ) {
					$term_ids[] = $new_id;
					$new_id = null;
				}
			}

			foreach ( $term_ids as $term_id ) {

				//Allow developers to skip copying the image to section translations
				if( apply_filters( 'basepress_wpml_sync_product_image', true ) ){
					//update product image
					update_term_meta(
						$term_id,
						'image',
						array(
							'image_url'    => $term_image['image_url'],
							'image_width'  => $term_image['image_width'],
							'image_height' => $term_image['image_height'],
						)
					);
				}

				//Update product sections style
				update_term_meta(
					$term_id,
					'sections_style',
					array(
						'sections'     => $sections_style['sections'],
						'sub_sections' => $sections_style['sub_sections'],
					)
				);

				//update section position
				update_term_meta(
					$term_id,
					'basepress_position',
					$term_position
				);

				//If the restriction roles were saved for this product they were cached in the content restriction class.
				global $basepress_content_restriction;
				if( isset( $basepress_content_restriction ) && ! empty( $basepress_content_restriction->saved_term_meta ) ){
					if( $basepress_content_restriction->saved_term_meta['term_id'] == $term->term_id )
						update_term_meta(
							$term_id,
							'basepress_restriction_roles',
							$basepress_content_restriction->saved_term_meta['restriction_roles']
						);
				}
			}
		}



		/**
		 * Synchronizes section meta data with all translations on section update
		 *
		 * @since 1.5.0
		 *
		 * @param $term
		 * @param $term_icon
		 * @param $term_image
		 * @param $term_position
		 */
		public function after_section_update( $term, $term_icon, $term_image, $term_position ) {
			global $wpdb;

			$languages = apply_filters(
				'wpml_active_languages',
				null,
				'orderby=id&order=desc'
			);

			$term_ids = array();

			//Get the translated term ids
			foreach ( $languages as $language ) {
				$new_id = apply_filters( 'wpml_object_id', $term->term_id, 'knowledgebase_cat', false, $language['language_code'] );
				if ( $new_id && $new_id != $term->term_id ) {
					$term_ids[] = $new_id;
					$new_id = null;
				}
			}

			foreach ( $term_ids as $term_id ) {

				//update parent
				$language_details = apply_filters(
					'wpml_element_language_details',
					null,
					array(
						'element_id' => $term_id,
						'element_type' => 'knowledgebase_cat',
					)
				);

				$new_parent = apply_filters( 'wpml_object_id', $term->parent, 'knowledgebase_cat', false, $language_details->language_code );

				if ( $new_parent ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->term_taxonomy} AS t SET t.parent = %d WHERE t.term_id = %d", absint( $new_parent ), absint( $term_id ) ) );
				}

				//update section icon
				update_term_meta(
					$term_id,
					'icon',
					$term_icon
				);

				//Allow developers to skip copying the image to section translations
				if( apply_filters( 'basepress_wpml_sync_section_image', true ) ){
					//update section image
					update_term_meta(
						$term_id,
						'image',
						array(
							'image_url'    => $term_image['image_url'],
							'image_width'  => $term_image['image_width'],
							'image_height' => $term_image['image_height'],
						)
					);
				}

				//update section position
				update_term_meta(
					$term_id,
					'basepress_position',
					$term_position
				);

				//If the restriction roles were saved for this product they were cached in the content restriction class.
				global $basepress_content_restriction;
				if( isset( $basepress_content_restriction ) && ! empty( $basepress_content_restriction->saved_term_meta ) ){
					if( $basepress_content_restriction->saved_term_meta['term_id'] == $term->term_id )
						update_term_meta(
							$term_id,
							'basepress_restriction_roles',
							$basepress_content_restriction->saved_term_meta['restriction_roles']
						);
				}
			}
		}



		/**
		 * Synchronizes the items order on Products and Sections for all languages
		 *
		 * @since 1.5.0
		 *
		 * @param $order
		 */
		public function update_items_order( $order ) {
			$languages = apply_filters(
				'wpml_active_languages',
				null,
				'orderby=id&order=desc'
			);

			//Update the order of every item per each language
			foreach ( $order as $position => $term_id ) {
				$term_ids = array();

				//Get the translated item ids
				foreach ( $languages as $language ) {
					$new_id = apply_filters( 'wpml_object_id', $term_id, 'knowledgebase_cat', false, $language['language_code'] );
					if ( $new_id && $new_id != $term_id ) {
						$term_ids[] = $new_id;
						$new_id = null;
					}
				}

				//update items order for all found terms
				foreach ( $term_ids as $term_id ) {
					update_term_meta( $term_id, 'basepress_position', $position );
				}
			}
		}



		/**
		 * Synchronizes the articles order for all languages
		 *
		 * @since 1.5.0
		 *
		 * @param $order
		 */
		public function update_articles_order( $order ) {
			global $wpdb;

			$languages = apply_filters(
				'wpml_active_languages',
				null,
				'orderby=id&order=desc'
			);

			//Update the order of every item per each language
			foreach ( $order as $position => $post_id ) {
				$post_ids = array();

				//Get the translated item ids
				foreach ( $languages as $language ) {
					$new_id = apply_filters( 'wpml_object_id', $post_id, 'post', false, $language['language_code'] );
					if ( $new_id && $new_id != $post_id ) {
						$post_ids[] = $new_id;
						$new_id = null;
					}
				}

				//update items order for all found terms
				if ( ! empty( $post_ids ) ) {

					$list = join( ', ', $post_ids );

					$wpdb->query(
						"
						UPDATE $wpdb->posts SET menu_order = $position
						WHERE ID IN ( $list );
						"
					);
				}
			}
		}


		/**
		 * Changes the entry page ID according to the current language
		 *
		 * @since 1.5.0
		 *
		 * @param $entry_page
		 * @return mixed
		 */
		public function set_entry_page( $entry_page ) {
			$new_entry_page = apply_filters( 'wpml_object_id', $entry_page, 'page', true );

			if ( $new_entry_page ) {
				return $new_entry_page;
			}

			return $entry_page;
		}


		/**
		 * Return entry page Id for default language
		 *
		 * @since 1.7.9
		 *
		 * @param $entry_page
		 * @return mixed
		 */
		public function set_entry_page_default_language( $entry_page){
			$new_entry_page = apply_filters( 'wpml_object_id', $entry_page, 'page', true, apply_filters( 'wpml_default_language', '' ) );
			return $new_entry_page;
		}


		/**
		 * Filters the posts to which reset votes and views according to current language
		 *
		 * @since 1.5.0
		 *
		 * @param $query
		 * @return string
		 */
		public function votes_views_reset( $query ) {
			global $wpdb;

			$query = "
				SELECT ID
				FROM $wpdb->posts
				LEFT JOIN {$wpdb->prefix}icl_translations ON 
				($wpdb->posts.ID = {$wpdb->prefix}icl_translations.element_id)
				WHERE post_type = 'knowledgebase'
				AND {$wpdb->prefix}icl_translations.language_code = '" . ICL_LANGUAGE_CODE . "'
			";

			return $query;
		}


		/**
		 * Adds the language field on the search bar to filter searches by current language
		 *
		 * @since 1.5.0
		 */
		public function add_search_language_field() {
			$lang = ICL_LANGUAGE_CODE;
			echo '<input type="hidden" name="lang" value="' . esc_attr( $lang ) . '">';
		}


		/**
		 * Creates a list of entry page slugs in all languages to use in the rewrite rules
		 * See basepress.cpt function rewrite_rules
		 *
		 * @since 1.5.0
		 * @updated 1.7.9
		 *
		 * @param $kb_slug
		 * @param $entry_page
		 * @return string
		 */
		public function kb_slug_multilingual( $kb_slug, $entry_page ){
			$pages_slug = array();

			$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );

			//Get the translated term ids
			foreach( $languages as $language ){
				$page_id = apply_filters( 'wpml_object_id', $entry_page, 'post', false, $language['language_code'] );

				$page_slug = get_post_field( 'post_name', $page_id );

				//Get parent pages slugs
				$parents = get_ancestors( $page_id, 'page' );

				foreach( $parents as $parent ){
					$parent_id = $page_id = apply_filters( 'wpml_object_id', $parent, 'post', false, $language['language_code'] );
					$parent_slug = get_post_field( 'post_name', $parent_id );
					$page_slug = $parent_slug . '/' . $page_slug;
				}

				$pages_slug[] = $page_slug;
			}

			$slugs = implode( '|', $pages_slug );
			$kb_slug = '(?:' . $slugs . ')';

			return $kb_slug;
		}



		/**
		 * Gets the kb_slug for the current language. Used in the search bar.
		 *
		 * @since 1.5.0
		 *
		 * @param $kb_slug
		 * @param $entry_page
		 * @return mixed
		 */
		public function kb_slug_search_multilingual( $kb_slug, $entry_page ){
			$new_entry_page = apply_filters( 'wpml_object_id', $entry_page, 'post', false, ICL_LANGUAGE_CODE );
			$new_entry_page_slug = get_post_field( 'post_name', $new_entry_page );

			//Get parent pages slugs
			$parents = get_ancestors( $new_entry_page, 'page' );

			foreach( $parents as $parent ){
				$parent_id = apply_filters( 'wpml_object_id', $parent, 'post', false );
				$parent_slug = get_post_field( 'post_name', $parent_id );
				$new_entry_page_slug = $parent_slug . '/' . $new_entry_page_slug;
			}

			return $new_entry_page_slug;
		}



		/**
		 * Fixes WPML language switcher in search results.
		 * If we perform a search for all product WPML menu containes %taxonomy%/ in the urls.
		 * This function gets called as a filter for the language selector.
		 *
		 * @since 1.5.0
		 *
		 * @param $w_active_languages
		 * @return mixed
		 */
		public function fix_ls_languages_url( $w_active_languages ){
			global $basepress_utils, $sitepress;

			if( $basepress_utils->is_knowledgebase ){

				$options = $basepress_utils->get_options();

				$current_language = $sitepress->get_current_language();
				$default_entry_page = $options['entry_page'];
				$default_entry_page = apply_filters( 'wpml_object_id', $default_entry_page, 'post', false, $current_language );
				$default_entry_page_slug = get_post_field( 'post_name', $default_entry_page );
				$default_entry_page_slug = $this->add_object_parents_to_slug( $default_entry_page, $default_entry_page_slug );

				foreach( $w_active_languages as $code => $data ){

					$entry_page = apply_filters( 'wpml_object_id', $default_entry_page, 'post', false, $data['code'] );
					$entry_page_slug = get_post_field( 'post_name', $entry_page );
					$entry_page_slug = $this->add_object_parents_to_slug( $entry_page, $entry_page_slug );

					$old_slug = '/' . preg_quote( $default_entry_page_slug, '/' ) . '/';

					//If this is a global search page the correct URL should be the main KB page
					if( $basepress_utils->is_global_search ){
						$data['url'] = apply_filters( 'wpml_permalink', get_permalink( $entry_page ), $code );
					}
					
					$w_active_languages[ $code ]['url'] = preg_replace( $old_slug, $entry_page_slug, $data['url'], 1 );
					$w_active_languages[ $code ]['url'] = str_replace( '%taxonomies%/', '', $w_active_languages[ $code ]['url'] );
				}

				//Remove product name in single product mode
				if( isset( $options['single_product_mode'] )){
					$product = $basepress_utils->get_product();
					foreach( $w_active_languages as $code => $data ){
						$translated_product_id = apply_filters( 'wpml_object_id', $product->id, 'knowledgebase_cat', false, $code );
						if( $translated_product_id ){
							do_action( 'wpml_switch_language', $code );
							$translated_product = get_term( $translated_product_id, 'knowledgebase_cat' );
							$w_active_languages[ $code ]['url'] = str_replace( $translated_product->slug . '/', '', $w_active_languages[ $code ]['url'] );
						}
					}
					do_action( 'wpml_switch_language', ICL_LANGUAGE_CODE );
				}
			}
			return $w_active_languages;
		}


		/**
		 * Adds the page parents slug to the passed slug
		 *
		 * @since 1.7.9
		 *
		 * @param $id
		 * @param $slug
		 * @return string
		 */
		public function add_object_parents_to_slug( $id, $slug ){

			$parents = get_ancestors( $id, 'page' );

			foreach( $parents as $parent ){
				$parent_slug = get_post_field( 'post_name', $parent );
				$slug = $parent_slug . '/' . $slug;
			}

			return $slug;
		}



		/**
		 * Gets the list of products in the default language
		 *
		 * @since 1.7.9
		 *
		 * @param $products_terms
		 * @return mixed
		 */
		public function get_product_terms_original_language( $products_terms ){
			global $sitepress;
			$current_language = $sitepress->get_current_language();
			$default_language = $sitepress->get_default_language();

			if( $current_language != $default_language ){
				do_action( 'wpml_switch_language', $default_language );
				$products_terms = get_terms(
					'knowledgebase_cat',
					array(
						'taxonomy'	=> 'knowledgebase_cat',
						'parent'		=> 0,
						'fields'    => 'ids'
					)
				);
				do_action( 'wpml_switch_language', $current_language );
			}

			return $products_terms;
		}

		/**
		 * Force WPML language for smart search
		 *
		 * @since 2.11.9
		 */
		public function force_live_search_lang(){

			if( sanitize_text_field( wp_unslash( $_REQUEST['lang'] ) ) ){
				do_action( 'wpml_switch_language', sanitize_text_field( wp_unslash( $_REQUEST['lang'] ) ) );
			}
		}
	} //End Class

	global $basepress_wpml_support;
	$basepress_wpml_support = new Basepress_WPML_Support();
}

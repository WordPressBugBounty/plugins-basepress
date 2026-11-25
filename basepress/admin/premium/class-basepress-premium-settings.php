<?php

/**
 * This is the class that handles BasePress Settings in the admin area for premium features
 */

// Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Basepress_Premium_Settings' ) ) {
	
	class Basepress_Premium_Settings {
		private $options = '';

		/**
		* Basepress_Premium_Settings constructor.
		*
		* @since 1.0.0
		*/
		public function __construct() {
			//Initialize options variables;
			if ( isset( $_GET['page'] ) && 'basepress' === $_GET['page'] ) {
				$this->load_options();
				add_action( 'init', array( $this, 'load_options' ), 10 );
			}

			add_action( 'basepress_settings_sections', array( $this, 'settings_sections' ) );

			add_action( 'basepress_settings_fields', array( $this, 'settings_fields' ) );
		}


		/**
		*  Loads and caches plugin options
		*
		*  @since 1.5.0
		*/
		public function load_options() {
			$options = get_option( 'basepress_settings' );
			if ( ! $options && is_multisite() ) {
				$options = get_site_option( 'basepress_settings' );
			}
			$this->options = $options;
		}


		public function settings_sections() {
			//Add votes settings
			add_settings_section( 'basepress_tags_settings', esc_html__( 'Tags', 'basepress' ), '', 'basepress' );

			//Add votes settings
			add_settings_section( 'basepress_votes_settings', esc_html__( 'Votes', 'basepress' ), '', 'basepress' );

			//Add feedback settings
			add_settings_section( 'basepress_feedback_settings', esc_html__( 'Feedback', 'basepress' ), '', 'basepress' );

			//Add Table of Content settings
			add_settings_section( 'basepress_toc_settings', esc_html__( 'Table of Contents', 'basepress' ), '', 'basepress' );

			//Add Notices settings
			add_settings_section( 'basepress_notices_settings', esc_html__( 'Notices', 'basepress' ), '', 'basepress' );

			//Add article navigation settings
			add_settings_section( 'basepress_articles_navigation_settings', esc_html__( 'Articles Navigation', 'basepress' ), '', 'basepress' );

			//Add content restriction settings
			add_settings_section( 'basepress_restriction_settings', esc_html__( 'Content Restriction', 'basepress' ), '', 'basepress' );

			//Add Statistics settings
			add_settings_section( 'basepress_insights_settings', esc_html__( 'Statistics', 'basepress' ), '', 'basepress' );

			//Add delete data settings for multisite
			if ( is_multisite() ) {
				add_settings_section( 'basepress_delete_data', esc_html__( 'Delete Data', 'basepress' ), '', 'basepress' );
			}
		}



		public function settings_fields() {
			//Add settings fields for Tags settings
			add_settings_field( 'tags_slug', esc_html__( 'Tags slug', 'basepress' ), array( $this, 'tags_slug_render' ), 'basepress', 'basepress_tags_settings' );
			add_settings_field( 'tags_page_title', esc_html__( 'Tags page title', 'basepress' ), array( $this, 'tags_page_title_render' ), 'basepress', 'basepress_tags_settings' );
			add_settings_field( 'tags_list_title', esc_html__( 'Tags list title', 'basepress' ), array( $this, 'tags_list_title_render' ), 'basepress', 'basepress_tags_settings' );

			//Add settings fields for VOTING settings
			add_settings_field( 'voting_active', esc_html__( 'Enable article votes', 'basepress' ), array( $this, 'voting_active_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'voting_question', esc_html__( 'Voting Question', 'basepress' ), array( $this, 'voting_question_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'hide_dislike_vote_button', esc_html__( 'Hide dislike button', 'basepress' ), array( $this, 'hide_dislike_vote_button_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'show_votes_count', esc_html__( 'Show votes count in the buttons', 'basepress' ), array( $this, 'show_votes_count_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'show_voting_confirmation', esc_html__( 'Show confirmation message after vote submission', 'basepress' ), array( $this, 'show_voting_confirmation_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'voting_confirmation', esc_html__( 'Vote confirmation message', 'basepress' ), array( $this, 'voting_confirmation_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'already_voted_notice', esc_html__( 'User has already voted notice', 'basepress' ), array( $this, 'already_voted_notice_render' ), 'basepress', 'basepress_votes_settings' );
			add_settings_field( 'hide_buttons_if_voted', esc_html__( 'Hide the voting buttons if the user has already voted', 'basepress' ), array( $this, 'hide_buttons_if_voted_render' ), 'basepress', 'basepress_votes_settings' );
			if( apply_filters( 'basepress_legacy_icons', false )){
				add_settings_field( 'votes_icons', esc_html__( 'Votes icons', 'basepress' ), array( $this, 'votes_icons_render' ), 'basepress', 'basepress_votes_settings' );
			}


			//Add settings fields for FEEDBACK settings
			add_settings_field( 'activate_feedback', esc_html__( 'Enable feedback', 'basepress' ), array( $this, 'activate_feedback_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'show_feedback_form', esc_html__( 'Form visibility', 'basepress' ), array( $this, 'show_feedback_form_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_form_lable', esc_html__( 'Form label', 'basepress' ), array( $this, 'feedback_form_label_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_include_email_field', esc_html__( 'Include email field', 'basepress' ), array( $this, 'feedback_include_email_field_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_email_field_is_required', esc_html__( 'Make email field required', 'basepress' ), array( $this, 'feedback_email_field_is_required_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_email_field_lable', esc_html__( 'Email field label', 'basepress' ), array( $this, 'feedback_email_field_label_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_email_field_placeholder', esc_html__( 'Email field placeholder', 'basepress' ), array( $this, 'feedback_email_field_placeholder_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_privacy_notice', esc_html__( 'Privacy notice', 'basepress' ), array( $this, 'feedback_privacy_notice_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_submit_text', esc_html__( 'Submit button text', 'basepress' ), array( $this, 'feedback_submit_text_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_submit_success_text', esc_html__( 'Message after successful submit', 'basepress' ), array( $this, 'feedback_submit_success_text_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_submit_fail_text', esc_html__( 'Message after failed submit', 'basepress' ), array( $this, 'feedback_submit_fail_text_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_notify_admin', esc_html__( 'Send notice of new feedback to:', 'basepress' ), array( $this, 'feedback_notify_admin_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_notice_email_from', esc_html__( 'Send notice from this email:', 'basepress' ), array( $this, 'feedback_notice_email_from_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_notice_email_subject', esc_html__( 'Notice email subject:', 'basepress' ), array( $this, 'feedback_notice_email_subject_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_notice_email_message', esc_html__( 'Notice email message:', 'basepress' ), array( $this, 'feedback_notice_email_message_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_recaptcha_type', esc_html__( 'Use reCAPTCHA', 'basepress' ), array( $this, 'feedback_recaptcha_type_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_recaptcha_site_key', esc_html__( 'reCAPTCHA Site KEY', 'basepress' ), array( $this, 'feedback_recaptcha_site_key_render' ), 'basepress', 'basepress_feedback_settings' );
			add_settings_field( 'feedback_recaptcha_secret_key', esc_html__( 'reCAPTCHA Secret KEY', 'basepress' ), array( $this, 'feedback_recaptcha_secrete_key_render' ), 'basepress', 'basepress_feedback_settings' );

			//Add settings fields for TABLE OF CONTENT settings
			add_settings_field( 'show_toc', esc_html__( 'Show Table of Content on articles', 'basepress' ), array( $this, 'show_toc_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'toc_title', esc_html__( 'Table of Content title', 'basepress' ), array( $this, 'toc_title_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'show_toc_numbers', esc_html__( 'Show numbers on list items', 'basepress' ), array( $this, 'show_toc_numbers_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'show_toc_totop', esc_html__( 'Show Back to top link', 'basepress' ), array( $this, 'show_toc_totop_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'toc_totop_text', esc_html__( 'Back to top link text', 'basepress' ), array( $this, 'toc_totop_text_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'show_copy_anchors_links', esc_html__( 'Show links to copy headings anchor URL to clipboard', 'basepress' ), array( $this, 'show_copy_anchors_links_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'copy_anchors_links_copy_text', esc_html__( 'Tooltip text for "Copy Link"', 'basepress' ), array( $this, 'copy_anchors_links_copy_text_render' ), 'basepress', 'basepress_toc_settings' );
			add_settings_field( 'copy_anchors_links_copied_text', esc_html__( 'Tooltip text for "Link Copied"', 'basepress' ), array( $this, 'copy_anchors_links_copied_text_render' ), 'basepress', 'basepress_toc_settings' );

			//Add settings fields for NOTICES settings
			add_settings_field( 'show_notice_titles', esc_html__( 'Show Titles', 'basepress' ), array( $this, 'show_notice_titles_render' ), 'basepress', 'basepress_notices_settings' );
			add_settings_field( 'show_notice_icons', esc_html__( 'Show Icons', 'basepress' ), array( $this, 'show_notice_icons_render' ), 'basepress', 'basepress_notices_settings' );
			add_settings_field( 'notice_titles', esc_html__( 'Titles', 'basepress' ), array( $this, 'notice_titles_render' ), 'basepress', 'basepress_notices_settings' );

			//Add settings fields for ARTICLE NAVIGATION settings
			add_settings_field( 'show_adjacent_articles', esc_html__( 'Show adjacent articles', 'basepress' ), array( $this, 'show_adjacent_articles_render' ), 'basepress', 'basepress_articles_navigation_settings' );
			add_settings_field( 'prev_article_text', esc_html__( 'Previous article title', 'basepress' ), array( $this, 'prev_article_text_render' ), 'basepress', 'basepress_articles_navigation_settings' );
			add_settings_field( 'next_article_text', esc_html__( 'Next article title', 'basepress' ), array( $this, 'next_article_text_render' ), 'basepress', 'basepress_articles_navigation_settings' );

			//Add settings fields for CONTENT RESTRICTION
			add_settings_field( 'activate_restrictions', esc_html__( 'Enable content restriction', 'basepress' ), array( $this, 'activate_restrictions_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'restriction_redirected_pages', esc_html__( 'Redirected Pages', 'basepress' ), array( $this, 'restriction_redirected_pages_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'restricted_page_notice', esc_html__( 'Restricted page notice', 'basepress' ), array( $this, 'restricted_page_notice_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'restricted_search_snippet_notice', esc_html__( 'Restricted search snippet notice', 'basepress' ), array( $this, 'restricted_search_snippet_notice_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'show_restricted_teaser', esc_html__( 'Show teaser', 'basepress' ), array( $this, 'show_restricted_teaser_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'restricted_teaser_length', esc_html__( 'Teaser length in characters', 'basepress' ), array( $this, 'restricted_teaser_length_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'restricted_teaser_do_shortcodes', esc_html__( 'Include shortcodes content in the teaser', 'basepress' ), array( $this, 'restricted_teaser_do_shortcodes_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'show_restricted_login', esc_html__( 'Show Login form', 'basepress' ), array( $this, 'show_restricted_login_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'order_roles_alpha', esc_html__( 'Order roles alphabetically', 'basepress' ), array( $this, 'order_roles_alpha_render' ), 'basepress', 'basepress_restriction_settings' );
			add_settings_field( 'show_roles_admin_columns', esc_html__( 'Show admin columns', 'basepress' ), array( $this, 'show_roles_admin_columns_render' ), 'basepress', 'basepress_restriction_settings' );

			//Add setting fields for INSIGHTS
			add_settings_field( 'activate_insights', esc_html__( 'Enable Statistics', 'basepress' ), array( $this, 'activate_insights_render' ), 'basepress', 'basepress_insights_settings' );
			add_settings_field( 'insights_default_time_range', esc_html__( 'Default time range', 'basepress' ), array( $this, 'insights_default_time_range_render' ), 'basepress', 'basepress_insights_settings' );
			add_settings_field( 'insights_max_results', esc_html__( 'Max Results to show per statistic', 'basepress' ), array( $this, 'insights_max_results_render' ), 'basepress', 'basepress_insights_settings' );

			//Add settings fields for DELETE DATA settings for multisite
			if ( is_multisite() ) {
				add_settings_field( 'delete_data', esc_html__( 'Delete all data', 'basepress' ), array( $this, 'delete_data_render' ), 'basepress', 'basepress_delete_data' );
			}
		}

		/*
		* Tags settings fields
		*/

		public function tags_slug_render() {
			$options = $this->options;

			$value = isset( $options['tags_slug'] ) ? $options['tags_slug'] : 'tag';
			echo '<input type="text" name="basepress_settings[tags_slug]" value="' . esc_attr( $value ) . '">';
			echo '<p class="description">' . __( 'The slug will be used in the tag permalinks. The default value is <b>tag</b>.', 'basepress' ) . '</p>'; //phpcs:ignore
		}

		public function tags_page_title_render() {
			$options = $this->options;

			$value = isset( $options['tags_page_title'] ) ? $options['tags_page_title'] : '';
			echo '<input type="text" name="basepress_settings[tags_page_title]" value="' . esc_attr( $value ) . '">';
			echo '<p class="description">' . __( 'This is the title used on the tag pages. Use %tag% where you want to include the tag name.<br>Example "Articles tagged with: %tag%". Leave empty to display the tag name only.', 'basepress' ) . '</p>'; //phpcs:ignore
		}

		public function tags_list_title_render() {
			$options = $this->options;

			$value = isset( $options['tags_list_title'] ) ? $options['tags_list_title'] : '';
			echo '<input type="text" name="basepress_settings[tags_list_title]" value="' . esc_attr( $value ) . '">';
			echo '<p class="description">' . __( 'This is the title used to list the tags for the current article. Default is <b>Tags:</b>.', 'basepress' ) . '</p>'; //phpcs:ignore
		}

		/*
		* Voting settings fields
		*/

		public function voting_active_render() {
			$options = $this->options;

			$value = isset( $options['voting_active'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[voting_active]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function voting_question_render() {
			$options = $this->options;

			$value = isset( $options['voting_question'] ) ? $options['voting_question'] : '';
			echo '<input type="text" name="basepress_settings[voting_question]" value="' . esc_attr( $value ) . '">';
		}

		public function hide_dislike_vote_button_render() {
			$options = $this->options;

			$value = isset( $options['hide_dislike_vote_button'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[hide_dislike_vote_button]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function show_votes_count_render() {
			$options = $this->options;

			$value = isset( $options['show_votes_count'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_votes_count]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function show_voting_confirmation_render(){
			$options = $this->options;

			$value = isset( $options['show_voting_confirmation'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_voting_confirmation]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function voting_confirmation_render(){
			$options = $this->options;

			$value = isset( $options['voting_confirmation'] ) ? $options['voting_confirmation'] : '';
			echo '<input type="text" name="basepress_settings[voting_confirmation]" value="' . esc_attr( $value ) . '">';
		}

		public function already_voted_notice_render(){
			$options = $this->options;

			$value = isset( $options['already_voted_notice'] ) ? $options['already_voted_notice'] : '';
			echo '<input type="text" name="basepress_settings[already_voted_notice]" value="' . esc_attr( $value ) . '">';
		}

		public function hide_buttons_if_voted_render(){
			$options = $this->options;

			$value = isset( $options['hide_buttons_if_voted'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[hide_buttons_if_voted]" value="1"' . checked( $value, 1, false ) . '>';
		}


		public function votes_icons_render() {
			global $basepress_utils;

			$options = $this->options;

			$icons = $basepress_utils->icons;
			if ( ! $icons ) {
				return;
			}

			echo '<div id="icon-sets">';
			$index = 0;
			foreach ( $icons->votes->set as $set ) {
				$checked = isset( $options['votes_icons'] ) && $options['votes_icons'] == $index ? 1 : 0;
				echo '<label>';
				echo '<input name="basepress_settings[votes_icons]" id="' . esc_attr( $index ) . '" type="radio" value="' . esc_attr( $index ) . '"' . checked( $checked, 1, false ) . '>';
				echo '<div class="icons-content">';

				foreach ( $set->icon as $icon ) {

					echo '<span class="' . esc_attr( $icon ) . '"></span>';

				}
				echo '</div>';
				echo '</label>';
				$index++;
			}
			echo '</div>';

		}

		/*
		 * Feedback settings fields
		 */

		public function activate_feedback_render(){
			$options = $this->options;

			$value = isset( $options['activate_feedback'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[activate_feedback]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function show_feedback_form_render(){
			$options = $this->options;

			$value = isset( $options['show_feedback_form'] ) ? $options['show_feedback_form'] : false;
			echo '<select name="basepress_settings[show_feedback_form]">';
			echo '<option ' . ( ! $value ? 'selected' : '' ) . ' disabled>' . esc_html__( 'Select Option', 'basepress' ) . '</option>';
			echo '<option value="dislike"' . selected( $value, 'dislike_only' ) . '>' . esc_html__( 'After Dislikes', 'basepress' ) . '</option>';
			echo '<option value="like_dislike"' . selected( $value, 'like_dislike' ) . '>' . esc_html__( 'After Likes and Dislikes', 'basepress' ) . '</option>';
			echo '<option value="always"' . selected( $value, 'always' ) . '>' . esc_html__( 'Always visible', 'basepress' ) . '</option>';
			echo '</select>';
		}

		public function feedback_form_label_render(){
			$options = $this->options;

			$value = isset( $options['feedback_form_lable'] ) ? $options['feedback_form_lable'] : '';
			echo '<input type="text" name="basepress_settings[feedback_form_lable]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_include_email_field_render(){
			$options = $this->options;

			$value = isset( $options['feedback_include_email_field'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[feedback_include_email_field]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Adds an optional field to the feedback form for the user to add an email to be contacted back.', 'basepress' ) . '</p>';
		}

		public function feedback_email_field_is_required_render(){
			$options = $this->options;

			$value = isset( $options['feedback_email_field_is_required'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[feedback_email_field_is_required]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'When enabled the user must supply an email to submit the feedback.', 'basepress' ) . '</p>';
		}

		public function feedback_email_field_label_render(){
			global $basepress_feedback;
			$options = $this->options;

			$value = isset( $options['feedback_email_field_label'] ) ? $options['feedback_email_field_label'] : $basepress_feedback->email_field_label;
			echo '<input type="text" name="basepress_settings[feedback_email_field_label]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_email_field_placeholder_render(){
			$options = $this->options;

			$value = isset( $options['feedback_email_field_placeholder'] ) ? $options['feedback_email_field_placeholder'] : '';
			echo '<input type="text" name="basepress_settings[feedback_email_field_placeholder]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_privacy_notice_render(){
			$options = $this->options;

			$value = isset( $options['feedback_privacy_notice'] ) ? $options['feedback_privacy_notice'] : '';
			echo '<textarea name="basepress_settings[feedback_privacy_notice]">' . esc_textarea( $value ) . '</textarea>';
			echo '<p class="description">' . esc_html__( 'Leave this field empty to disable the privacy notice.', 'basepress' ) . '</p>';
		}

		public function feedback_submit_text_render(){
			$options = $this->options;

			$value = isset( $options['feedback_submit_text'] ) ? $options['feedback_submit_text'] : '';
			echo '<input type="text" name="basepress_settings[feedback_submit_text]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_submit_success_text_render(){
			$options = $this->options;

			$value = isset( $options['feedback_submit_success_text'] ) ? $options['feedback_submit_success_text'] : '';
			echo '<textarea name="basepress_settings[feedback_submit_success_text]">' . esc_textarea( $value ) . '</textarea>';
		}

		public function feedback_submit_fail_text_render(){
			$options = $this->options;

			$value = isset( $options['feedback_submit_fail_text'] ) ? $options['feedback_submit_fail_text'] : '';
			echo '<textarea name="basepress_settings[feedback_submit_fail_text]">' . esc_textarea( $value ) . '</textarea>';
		}

		public function feedback_notify_admin_render(){
			$options = $this->options;

			$value = isset( $options['feedback_notify_admin'] ) ? $options['feedback_notify_admin'] : '';
			echo '<textarea name="basepress_settings[feedback_notify_admin]" rows="5">' . esc_textarea( $value ) . '</textarea>';
			echo '<p class="description">' . __( 'Add emails that should be notified of new feedback. One email per line. Use the {{author_email}} placeholder to notify the author of the article. Leave empty to disable this feature.', 'basepress' ) . '</p>';
		}

		public function feedback_notice_email_from_render(){
			$options = $this->options;

			$value = isset( $options['feedback_notice_email_from'] ) ? $options['feedback_notice_email_from'] : '';
			echo '<input type="text" name="basepress_settings[feedback_notice_email_from]" value="' . esc_attr( $value ) . '">';
			echo '<p class="description">' . __( 'This is the email address used to send the notice from your server. If left empty the default WordPress email will be used: wordpress@yourdomain.com.', 'basepress' ) . '</p>';
		}

		public function feedback_notice_email_subject_render(){
			$options = $this->options;

			$value = isset( $options['feedback_notice_email_subject'] ) ? $options['feedback_notice_email_subject'] : esc_html__( 'You have received a new Knowledge Base Feedback!', 'basepress' );
			echo '<input type="text" name="basepress_settings[feedback_notice_email_subject]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_notice_email_message_render(){
			global $basepress_feedback;
			$options = $this->options;

			 //Email message
			$default_message = $basepress_feedback->notice_email_message;

			$value = isset( $options['feedback_notice_email_message'] ) && ! empty( $options['feedback_notice_email_message'] ) ? $options['feedback_notice_email_message'] : $default_message;
			echo '<textarea name="basepress_settings[feedback_notice_email_message]" rows="7">' . esc_textarea( $value ) . '</textarea>';
			echo '<p class="description">' . __( 'Use the following placeholders when customising the message:<br><b>{{article_title}}</b> for the title of the article that the feedback refers to<br><b>{{article_link}}</b> for the link to the article<br><b>{{feedback_text}}</b> for the text of the feedback<br><b>{{submitter_email}}</b> for the email of the person that submitted the feedback if present', 'basepress' ) . '</p>'; //phpcs:ignore
		}

		public function feedback_recaptcha_type_render(){
			$options = $this->options;

			$value = isset( $options['feedback_recaptcha_type'] ) ? $options['feedback_recaptcha_type'] : 'none';
			echo '<select name="basepress_settings[feedback_recaptcha_type]">';
			echo '<option value="none"' . selected( $value, 'none' ) . '>' . esc_html__( 'None', 'basepress' ) . '</option>';
			echo '<option value="gr-v2-checkbox"' . selected( $value, 'gr-v2-checkbox' ) . '>' . esc_html__( 'reCAPTCHA v2 Checkbox', 'basepress' ) . '</option>';
			echo '<option value="gr-v2-invisible"' . selected( $value, 'gr-v2-invisible' ) . '>' . esc_html__( 'reCAPTCHA v2 Invisible', 'basepress' ) . '</option>';
			echo '</select>';
		}

		public function feedback_recaptcha_site_key_render(){
			$options = $this->options;

			$value = isset( $options['feedback_recaptcha_site_key'] ) ? $options['feedback_recaptcha_site_key'] : '';
			echo '<input type="text" name="basepress_settings[feedback_recaptcha_site_key]" value="' . esc_attr( $value ) . '">';
		}

		public function feedback_recaptcha_secrete_key_render(){
			$options = $this->options;

			$value = isset( $options['feedback_recaptcha_secrete_key'] ) ? $options['feedback_recaptcha_secrete_key'] : '';
			echo '<input type="text" name="basepress_settings[feedback_recaptcha_secrete_key]" value="' . esc_attr( $value ) . '">';
		}

		/*
		* Table of content settings fields
		*/
		public function show_toc_render() {
			$options = $this->options;

			$value = isset( $options['show_toc'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_toc]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'This applies only to the ToC on the articles body. If unchecked you can still use the dedicated widget in the sidebar to show a ToC.', 'basepress' ) . '</p>';
		}

		public function toc_title_render() {
			$options = $this->options;

			$value = isset( $options['toc_title'] ) ? $options['toc_title'] : '';
			echo '<input type="text" name="basepress_settings[toc_title]" value="' . esc_attr( $value ) . '">';
			echo '<p class="description">' . esc_html__( 'This title applies to the Table of Content in articles body. The widget has its own title.', 'basepress' ) . '</p>';
		}

		public function show_toc_numbers_render() {
			$options = $this->options;

			$value = isset( $options['show_toc_numbers'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_toc_numbers]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'This option applies to the widget as well.', 'basepress' ) . '</p>';
		}

		public function show_toc_totop_render() {
			$options = $this->options;

			$value = isset( $options['show_toc_totop'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_toc_totop]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Shows a link to go back to the top of the page.', 'basepress' ) . '</p>';
		}

		public function toc_totop_text_render() {
			$options = $this->options;

			$value = isset( $options['toc_totop_text'] ) ? $options['toc_totop_text'] : '';
			echo '<input type="text" name="basepress_settings[toc_totop_text]" value="' . esc_attr( $value ) . '">';
		}

		public function show_copy_anchors_links_render() {
			$options = $this->options;

			$value = isset( $options['show_copy_anchors_links'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_copy_anchors_links]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Shows a link next to the article\'s headings to copy the URL to the heading.', 'basepress' ) . '</p>';
		}


		public function copy_anchors_links_copy_text_render() {
			$options = $this->options;

			$value = isset( $options['copy_anchors_links_copy_text'] ) ? $options['copy_anchors_links_copy_text'] : esc_html__( 'Copy Link', 'basepress' );
			echo '<input type="text" name="basepress_settings[copy_anchors_links_copy_text]" value="' . esc_attr( $value ) . '">';
		}

		public function copy_anchors_links_copied_text_render() {
			$options = $this->options;

			$value = isset( $options['copy_anchors_links_copied_text'] ) ? $options['copy_anchors_links_copied_text'] : esc_html__( 'Link Copied', 'basepress' );
			echo '<input type="text" name="basepress_settings[copy_anchors_links_copied_text]" value="' . esc_attr( $value ) . '">';
		}


		/*
		 * Notices fields
		 */

		public function show_notice_titles_render() {
			$options = $this->options;

			$value = isset( $options['show_notice_titles'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_notice_titles]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function show_notice_icons_render() {
			$options = $this->options;

			$value = isset( $options['show_notice_icons'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_notice_icons]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function notice_titles_render() {
			global $basepress_notices;
			$options = $this->options;

			$notice_items = $basepress_notices->notice_items;

			if( ! isset( $options['notice_titles'] ) || empty( $options['notice_titles'] ) ){
				$options['notice_titles'] = array();
			}

			foreach( $notice_items as $name => $label ){
				$value = isset( $options['notice_titles'][$name] ) ? $options['notice_titles'][$name] : '';
				echo '<p class="description">' . esc_html( $label ) . '</p>';
				echo "<input type='text' name='basepress_settings[notice_titles][{$name}]' value='{$value}'>"; // phpcs:ignore
			}
		}

		/*
		 * Navigation fields
		 */

		public function show_adjacent_articles_render() {
			$options = $this->options;

			$value = isset( $options['show_adjacent_articles'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_adjacent_articles]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Shows Previous and Next articles link.', 'basepress' ) . '</p>';
		}

		public function prev_article_text_render() {
			$options = $this->options;

			$value = isset( $options['prev_article_text'] ) ? $options['prev_article_text'] : '';
			echo '<input type="text" name="basepress_settings[prev_article_text]" value="' . esc_attr( $value ) . '">';
		}

		public function next_article_text_render() {
			$options = $this->options;

			$value = isset( $options['next_article_text'] ) ? $options['next_article_text'] : '';
			echo '<input type="text" name="basepress_settings[next_article_text]" value="' . esc_attr( $value ) . '">';
		}


		/*
		* Comments fields
		*/

		public function enable_comments_render() {
			$options = $this->options;

			$value = isset( $options['enable_comments'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[enable_comments]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function use_default_comments_template_render() {
			$options = $this->options;

			$value = isset( $options['use_default_comments_template'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[use_default_comments_template]" value="1"' . checked( $value, 1, false ) . '>';
		}


		/*
		* Content restriction fields
		*/

		public function activate_restrictions_render() {
			$options = $this->options;

			$value = isset( $options['activate_restrictions'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[activate_restrictions]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function restriction_redirected_pages_render(){
			$options = $this->options;

			$value = isset( $options['restriction_redirected_pages'] ) ? $options['restriction_redirected_pages'] : 'all';
			echo '<select name="basepress_settings[restriction_redirected_pages]">';
			echo '<option value="all"' . selected( $value, 'all' ) . '>' . esc_html__( 'All pages', 'basepress' ) . '</option>';
			echo '<option value="none"' . selected( $value, 'none' ) . '>' . esc_html__( 'None', 'basepress' ) . '</option>';
			echo '<option value="sections_only"' . selected( $value, 'sections_only' ) . '>' . esc_html__( 'Sections only', 'basepress' ) . '</option>';
			echo '</select>';
			echo '<p class="description">';
			echo __( '<b>All Pages</b> - restricted content is visible ONLY to users that have access. Direct access to any of these pages without permission will lead to a 404 error page.' , 'basepress' ) . '<br>'; // phpcs:ignore
			echo __( "<b>None</b> - restricted content is visible to ALL users. Anyone that doesn't have the right role to view the content sees only teaser content and a restriction notice." , 'basepress' ) . '<br>'; // phpcs:ignore
			echo __( '<b>Section Only</b> - restricted content is visible ONLY to users that have access. Anyone accessing an article with a direct link without the right role to view the content only sees teaser content and a restriction notice.' , 'basepress' ); // phpcs:ignore
			echo '</p>';
		}

		public function restricted_page_notice_render() {
			$options = $this->options;
			$value = isset( $options['restricted_page_notice'] ) ? $options['restricted_page_notice'] : '';
			echo '<input type="text" name="basepress_settings[restricted_page_notice]" value="' . esc_attr( $value ) . '">';
		}

		public function restricted_search_snippet_notice_render(){
			$options = $this->options;
			$value = isset( $options['restricted_search_snippet_notice'] ) ? $options['restricted_search_snippet_notice'] : '';
			echo '<input type="text" name="basepress_settings[restricted_search_snippet_notice]" value="' . esc_attr( $value ) . '">';
		}

		public function show_restricted_teaser_render() {
			$options = $this->options;

			$value = isset( $options['show_restricted_teaser'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_restricted_teaser]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function show_restricted_login_render() {
			$options = $this->options;

			$value = isset( $options['show_restricted_login'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_restricted_login]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function restricted_teaser_length_render() {
			$options = $this->options;

			$value = isset( $options['restricted_teaser_length'] ) ? $options['restricted_teaser_length'] : '';
			echo '<input type="number" name="basepress_settings[restricted_teaser_length]" value="' . esc_attr( $value ) . '" min="0">';
		}

		public function restricted_teaser_do_shortcodes_render(){
			$options = $this->options;

			$value = isset( $options['restricted_teaser_do_shortcodes'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[restricted_teaser_do_shortcodes]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function  order_roles_alpha_render(){
			$options = $this->options;

			$value = isset( $options['order_roles_alpha'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[order_roles_alpha]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Orders user roles alphabetically in articles edit screen.', 'basepress' ) . '</p>';
		}

		public function show_roles_admin_columns_render(){
			$options = $this->options;

			$value = isset( $options['show_roles_admin_columns'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[show_roles_admin_columns]" value="1"' . checked( $value, 1, false ) . '>';
			echo '<p class="description">' . esc_html__( 'Adds admin columns in the articles list with the list of roles that can view each article.', 'basepress' ) . '</p>';
		}

		/*
		 * Statistics fields
		 */

		public function activate_insights_render(){
			$options = $this->options;

			$value = isset( $options['activate_insights'] ) ? 1 : 0;
			echo '<input type="checkbox" name="basepress_settings[activate_insights]" value="1"' . checked( $value, 1, false ) . '>';
		}

		public function insights_default_time_range_render(){
			$options = $this->options;

			$value = isset( $options['insights_default_time_range'] ) ? $options['insights_default_time_range'] : 'week';
			echo '<select name="basepress_settings[insights_default_time_range]">';
			echo '<option value="week" ' . selected( 'week', $value, false ) . '>' . esc_html__( 'Week', 'basepress' ) . '</option>';
			echo '<option value="month" ' . selected( 'month', $value, false ) . '>' . esc_html__( 'Month', 'basepress' ) . '</option>';
			echo '<option value="year" ' . selected( 'year', $value, false ) . '>' . esc_html__( 'Year', 'basepress' ) . '</option>';
			echo '</select>';
		}

		public function insights_max_results_render(){
			$options = $this->options;

			$value = isset( $options['insights_max_results'] ) ? $options['insights_max_results'] : 10;
			echo '<input type="number" name="basepress_settings[insights_max_results]" value="' . esc_attr( $value ) . '" min="10" max="100">';
		}


		/*
		* Delete Data fields
		*/

		public function delete_data_render() {
			$site = get_current_blog_id();
			wp_nonce_field( 'basepress_network_delete', 'basepress_delete_sites' );
			echo '<input type="submit" name="delete_data" id="delete_data_site" data-site="' . esc_attr( $site ) . '" class="button button-primary" value="' . esc_html__( 'Delete', 'basepress' ) . '">';
			echo '<p class="description">' . esc_html__( 'ATTENTION! This action cannot be undone.', 'basepress' ) . '</p>';
			//Ajax Loader
			echo '<div id="ajax-loader"></div>';
		}
	}//End Class

	new Basepress_Premium_settings();
}

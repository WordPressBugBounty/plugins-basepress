<?php
/**
* This is the class that handles the users feedback
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists( 'Basepress_Users_Feedback' ) ){

	#[\AllowDynamicProperties]
	class Basepress_Users_Feedback{

		private $status;
		private $paged;
		private $search_terms;
		private $open_url;
		private $archived_url;
		private $posts_per_page;
		private $feedback_query;
		private $feedback_id = false;
		private $action = false;
		public $page_url = 'edit.php?post_type=knowledgebase&page=basepress_feedback';
		public $notice_email_message = '';
		public $email_field_label = '';
		private $allowed_html_in_form = array(
			'a' => array(
				'href' => array(),
				'title' => array()
			)
		);

		/**
		 * Basepress_Users_Feedback constructor.
		 *
		 * @since 1.9.0
		 */
		public function __construct(){
			global $basepress_utils;

			$options = $basepress_utils->get_options();

			//If the feedback is not active exit
			if( isset( $options['activate_feedback'] ) ){

				add_action( 'init', array( $this, 'register_post_type' ) );

				add_action( 'admin_menu', array( $this, 'add_feedback_page' ) );

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

				//Add ajax call for backend
				add_action( 'wp_ajax_basepress_manage_feedback', array( $this, 'basepress_manage_feedback' ) );

				//Add ajax calls for frontend
				add_action( 'wp_ajax_nopriv_basepress_submit_feedback', array( $this, 'basepress_submit_feedback' ) );
				add_action( 'wp_ajax_basepress_submit_feedback', array( $this, 'basepress_submit_feedback' ) );

				//Notify admins of new feedback
				add_action( 'save_post_basepress_feedback', array( $this, 'send_emails_on_new_feedback' ), 10, 3 );

				//Set translations
				add_action( 'plugins_loaded', function(){
					$this->notice_email_message = __(	"Hi,\n\nYou have received a new feedback on:\n\n{{article_title}}\n{{article_link}}\n\nMessage:\n\n{{feedback_text}}\n\nFrom: {{submitter_email}}", 'basepress' );
					$this->email_field_label = esc_html__( 'Your email:', 'basepress' );
				} );

				$this->allowed_html_in_form = apply_filters( 'basepress_feedback_allowed_html_in_form', $this->allowed_html_in_form );
			}

		}


		/**
		 * Register feedback post type
		 *
		 * @since 1.9.0
		 */
		public function register_post_type(){
			register_post_type(
				'basepress_feedback',
				array(
					'label'               => esc_html__( 'Users Feedback', 'basepress' ),
					'supports'            => array(),
					'hierarchical'        => false,
					'query_var'           => false,
					'public'              => false,
					'show_ui'             => false,
					'show_in_menu'        => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
					'can_export'          => true,
					'has_archive'         => false,
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'capability'          => 'edit_post',
				)
			);
		}


		/**
		 * Add feedback page to admin
		 *
		 * @since 1.9.0
		 */
		public function add_feedback_page(){
			//Add the sub menu 'Feedback' on knowledgebase post type
			add_submenu_page( 'edit.php?post_type=knowledgebase', 'BasePress' . esc_html__( 'Feedback', 'basepress' ), esc_html__( 'Users Feedback', 'basepress' ), 'manage_categories', 'basepress_feedback', array( $this, 'display_screen' ) );
		}


		/**
		 * Enqueue Admin scripts
		 *
		 * @since 1.9.0
		 *
		 * @param $hook
		 */
		public function enqueue_admin_scripts( $hook ){
			//Enqueue admin script for Feedback screen only
			if( 'knowledgebase_page_basepress_feedback' == $hook ){
				wp_register_script( 'basepress-feedback-js', BASEPRESS_URI . 'admin/premium/js/basepress-feedback.js', array( 'jquery' ), BASEPRESS_VER, true );
				wp_enqueue_script( 'basepress-feedback-js' );
				wp_localize_script( 'basepress-feedback-js', 'feedbacktext', array( 'confirmdelete' => esc_html__( 'Are you sure you want to delete this feedback?', 'basepress' ) ) );
			}
		}


		/**
		 * Setup variables for the query and action
		 *
		 * @since 1.9.0
		 */
		private function setup_vars(){
			$this->status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : 'publish';
			$this->paged = isset( $_REQUEST['paged'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) : 1;
			$this->search_terms = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
			$this->feedback_id = isset( $_REQUEST['feedbackID'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['feedbackID'] ) ) : false;
			$this->action = isset( $_REQUEST['feedbackAction'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['feedbackAction'] ) ) : false;

			$this->open_url = $this->page_url;
			$this->archived_url = $this->open_url . '&status=trash';
			$this->posts_per_page = apply_filters( 'basepress_feedback_per_page', 10 );
		}


		/**
		 * Display admin page
		 *
		 * @since 1.9.0
		 */
		public function display_screen(){
			$this->setup_vars();
			$current_status = $this->status;
			$feedbacks = $this->get_feedbacks();
			$feedbacks_count = wp_count_posts( 'basepress_feedback' );

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Knowledge Base Users Feedback', 'basepress' ); ?></h1>

				<div id="basepress-feedbacks-header">
					<ul class="basepress-feedback-status">
						<li class="<?php echo 'publish' == $current_status ? 'current' : ''; ?>">
							<a href="<?php echo esc_url( $this->open_url ); ?>"><?php esc_html_e( 'Open', 'basepress' ); ?></a>
							(<span class="open-count"><?php echo esc_html( $feedbacks_count->publish ) ?></span>)
						</li>
						<span>|</span>
						<li class="<?php echo 'trash' == $current_status ? 'current' : ''; ?>">
							<a href="<?php echo esc_url( $this->archived_url ); ?>"><?php esc_html_e( 'Archived', 'basepress' ); ?></a>
							(<span class="trash-count"><?php echo esc_html( $feedbacks_count->trash ) ?></span>)
						</li>
					</ul>
					<div class="basepress-feedbacks-search">
						<?php $this->render_search_bar(); ?>
					</div>
				</div>
				<div id="basepress-feedbacks-container" data-paged="<?php echo esc_attr( $this->paged ); ?>" data-status="<?php echo esc_attr( $this->status ); ?>" data-search="<?php echo esc_attr( $this->search_terms ) ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-feedback-nonce' ) ); ?>">
				<?php $this->render_feedbacks_list( $feedbacks->posts ); ?>
				<?php $this->render_pagination(); ?>
				</div>
			</div>


			<?php
		}


		/**
		 * Renders admin search bar
		 *
		 * @since 1.9.0
		 */
		private function render_search_bar(){
			?>
			<form id="feedbacks-filter" method="get">
				<label class="screen-reader-text" for="feedback-search-input"><?php esc_html_e( 'Search feedback', 'basepress' ); ?></label>
				<input type="hidden" name="post_type" value="knowledgebase">
				<input type="hidden" name="page" value="basepress_feedback">
				<input type="hidden" name="status" value="<?php echo esc_attr( $this->status ) ?>">
				<input type="search" id="feedback-search-input" name="s" value="<?php echo esc_attr( $this->search_terms ); ?>">
				<input type="submit" id="search-submit" class="button" value="<?php esc_html_e( 'Search feedback', 'basepress' ); ?>">
			</form>
			<?php
		}


		/**
		 * Renders Pagination
		 *
		 * @since 1.9.0
		 */
		private function render_pagination(){

			if( $this->feedback_query->max_num_pages > 1 ){
				$root_url = add_query_arg( 'status', $this->status, $this->open_url );
				if( $this->search_terms ){
					$root_url = add_query_arg( 's', $this->search_terms, $root_url );
				}
				$root_url = html_entity_decode( $root_url );

				echo '<div class="basepress-feedbacks-nav">';

				for( $i = 1; $i <= $this->feedback_query->max_num_pages; $i++ ){
					if( $i == $this->paged ){
						echo '<span class="page-numbers current">' . esc_html( $i ) . '</span>';
					}else{
						$url = $root_url . '&paged=' . $i;
						echo '<a href="' . esc_url( $url ) . '" class="page-numbers">' . esc_html( $i ) . '</a>';
					}
				}

				echo '</div>';
			}
		}


		/**
		 * Get list of feedbacks for admin side
		 *
		 * @since 1.9.0
		 */
		private function get_feedbacks(){
			$feedbacks_query = $this->feedbacks_query();
			if( 0 == count( $feedbacks_query->posts ) && $this->paged > 1 ){
				$this->paged = $this->paged - 1;
				$feedbacks_query = $this->get_feedbacks();
			}
			return $feedbacks_query;
		}


		/**
		 * Runs the query to get the feedbacks for admin side
		 *
		 * @since 1.9.0
		 *
		 * @return WP_Query
		 */
		private function feedbacks_query(){
			$feedback_args = array(
				'post_type'      => 'basepress_feedback',
				'post_status'    => $this->status,
				'posts_per_page' => $this->posts_per_page,
				'paged'          => $this->paged,
				'orderby'        => 'date',
				'order'          => 'DESC',
				's'              => $this->search_terms
			);


			$this->feedback_query = new WP_Query( $feedback_args );
			return $this->feedback_query;
		}


		/**
		 * Renders feedback list
		 *
		 * @since 1.9.0
		 *
		 * @param $feedbacks
		 */
		private function render_feedbacks_list( $feedbacks ){
			global $basepress_utils;

			echo '<ul id="basepress-feedbacks-table">';
			//Feedbacks list
			foreach( $feedbacks as $feedback ):
				$post_link = get_the_permalink( $feedback->post_parent );
				$post_title = $post_link ? get_the_title( $feedback->post_parent ): $feedback->post_title;
				$post_terms = $post_link ? wp_get_post_terms( $feedback->post_parent, 'knowledgebase_cat' ) : false;
				$post_product = ! empty ( $post_terms ) && ! is_wp_error( $post_terms ) ? $basepress_utils->get_top_term( $post_terms[0] ) : false;
				if( empty( $post_link ) ){
					$post_product_name = '<i>(' . esc_html__( 'This article has been deleted from the Knowledge Base', 'basepress' ) . ')</i>';
				}
				elseif( empty( $post_product ) ){
					$post_product_name = esc_html__( 'Uncategorised', 'basepress' );
				}
				else{
					$post_product_name = $post_product->name;
				}

				?>
				<li class="basepress-feedback <?php echo esc_attr( $this->status ); ?>" data-feedback="<?php echo esc_attr( $feedback->ID ); ?>">
					<div class="basepress-feedback-header">
						<a href="<?php echo esc_url( $post_link ); ?>" class="basepress-feedback-post-title"
						   target="_blank"><?php echo esc_html( $post_title ); ?></a>
						<?php echo ' - ' . esc_html( $post_product_name ); ?>
					</div>
					<div class="basepress-feedback-date">
						<span class="dashicons dashicons-clock"></span>
						<?php echo esc_html( $feedback->post_date ); ?>
					</div>
					<?php if( ! empty( $feedback->post_password ) ) : ?>
					<div class="basepress-feedback-from">
						<a href="mailto:<?php echo esc_html( $feedback->post_password ); ?>"><span class="dashicons dashicons-email-alt"></span></a>
						<span><?php echo esc_html( $feedback->post_password ); ?></span>
					</div>
					<?php endif; ?>
					<p><?php echo nl2br( esc_html( $feedback->post_content ) ); ?></p>
					<span class="dashicons dashicons-trash basepress-delete-feedback" data-feedback="<?php echo esc_attr( $feedback->ID ); ?>"></span>
					<?php if( 'publish' == $this->status ) : ?>
						<span class="dashicons dashicons-hidden basepress-archive-feedback" data-feedback="<?php echo esc_attr( $feedback->ID ); ?>"></span>
					<?php else: ?>
						<span class="dashicons dashicons-visibility basepress-restore-feedback" data-feedback="<?php echo esc_attr( $feedback->ID ); ?>"></span>
					<?php endif; ?>
				</li>

			<?php endforeach;
			echo '</ul>';
		}


		/**
		 * Manage Ajax calls
		 *
		 * @since 1.9.0
		 */
		public function basepress_manage_feedback(){
			$data = array();
			if ( ! current_user_can( 'manage_categories' ) ) {
				status_header( 403 );
				$state = 'error';
				$message = esc_html__( 'You do not have permission to perform this action.', 'basepress' );
				echo wp_json_encode( array( 'state' => $state, 'message' => $message, 'data' => $data ) );
				wp_die();
			}

			//nonce verification
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bp-feedback-nonce' ) ) {
				$state = 'error';
				$message = esc_html__( 'Nonce verification failed.', 'basepress' );
				echo wp_json_encode( array( 'state' => $state, 'message' => $message, 'data' => $data ) );
				wp_die();
			}
			global $wpdb;

			$this->setup_vars();
			$response = false;

			if( $this->feedback_id && 'basepress_feedback' === get_post_type( $this->feedback_id ) && $this->action ){
				switch( $this->action ){
					case 'delete':
						$deleted = wp_delete_post( $this->feedback_id, true );
						$response = $deleted ? true : false;
						break;
					case 'archive':
						$archived = $wpdb->update(
							$wpdb->posts,
							array(
								'post_status' => 'trash'
							),
							array( 'ID' => (int)$this->feedback_id )
						);
						$response = false !== $archived ? true : false;
						break;
					case 'restore':
						$restore = $wpdb->update(
							$wpdb->posts,
							array(
								'post_status' => 'publish'
							),
							array( 'ID' => (int)$this->feedback_id )
						);
						$response = false !== $restore ? true : false;
						break;
				}
			}

			if( $response ){
				ob_start();
				$feedbacks = $this->get_feedbacks();
				$this->render_feedbacks_list( $feedbacks->posts );
				$this->render_pagination();
				$data['feedbacks'] = ob_get_clean();
				$data['counts'] =  wp_count_posts( 'basepress_feedback' );
				$data['vars'] = array( 'search' => $this->search_terms );
				$state = 'success';
				$message = '';
			}
			else{
				$state = 'error';
				$message = esc_html__( 'There was a problem deleting the feedback. Please try again after refreshing the page.', 'basepress' );
			}

			echo wp_json_encode( array( 'state' => $state, 'message' => $message, 'data' => $data ) );
			wp_die();
		}


		/**
		 * Frontend submission form
		 *
		 * @since 1.9.0
		 */
		public function render_feedback_form(){
			global $basepress_utils;

			$options = $basepress_utils->get_options();

			//If the feedback is not active exit
			if( ! isset( $options['activate_feedback'] ) ){
				return;
			}

			$this->form_label = isset( $options['feedback_form_lable'] ) ? esc_html( $options['feedback_form_lable'] ) : '';
			$this->form_include_email_field = isset( $options['feedback_include_email_field'] ) ? (bool)$options['feedback_include_email_field'] : false;
			$this->form_email_required = isset( $options['feedback_email_field_is_required'] ) ? (bool)$options['feedback_email_field_is_required'] : false;
			$this->form_email_label = isset( $options['feedback_email_field_label'] ) ? wp_kses( $options['feedback_email_field_label'], $this->allowed_html_in_form ) : '';
			$this->form_email_placeholder = isset( $options['feedback_email_field_placeholder'] ) ? esc_html( $options['feedback_email_field_placeholder'] ) : '';
			$this->form_privacy_notice = isset( $options['feedback_privacy_notice'] ) ? wp_kses( $options['feedback_privacy_notice'], $this->allowed_html_in_form ) : '';
			$this->submit_text = isset( $options['feedback_submit_text'] ) ? esc_html( $options['feedback_submit_text'] ) : esc_html__( 'Submit', 'basepress' );
			$this->form_visibility_class = isset( $options['show_feedback_form'] ) && 'always' != $options['show_feedback_form'] ? ' hidden' : '';
			$this->data_show = isset( $options['show_feedback_form'] ) ? $options['show_feedback_form'] : 'always';
			$recaptcha_type = isset( $options['feedback_recaptcha_type'] ) ? $options['feedback_recaptcha_type'] : 'none';
			if( 'none' != $recaptcha_type ){
				$recaptcha_site_key = isset( $options['feedback_recaptcha_site_key'] ) ? $options['feedback_recaptcha_site_key'] : '';
				$recaptcha_secrete_key = isset( $options['feedback_recaptcha_secrete_key'] ) ? $options['feedback_recaptcha_secrete_key'] : '';
				//Disable reCAPTCHA if the site KEY or the secret KEY are missing
				$recaptcha_type = $recaptcha_site_key && $recaptcha_secrete_key ? $recaptcha_type : 'none';
			}

			switch( $recaptcha_type ){
				case 'none':
					$this->render_default_form();
					break;
				case 'gr-v2-checkbox':
					$this->render_gr_v2_checkbox_form( $recaptcha_site_key );
					break;
				case 'gr-v2-invisible':
					$this->render_gr_v2_invisible_form( $recaptcha_site_key );
					break;
			}
		}


		/**
		 * Renders default Feedback form
		 *
		 * @since 2.7.0
		 */
		private function render_default_form(){
			global $post;
			?>
			<div class="bpress-feedback<?php echo esc_attr( $this->form_visibility_class ); ?>" data-show="<?php echo esc_attr( $this->data_show ); ?>">
				<form id="bpress-feedback-form">
					<label for="bpress-feedback-message"><?php echo esc_html( $this->form_label ); ?></label>
					<div class="bpress-feedback-textarea">
						<textarea id="bpress-feedback-message" name="bpress-feedback-message" required></textarea>
					</div>
					<?php if( $this->form_include_email_field ){
						$this->render_email_field();
					} ?>
					<?php if( $this->form_privacy_notice ){
						$this->render_privacy_notice();
					} ?>
					<input type="text" id="bpress-feedback-password" name="bpress-feedback-password" style="display:none !important" tabindex="-1" autocomplete="off">
					<?php wp_nonce_field( 'bp-feedback-nonce', 'nonce' ); ?>
					<button type="submit" class="bpress-btn bpress-submit-feedback"
					        data-post="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $this->submit_text ); ?></button>
				</form>
			</div>
			<div class="bpress-feedback-confirm"></div>
			<?php
		}


		/**
		 * Renders the Feedback form to use with reCAPTCHA v2 checkbox
		 *
		 * @since 2.7.0
		 *
		 * @param $recaptcha_site_key
		 */
		private function render_gr_v2_checkbox_form( $recaptcha_site_key ){
			global $post;

			$recaptcha_args = array(
				"data-sitekey"  => $recaptcha_site_key,
				"data-callback" => "basepressFeedbackEnableSubmit",
				"data-expired-callback" => "basepressFeedbackDisableSubmit",
			);

			$recaptcha_atts = $this->get_recapctha_atts( $recaptcha_args );
			?>
			<div class="bpress-feedback<?php echo esc_attr( $this->form_visibility_class ); ?>" data-show="<?php echo esc_attr( $this->data_show ); ?>">
				<form id="bpress-feedback-form">
					<label for="bpress-feedback-message"><?php echo esc_html( $this->form_label ); ?></label>
					<div class="bpress-feedback-textarea">
						<textarea id="bpress-feedback-message" name="bpress-feedback-message" required></textarea>
					</div>
					<?php if( $this->form_include_email_field ){
						$this->render_email_field();
					} ?>
					<div class="g-recaptcha"<?php echo $recaptcha_atts; ?>></div> <?php // phpcs:ignore ?>
					<?php wp_nonce_field( 'bp-feedback-nonce', 'nonce' ); ?>
					<button type="submit" class="bpress-btn bpress-submit-feedback"
					     data-post="<?php echo esc_attr( $post->ID ); ?>" disabled><?php echo esc_html( $this->submit_text ); ?></button>
				</form>
			</div>
			<div class="bpress-feedback-confirm"></div>
			<script src="https://www.google.com/recaptcha/api.js" async defer></script>
			<?php
		}


		/**
		 * Renders the Feedback form to use with reCAPTCHA v2 Invisible
		 *
		 * @since 2.7.0
		 *
		 * @param $recaptcha_site_key
		 */
		private function render_gr_v2_invisible_form( $recaptcha_site_key ){
			global $post;

			$recaptcha_args = array(
				"data-sitekey"  => $recaptcha_site_key,
				"data-callback" => "basepressFeedbackOnSubmit",
			);

			$recaptcha_atts = $this->get_recapctha_atts( $recaptcha_args );

			?>
			<div class="bpress-feedback<?php echo esc_attr( $this->form_visibility_class ); ?>" data-show="<?php echo esc_attr( $this->data_show ); ?>">
				<form id="bpress-feedback-form" method="post">
					<label for="bpress-feedback-message"><?php echo esc_html( $this->form_label ); ?></label>
					<div class="bpress-feedback-textarea">
						<textarea id="bpress-feedback-message" name="bpress-feedback-message" required></textarea>
					</div>
					<?php if( $this->form_include_email_field ){
						$this->render_email_field();
					} ?>
					<?php wp_nonce_field( 'bp-feedback-nonce', 'nonce' ); ?>
					<button type="submit" class="bpress-btn bpress-submit-feedback grecaptcha-invisible g-recaptcha"
					        <?php echo $recaptcha_atts; // phpcs:ignore ?>
					        data-post="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $this->submit_text ); ?></button>
				</form>
			</div>
			<div class="bpress-feedback-confirm"></div>
			<script src="https://www.google.com/recaptcha/api.js" async defer></script>
			<?php
		}


		/**
		 * returns a string of attributes for the reCAPTCHA element
		 *
		 * @since 2.7.0
		 *
		 * @param $recaptcha_args
		 * @return string
		 */
		private function get_recapctha_atts( $recaptcha_args ){
			$recaptcha_args = apply_filters( 'basepress_feedback_recaptcha_args', $recaptcha_args );
			$recaptcha_atts = '';
			if( ! empty( $recaptcha_args ) ){
				foreach( $recaptcha_args as $data_key => $data_value ){
					$recaptcha_atts .= ' ' . $data_key . ' ="' . $data_value . '"';
				}
			}
			return $recaptcha_atts;
		}


		/**
		 * Adds the email field to the feedback form
		 *
		 * @since 2.10.0
		 */
		private function render_email_field(){
			$required = $this->form_email_required ? ' required' : '';
			$placeholder = ! empty( $this->form_email_placeholder ) ? " placeholder='{$this->form_email_placeholder}'" : '';
			if( ! empty( $this->form_email_label ) ){
				echo "<label for='bpress-feedback-email' class='bpress-feedback-email-label'>{$this->form_email_label}</label>"; // phpcs:ignore Standard.Category.SniffName.ErrorCode
			}

			echo "<input id='bpress-feedback-email' type='email'{$required} name='bpress-feedback-email'{$placeholder}>"; // phpcs:ignore Standard.Category.SniffName.ErrorCode
		}


		/**
		 * Renders the privacy notice for the frontend
		 *
		 * @since 2.10.0
		 */
		private function render_privacy_notice(){
			echo '<div class="bpress-feedback-privacy-notice">';
			echo '<input type="checkbox" id="bpress-feedback-privacy-checkbox" value="0" required>';
			echo "<label for='bpress-feedback-privacy-checkbox'>{$this->form_privacy_notice}</label><br>"; // phpcs:ignore Standard.Category.SniffName.ErrorCode
			echo '</div>';
		}


		/**
		 * Insert Feedback from frontend Ajax call
		 *
		 * @since 1.9.0
		 */
		public function basepress_submit_feedback(){
			//nonce verification
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'bp-feedback-nonce' ) ) {
				$message = esc_html__( 'Nonce verification failed.', 'basepress' );
				$state = 'fail';
				echo wp_json_encode( array( 'state' => $state, 'message' => $message ) );
				wp_die();
			}
			global $basepress_utils;

			$options = $basepress_utils->get_options();

			$recaptcha_type = isset( $options['feedback_recaptcha_type'] ) ? $options['feedback_recaptcha_type'] : 'none';

			$post_ID = isset( $_REQUEST['postID'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['postID'] ) ) : '';
			$feedback = isset( $_REQUEST['feedback'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['feedback'] ) ) : '';
			$submitter_email = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : '';
			$recaptcha_response = isset( $_REQUEST['grecaptcha'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['grecaptcha'] ) ) : '';
			$recaptcha_secrete_key = isset( $options['feedback_recaptcha_secrete_key'] ) ? $options['feedback_recaptcha_secrete_key'] : '';


			if( 'none' != $recaptcha_type && ! empty( $recaptcha_type ) ){
				//If we are using reCAPTCHA and there is no secrete key or response block feedback
				if( empty( $recaptcha_response ) || empty( $recaptcha_secrete_key ) ){
					$feedback = '';
				}
				else{
					//Verifiy that the response from reCAPTCHA is correct
					$verified = $this->verify_grecaptcha_response( $recaptcha_response, $recaptcha_secrete_key );
					$feedback = $verified ? $feedback : '';
				}
			}

			//Sanitize feedback sanitize_textarea_field was introduced in WP 4.7
			if( function_exists( 'sanitize_textarea_field' ) ){
				$feedback = sanitize_textarea_field( $feedback );
			}
			else{
				$feedback = sanitize_text_field( $feedback );
			}

			//Remove any HTML tags, scripts and styles
			$feedback = stripslashes( wp_strip_all_tags( $feedback ) );

			//Can we proceed if the email was not submitted?
			//If the email is not accepted by WordPress sanitize_email() do not continue
			$email_is_valid = true;

			if( isset( $_REQUEST['email'] ) && ! empty( $_REQUEST['email'] ) ){
				$email_is_valid = ! empty( $submitter_email );
			}

			$inserted = false;

			if( '' !== $post_ID && '' !== $feedback && $email_is_valid ){

				$title = get_post_field( 'post_title', $post_ID );

				$data = array(
					'ID'            => 0,
					'post_type'     => 'basepress_feedback',
					'post_status'   => 'publish',
					'post_title'    => $title,
					'post_content'  => $feedback,
					'post_password' => $submitter_email,
					'post_parent'   => $post_ID
				);

				$inserted = wp_insert_post( $data, true );
			}

			if( 0 != $inserted && ! is_wp_error( $inserted ) ){
				$message = $options['feedback_submit_success_text'];
				$state = 'success';
			}
			else{
				$message = $options['feedback_submit_fail_text'];
				$state = 'fail';
			}

			echo wp_json_encode( array( 'state' => $state, 'message' => $message ) );

			wp_die();
		}


		/**
		 * Calls Google reCAPTCHA API to verify the response
		 *
		 * @since 2.7.0
		 *
		 * @param $recaptcha_response
		 * @param $recaptcha_secrete_key
		 * @return mixed
		 */
		private function verify_grecaptcha_response( $recaptcha_response, $recaptcha_secrete_key ){
			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify" );
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS,
				http_build_query(
					array(
						'response' => $recaptcha_response,
						'secret'   => $recaptcha_secrete_key
					)
				)
			);
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec( $curl );
			curl_close( $curl );

			$response = json_decode( $response, true );

			return $response['success'];
		}


		/**
		 * Send an email to the specified email addresses to notify the reception of a new feedback
		 *
		 * @since 2.10.0
		 *
		 * @param $post_id
		 * @param $post
		 * @param $update
		 */
		function send_emails_on_new_feedback( $post_id, $post, $update ){
			if( $update	){
				return;
			}

			global $basepress_utils;

			$author_placeholder = '{{author_email}}';

			//get list of email addresses to send the message to
			if( $to_emails = $basepress_utils->get_option( 'feedback_notify_admin' ) ){
				if( false !== strpos( $to_emails, $author_placeholder ) ){
					$post_author_id = get_post_field( 'post_author', $post->post_parent );
					$author_email = get_the_author_meta( 'user_email', $post_author_id );
					$to_emails = str_replace( $author_placeholder, $author_email, $to_emails );
				}
				$to_emails = preg_split('/\r\n|\r|\n/', $to_emails );
			}

			if( empty( $to_emails ) ){
				return;
			}

			$valid_to_emails = array();
			foreach( $to_emails as $index => $email ){
				if( filter_var( $email, FILTER_VALIDATE_EMAIL ) ){
					$valid_to_emails[] = $email;
				}
			}

			if( empty( $valid_to_emails ) ){
				return;
			}

			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$email_from = $basepress_utils->get_option( 'feedback_notice_email_from' );
			$submitter_email = $post->post_password;

			$headers = array();
			$headers[] = empty( $email_from )
										? ''
										: 'from:' . $blogname . ' <' . $email_from . '>';

			//Email subject
			$subject = $basepress_utils->get_option( 'feedback_notice_email_subject' );
			$subject = ! empty( $subject ) ? $subject : esc_html__( 'You have received a new Knowledge Base Feedback!', 'basepress' );

			//Email message
			$message = $basepress_utils->get_option( 'feedback_notice_email_message' );
			$message = ! empty( $message ) ? $message : $this->notice_email_message;

			/**
			 * Filter the notice message before it is processed
			 */
			$message = apply_filters( 'basepress_feedback_admin_notice_message', $message, $post );

			$message = str_replace(
				array(
					'{{article_title}}',
					'{{article_link}}',
					'{{feedback_text}}',
					'{{submitter_email}}'
				),
				array(
					$post->post_title,
					get_the_permalink( $post->post_parent ),
					$post->post_content,
					! empty( $submitter_email ) ? $submitter_email : esc_html__( 'Not specified', 'basepress' )
				),
				$message
			);


			$email_parts = array(
				'headers' => $headers,
				'subject' => $subject,
				'message' => $message
			);

			$email_parts = apply_filters( 'basepress_feedback_notify_parts', $email_parts, $post );

			wp_mail( $valid_to_emails, $email_parts['subject'], $email_parts['message'], $email_parts['headers'] );
		}
	}

	global $basepress_feedback;
	$basepress_feedback = new Basepress_Users_Feedback;
}
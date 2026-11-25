<?php
/**
 * This is the class that handles the articles votes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basepress_Votings' ) ) {

	class Basepress_Votings {

		/**
		 * basepress_votes constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			//Define the ajax call for front end
			add_action( 'wp_ajax_nopriv_basepress_update_votes', array( $this, 'basepress_update_votes' ) );
			add_action( 'wp_ajax_basepress_update_votes', array( $this, 'basepress_update_votes' ) );

			//Add Votes column to posts
			add_filter( 'manage_knowledgebase_posts_columns' , array( $this, 'add_custom_columns' ) );
			add_action( 'manage_knowledgebase_posts_custom_column' , array( $this, 'manage_custom_columns' ), 10, 2 );
			add_filter( 'manage_edit-knowledgebase_sortable_columns', array( $this, 'sortable_custom_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'set_sortable_meta_key' ) );

			//Add Bulk Action to reset votes
			add_action( 'admin_footer-edit.php', array( $this, 'reset_votes_bulk_action_option' ) );
			add_action( 'load-edit.php', array( $this, 'reset_votes_bulk_action' ), 10 );
			add_action( 'admin_notices', array( $this, 'reset_votes_bulk_action_notices' ) );

			add_filter( 'basepress_search_query_pieces', array( $this, 'search_query_pieces' ) );
		}




		/**
		 * Renders the voting main div
		 *
		 * @since 1.0.0
		 *
		 * @updated 1.7.8
		 */
		public function render_voting_items() {
			global $basepress_utils, $post;
			$options = $basepress_utils->get_options();
			$voted_articles = array();

			//If the votes are not active exit
			if ( ! isset( $options['voting_active'] ) ) {
				return;
			}

			//If there are votes cookie split it into an array
			if ( isset( $_COOKIE['basepress_votes'] ) ) {
				$voted_articles = explode( '|', $_COOKIE['basepress_votes'] ); // phpcs:ignore
			}

			//Collect article votes from DB
			$votes = get_post_meta( $post->ID, 'basepress_votes', true );

			// If there are no votes for this article save theme as 0s
			if ( ! $votes ) {
				$votes = array(
					'like' => 0,
					'dislike' => 0,
				);
				update_post_meta( $post, 'basepress_votes', $votes );
				update_post_meta( $post, 'basepress_score', 0 );
			}

			$has_voted = in_array( $post->ID, $voted_articles );

			//Echo the vote items
			if( ! $has_voted ){
				echo '<div class="bpress-votes" data-post="' . get_the_ID() . '">';
			}
			else{
				echo '<div class="bpress-votes already-voted" data-post="' . get_the_ID() . '">';
			}

			if( ! $has_voted ){
				echo '<p class="bpress-votes-question">' . esc_html( $options['voting_question'] ) . '</p>';
			}
			elseif( $has_voted && isset( $options['already_voted_notice'] ) && ! empty( $options['already_voted_notice'] ) ){
				echo '<p class="bpress-votes-already-voted">' . esc_html( $options['already_voted_notice'] ) . '</p>';
			}

			if( $has_voted && isset( $options['hide_buttons_if_voted'] ) ){
				return;
			}

			$buttons_state = ! $has_voted ? ' enabled' : ' disabled';

			$icons = $basepress_utils->icons->votes;

			//Load icons from theme if in legacy mode
			if( apply_filters( 'basepress_legacy_icons', false) ){
				$icons_set = isset( $options['votes_icons'] ) ? $options['votes_icons'] : 0;
				$like_icon = isset( $icons->set[(int)$icons_set]->icon[0] ) ? (string)$icons->set[(int)$icons_set]->icon[0] : '';
				$dislike_icon = isset( $icons->set[(int)$icons_set]->icon[1] ) ? (string)$icons->set[(int)$icons_set]->icon[1] : '';
			}
			else{
				$like_icon = isset( $icons->icon[0] ) ? $icons->icon[0] : '';
				$dislike_icon = isset( $icons->icon[1] ) ? $icons->icon[1] : '';
			}


			echo '<button class="bpress-btn bpress-vote-like' . esc_attr( $buttons_state ) . '" data-post="' . esc_attr( $post->ID ) . '" data-vote="like" data-nonce="' . esc_attr( wp_create_nonce( 'bp-vote-nonce' ) ) . '">';
			ob_start();
			do_action( 'basepress_like_button_content', array( 'votes' => $votes, 'icon' => $like_icon ) );
			$like_button_content = ob_get_clean();

			if( ! empty( $like_button_content ) ){
				echo $like_button_content; // WPCS: XSS ok.
			}
			else{
				echo '<span class="bpress-vote-icon ' . esc_attr( $like_icon ) . '"></span>';
				if( isset( $options['show_votes_count'] ) ){
					echo '<span class="bpress-vote">' . esc_html( $votes['like'] ) . '</span>';
				}
			}
			echo '</button>';

			//Dislike vote button
			if ( ! isset( $options['hide_dislike_vote_button'] ) ) {
				echo '<button class="bpress-btn bpress-vote-dislike' . esc_attr( $buttons_state ) . '" data-post="' . esc_attr( $post->ID ) . '" data-vote="dislike" data-nonce="' . esc_attr( wp_create_nonce( 'bp-vote-nonce' ) ) . '">';
				ob_start();
				do_action( 'basepress_dislike_button_content', array( 'votes' => $votes, 'icon' => $like_icon ) );
				$like_button_content = ob_get_clean();

				if( ! empty( $like_button_content ) ){
					echo $like_button_content; // WPCS: XSS ok.
				}
				else{
					echo '<span class="bpress-vote-icon ' . esc_attr( $dislike_icon ) . '"></span>';
					if( isset( $options['show_votes_count'] ) ){
						echo '<span class="bpress-vote">' . esc_html( $votes['dislike'] ) . '</span>';
					}
				}
				echo '</button>';
			}

			if( isset( $options['show_voting_confirmation'] ) && isset( $options['voting_confirmation'] ) && $options['voting_confirmation'] ){
				echo '<div class="bpress-votes-confirm"></div>';
			}

			echo '</div>';
		}




		/**
		 * Updates post votes and score on DB
		 *
		 * @since 1.0.0
		 */
		public function basepress_update_votes() {
			//nonce verification
			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'bp-vote-nonce' ) ) {
				wp_die();
			}
			global $basepress_utils;
			$options = $basepress_utils->get_options();
			$post = sanitize_text_field( wp_unslash( $_GET['post'] ) );
			$vote = sanitize_text_field( wp_unslash( $_GET['vote'] ) );

			//Get the votes already saved for the post
			$votes = get_post_meta( $post, 'basepress_votes', true );

			//Get the votes count for this post and increase it by one
			$votes_count = get_post_meta( $post, 'basepress_votes_count', true );
			$votes_count = is_numeric( $votes_count) ? (int)$votes_count + 1 : 1;

			//If there are no votes set them to 0
			if ( ! $votes ) {
				$votes = array(
					'like' => 0,
					'dislike' => 0,
				);
			}

			//If is a positive vote increase the positive
			if ( 'like' == $vote ) {
				$votes['like'] = $votes['like'] + 1;
			}
			//If is a negative vote increase the negative
			if ( 'dislike' == $vote ) {
				$votes['dislike'] = $votes['dislike'] + 1;
			}

			//Calculate the score according to votes
			if( $votes['like'] > $votes['dislike'] ){
				$score = round( ( $votes['like'] / ( $votes['like'] + $votes['dislike'] ) ) * 100 );
			}
			else if( $votes['like'] < $votes['dislike'] ){
				$score = -round( ( $votes['dislike'] / ( $votes['like'] + $votes['dislike'] ) ) * 100 );
			}
			else{
				$score = 0;
			}

			//Update meta data for votes and score
			update_post_meta( $post, 'basepress_votes', $votes );
			update_post_meta( $post, 'basepress_votes_count', $votes_count );
			update_post_meta( $post, 'basepress_score', $score );

			$votes['confirmation'] = isset( $options['show_voting_confirmation'] ) && isset( $options['voting_confirmation'] ) && $options['voting_confirmation']
				? '<p>' . sanitize_text_field( $options['voting_confirmation'] ) . '</p>'
				: false;

			//Return the votes meta values
			echo wp_json_encode( $votes );
			wp_die();
		}




		/**
		 * Adds votes and score columns on post edit screen
		 *
		 * @since 1.0.0
		 *
		 * @param $columns
		 * @return array
		 */
		public function add_custom_columns( $columns ) {
			$last_column = array_slice( $columns, count( $columns ) - 1, 1, true );
			array_pop( $columns );
			$columns['basepress-votes'] = esc_html__( 'Votes', 'basepress' );
			$columns['basepress-score'] = esc_html__( 'Score %', 'basepress' );
			$columns = array_merge( $columns, $last_column );
			return $columns;
		}




		/**
		 * Adds the votes and score on relative columns on post list screen
		 *
		 * @since 1.0.0
		 *
		 * @param $column
		 * @param $post_id
		 */
		public function manage_custom_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'basepress-votes':
					$like = 0;
					$dislike = 0;
					$votes = get_post_meta( $post_id, 'basepress_votes', true );
					if ( ! is_string( $votes ) ) {
						$like = intval( $votes['like'] );
						$dislike = intval( $votes['dislike'] );
					}
					echo esc_html( $like );
					echo '<span class="dashicons dashicons-yes"></span>  ';
					echo esc_html( $dislike );
					echo '<span class="dashicons dashicons-no-alt"></span>';

					break;
				case 'basepress-score':
					$votes = get_post_meta( $post_id, 'basepress_votes', true );
					$score = get_post_meta( $post_id, 'basepress_score', true );
					$like = 0;
					$dislike = 0;
					if ( ! is_string( $votes ) ) {
						$like = intval( $votes['like'] );
						$dislike = intval( $votes['dislike'] );
					}
					if ( ! $score && ! $like && ! $dislike ) {
						echo '—';
					} else {
						echo esc_html( $score );
					}

					break;
			}
		}




		/**
		 * Makes voting score column sortable on post list screen
		 *
		 * @since 1.0.0
		 * @param $columns
		 * @return mixed
		 */
		public function sortable_custom_columns( $columns ) {
			$columns['basepress-score'] = 'basepress_score';
			return $columns;
		}




		/**
		 * Sorts posts by score on post list screen
		 *
		 * @since 1.0.0
		 *
		 * @param $query
		 */
		public function set_sortable_meta_key( $query ) {
			if ( is_admin() && ! wp_doing_ajax() && $query->is_main_query() && 'knowledgebase' == $query->query_vars['post_type']) {
				$orderby = $query->get( 'orderby' );

				if ( 'basepress_score' == $orderby ) {
					$query->set( 'meta_key', 'basepress_score' );
					$query->set( 'orderby', 'meta_value_num' );
				}
			}
		}




		/**
		 * Adds Bulk Actions to reset post votes on edit screen
		 *
		 * @since 1.0.0
		 */
		public function reset_votes_bulk_action_option() {
			global $post_type;

			if ( 'knowledgebase' == $post_type ) {
				if ( ! current_user_can( 'manage_categories' ) ) {
					return;
				}
				?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery( '<option>' ).text( '─────────' ).prop( 'disabled', true ).appendTo( "select[name='action']" );
						jQuery( '<option>' ).val( 'reset_votes' ).text( '<?php esc_html_e( 'Reset Votes (Selected)', 'basepress' ); ?>' ).appendTo( "select[name='action']" );
						jQuery( '<option>' ).val( 'reset_votes_all' ).text( '<?php esc_html_e( 'Reset Votes (All)', 'basepress' ); ?>' ).appendTo( "select[name='action']" );

						jQuery( '<option>' ).text( '─────────' ).prop( 'disabled', true ).appendTo( "select[name='action2']" );
						jQuery( '<option>' ).val( 'reset_votes' ).text( '<?php esc_html_e( 'Reset Votes (Selected)', 'basepress' ); ?>' ).appendTo( "select[name='action2']" );
						jQuery( '<option>' ).val( 'reset_votes_all' ).text( '<?php esc_html_e( 'Reset Votes (All)', 'basepress' ); ?>' ).appendTo( "select[name='action2']" );
					});
				</script>
				<?php
			}
		}




		/**
		 * Resets the votes according to bulk action
		 *
		 * @since 1.0.0
		 */
		public function reset_votes_bulk_action() {
			global $typenow, $wpdb;
			$post_type = $typenow;

			if ( 'knowledgebase' == $post_type ) {

				// get the action
				$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

				$action = $wp_list_table->current_action();

				//If the called action is not the following return
				$allowed_actions = array( 'reset_votes', 'reset_votes_all' );
				if ( ! in_array( $action, $allowed_actions ) ) {
					return;
				}

				// security check
				check_admin_referer( 'bulk-posts' );

				//Get post ids
				if ( isset( $_REQUEST['post'] ) ) {
					$post_ids = array_map( 'intval', $_REQUEST['post'] );
				}

				if ( empty( $post_ids ) && 'reset_votes_all' == $action ) {
					$post_ids = 'all';
				};

				if ( empty( $post_ids ) ) {
					return;
				}

				// this is based on wp-admin/edit.php
				$sendback = remove_query_arg( array( 'reset_votes', 'reset_views', 'untrashed', 'deleted', 'ids' ), wp_get_referer() );
				if ( ! $sendback ) {
					$sendback = admin_url( "edit.php?post_type=$post_type" );
				}

				$pagenum = $wp_list_table->get_pagenum();
				$sendback = add_query_arg( 'paged', $pagenum, $sendback );

				switch ( $action ) {

					//Reset votes for selected articles
					case 'reset_votes':
						$reset = 0;
						foreach ( $post_ids as $post_id ) {

							if ( ! $this->reset_votes( $post_id ) ) {
								wp_die( esc_html__( 'Error resetting votes.', 'basepress' ) );
							}

							$reset++;
						}

						$sendback = add_query_arg(
							array(
								'reset_votes' => $reset,
								'ids' => join( ',', $post_ids ),
							),
							$sendback
						);

						break;

					//Reset votes for all articles
					case 'reset_votes_all':
						$post_ids = array();
						$query = "
							SELECT ID
							FROM $wpdb->posts
							WHERE post_type = 'knowledgebase'
							";

						/**
						 * Fires before the querying the database to get all posts that need to be reset
						 */
						$query = apply_filters( 'basepress_votes_reset', $query );

						$all_posts = $wpdb->get_results( $query );

						$reset = 0;

						foreach ( $all_posts as $post ) {
							$post_id = $post->ID;
							$post_ids[] = $post_id;

							if ( ! $this->reset_votes( $post_id ) ) {
								wp_die( esc_html__( 'Error resetting votes.', 'basepress' ) );
							}

							$reset++;
						}

						$sendback = add_query_arg(
							array(
								'reset_votes' => $reset,
								'ids' => join( ',', $post_ids ),
							),
							$sendback
						);

						break;

					default:
						return;
				}

				$sendback = remove_query_arg(
					array(
						'action',
						'action2',
						'tags_input',
						'post_author',
						'comment_status',
						'ping_status',
						'_status',
						'post',
						'bulk_edit',
						'post_view',
					),
					$sendback
				);

				wp_redirect( $sendback );
				exit();
			}
		}




		/**
		 * Generates Response message on edit screen
		 *
		 * @since 1.0.0
		 */
		public function reset_votes_bulk_action_notices() {
			global $post_type, $pagenow;

			if ( 'edit.php' == $pagenow
				&& 'basepress' == $post_type
				&& isset( $_REQUEST['reset_votes'] )
				&& (int) $_REQUEST['reset_votes'] ) {

				// If there are no ids there were no resets skip message
				if ( isset( $_REQUEST['ids'] ) ) {
					$message = sprintf( '%s Post votes reset.', number_format_i18n( esc_html( $_REQUEST['reset_votes'] ) ) );
					echo "<div class='updated notice is-dismissible'><p>{$message}</p></div>"; // wpcs xss ok.
				}
			}
		}




		/**
		 * Resets the votes for specif post
		 *
		 * @since 1.0.0
		 *
		 * @param $post
		 * @return bool
		 */
		private function reset_votes( $post ) {
			$votes = array(
				'like' => 0,
				'dislike' => 0,
			);

			update_post_meta( $post, 'basepress_votes', $votes );
			update_post_meta( $post, 'basepress_votes_count', 0 );
			update_post_meta( $post, 'basepress_score', 0 );

			return true;
		}



		/**
		 * Adds search query pieces to includes votes on searches
		 *
		 * @since 1.7.0
		 *
		 * @param mixed $pieces
		 * @return mixed
		 */
		public function search_query_pieces( $pieces ) {
			global $wpdb;

			$pieces['join'] .= " LEFT JOIN $wpdb->postmeta AS views ON ($wpdb->posts.ID = views.post_id AND views.meta_key = 'basepress_views')";
			$pieces['join'] .= " LEFT JOIN $wpdb->postmeta AS score ON ($wpdb->posts.ID = score.post_id AND score.meta_key = 'basepress_score')";
			$pieces['orderby'] .= ', IFNULL(views.meta_value+0,0) DESC, IFNULL(score.meta_value+0,0) DESC';

			return $pieces;
		}

	} //End Class

	global $basepress_votings;
	$basepress_votings = new Basepress_Votings;
}

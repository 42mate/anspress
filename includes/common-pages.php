<?php
/**
 * Class for base page
 *
 * @package   AnsPress
 * @author    Rahul Aryan <support@anspress.io>
 * @license   GPL-3.0+
 * @link      https://anspress.io
 * @copyright 2014 Rahul Aryan
 */

/**
 * Handle output of all default pages of AnsPress
 */
class AnsPress_Common_Pages {
	/**
	 * Register all pages of AnsPress
	 */
	public static function register_common_pages() {
		ap_register_page( 'base', ap_opt( 'base_page_title' ), [ __CLASS__, 'base_page' ] );
		ap_register_page( 'question', __( 'Question', 'anspress-question-answer' ), [ __CLASS__, 'question_page' ], false );
		ap_register_page( 'ask', __( 'Ask a Question', 'anspress-question-answer' ), [ __CLASS__, 'ask_page' ] );
		ap_register_page( 'search', __( 'Search', 'anspress-question-answer' ), [ __CLASS__, 'search_page' ], false );
		ap_register_page( 'edit', __( 'Edit Answer', 'anspress-question-answer' ), [ __CLASS__, 'edit_page' ], false );
	}

	/**
	 * Layout of base page
	 */
	public static function base_page() {
		global $questions, $wp;

		$keywords   = ap_sanitize_unslash( 'ap_s', 'query_var', false );

		$tax_relation = ! empty( $wp->query_vars['ap_tax_relation'] ) ? $wp->query_vars['ap_tax_relation'] : 'OR';
		$args = array();
		$args['tax_query'] = array( 'relation' => $tax_relation );
		$args['tax_query'] = array( 'relation' => $tax_relation );

		if ( false !== $keywords ) {
			$args['s'] = $keywords;
		}

		/**
		 * Filter main question list query arguments.
		 *
		 * @param array $args Wp_Query arguments.
		 */
		$args = apply_filters( 'ap_main_questions_args', $args );

		anspress()->questions = $questions = new Question_Query( $args );
		ap_get_template_part( 'question-list' );
	}

	private static function question_permission_msg( $_post ) {
		$msg = false;

		// Check if user is allowed to read this question.
		if ( ! ap_user_can_read_question( $_post->ID ) ) {
			if ( 'moderate' === $_post->post_status ) {
				$msg = __( 'This question is awaiting moderation and cannot be viewed. Please check back later.', 'anspress-question-answer' );
			} else {
				$msg = __( 'Sorry! you are not allowed to read this question.', 'anspress-question-answer' );
			}
		} elseif ( 'future' === $_post->post_status ) {
			$time_to_publish = human_time_diff( strtotime( $_post->post_date ), current_time( 'timestamp', true ) );

			$msg = '<strong>' . sprintf(
				// Translators: %s contain time to publish.
				__( 'Question will be published in %s', 'anspress-question-answer' ),
				$time_to_publish
			) . '</strong>';

			$msg .= '<p>' . esc_attr__( 'This question is not published yet and is not accessible to anyone until it get published.', 'anspress-question-answer' ) . '</p>';
		}

		/**
		 * Filter single question page permission message.
		 *
		 * @param string $msg Message.
		 * @since 4.1.0
		 */
		$msg = apply_filters( 'ap_question_page_permission_msg', $msg );

		return $msg;
	}

	/**
	 * Output single question page.
	 */
	public static function question_page() {
		global $question_rendered, $post;
		$question_rendered = false;

		$msg = self::question_permission_msg( $post );

		// Check if user have permission.
		if ( false !== $msg ) {
			echo '<div class="ap-no-permission">' . $msg . '</div>';
			$question_rendered = true;
			return;
		}

		include( ap_get_theme_location( 'question.php' ) );

		do_action( 'ap_after_question' );

		$question_rendered = true;
	}

	/**
	 * Output ask page template
	 */
	public static function ask_page() {
		$post_id = ap_sanitize_unslash( 'id', 'r', false );

		if ( $post_id && ! ap_verify_nonce( 'edit-post-' . $post_id ) ) {
			esc_attr_e( 'Something went wrong, please try again', 'anspress-question-answer' );
			return;
		}

		include ap_get_theme_location( 'ask.php' );
	}

	/**
	 * Load search page template
	 */
	public static function search_page() {
		$keywords   = ap_sanitize_unslash( 'ap_s', 'query_var', false );
		wp_safe_redirect( add_query_arg( [ 'ap_s' => $keywords ], ap_get_link_to( '/' ) ) );
	}

	/**
	 * Output edit page template
	 */
	public static function edit_page() {
		$post_id = (int) ap_sanitize_unslash( 'id', 'r' );

		if ( ! ap_verify_nonce( 'edit-post-' . $post_id ) || empty( $post_id ) || ! ap_user_can_edit_answer( $post_id ) ) {
				echo '<p>' . esc_attr__( 'Sorry, you cannot edit this answer.', 'anspress-question-answer' ) . '</p>';
				return;
		}

		global $editing_post;
		$editing_post = ap_get_post( $post_id );

		ap_answer_form( $editing_post->post_parent, true );
	}

	/**
	 * If page is not found then set header as 404
	 */
	public static function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include ap_get_theme_location( 'not-found.php' );
	}
}


<?php
/**
 * Peer Session: Single permalink screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

/**
 * Catch and route requests for single peer session item permalinks.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_peer_session_action_permalink_router() {
	// Not viewing peer session.
	if ( ! bp_is_peer_session_component() || ! bp_is_current_action( 'p' ) )
		return false;

	// No peer session to display.
	if ( ! bp_action_variable( 0 ) || ! is_numeric( bp_action_variable( 0 ) ) )
		return false;

	// Get the peer session details.
	$peer_session = bp_peer_session_get_specific( array( 'peer_session_id' => bp_action_variable( 0 ), 'show_hidden' => true ) );

	// 404 if peer session does not exist
	if ( !$peer_session.have_posts() ) {
		bp_do_404();
		return;
	} else {
		$peer_session = $peer_session.the_post();
	}
    
    $userId = bp_loggedin_user_id();
    if ($userId == $peer_session->peer_session_creator_id) {
        $user_domain_id = $peer_session->recipient_id;
    } else {
        $user_domain_id = $peer_session->peer_session_creator_id;
    }
	$redirect = bp_core_get_user_domain( $user_domain_id ) . bp_get_peer_session_slug() . '/' . $peer_session->id . '/';

	// If set, add the original query string back onto the redirect URL.
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$query_frags = array();
		wp_parse_str( $_SERVER['QUERY_STRING'], $query_frags );
		$redirect = add_query_arg( urlencode_deep( $query_frags ), $redirect );
	}

	/**
	 * Filter the intended redirect url before the redirect occurs for the single peer session item.
	 *
	 * @since 1.2.2
	 *
	 * @param array $value Array with url to redirect to and peer session related to the redirect.
	 */
	if ( ! $redirect = apply_filters_ref_array( 'bp_peer_session_permalink_redirect_url', array( $redirect, &$peer_session ) ) ) {
		bp_core_redirect( bp_get_root_domain() );
	}

	// Redirect to the actual activity permalink page.
	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_peer_session_action_permalink_router' );

/**
 * Load the page for a single peer session item.
 *
 * @since 1.2.0
 *
 * @return bool|string Boolean on false or the template for a single activity item on success.
 */
function bp_peer_session_screen_single_peer_session_permalink() {
	// No displayed user or not viewing activity component.
	if ( ! bp_is_peer_session_component() ) {
		return false;
	}

	$action = bp_current_action();
	if ( ! $action || ! is_numeric( $action ) ) {
		return false;
	}

	// Get the activity details.
    $peer_session = bp_peer_session_get_specific( array(
		'peer_session_id' => $action,
		'show_hidden'  => true,
		'spam'         => 'ham_only',
	) );

    // 404 if activity does not exist
    if ( !$peer_session.have_posts() || bp_action_variables() ) {
		bp_do_404();
		return;

	}

	/**
	 * Check user access to the activity item.
	 *
	 * @since 3.0.0
	 */
	$has_access = bp_peer_session_user_can_read( $peer_session );

	/**
	 * Fires before the loading of a single activity template file.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Peer_Session_Peer_Session $peer_session   Object representing the current activity item being displayed.
	 * @param bool                 $has_access Whether or not the current user has access to view activity.
	 */
	do_action( 'bp_peer_session_screen_single_peer_session_permalink', $peer_session, $has_access );

	// Access is specifically disallowed.
	if ( false === $has_access ) {
		// If not logged in, prompt for login.
		if ( ! is_user_logged_in() ) {
			bp_core_no_access();

		// Redirect away.
		} else {
			bp_core_add_message( __( 'You do not have access to this peer session.', 'bp-peer-session' ), 'error' );
			bp_core_redirect( bp_loggedin_user_domain() );
		}
	}

	/**
	 * Filters the template to load for a single peer session screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the peer session template to load.
	 */
	$template = apply_filters( 'bp_peer_session_template_single_peer_session_permalink', 'peer-session/peer-session-details' );

	// Load the template.
	bp_core_load_template( $template );
}
add_action( 'bp_screens', 'bp_peer_session_screen_single_peer_session_permalink' );
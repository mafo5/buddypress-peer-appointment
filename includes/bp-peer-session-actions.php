<?php

/**
 * Check to see if a peer session is being given, and if so, save it.
 *
 * Hooked to bp_actions, this function will fire before the screen function. We use our function
 * bp_is_peer_session_component(), along with the bp_is_current_action() and bp_is_action_variable()
 * functions, to detect (based on the requested URL) whether the user has clicked on "create peer 
 * session". If so, we do a bit of simple logic to see what should happen next.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_peer_session_save() {

	if ( bp_is_peer_session_component() && bp_is_current_action( 'create-peer-session' ) ) {
		// The logged in user has clicked on the 'create peer session' link

		if ( bp_is_my_profile() || bp_displayed_user_id() == bp_loggedin_user_id() ) {
			// Don't let users create peer sessions with themselves
			bp_core_add_message( __( 'No self-sessions! :)', 'bp-peer-session' ), 'error' );
		} else {
			$session_id = bp_peer_session_have_unfinished_session( bp_displayed_user_id(), bp_loggedin_user_id() );
			if ( $session_id ) {
				bp_core_add_message( __( 'You already have an open session.', 'bp-peer-session' ) );
			} else {
				$session_id = bp_peer_session_create_peer_session( bp_displayed_user_id(), bp_loggedin_user_id() );
				if ( $session_id ) {
					bp_core_add_message( __( 'Session requested!', 'bp-peer-session' ) );
				} else {
					bp_core_add_message( __( 'Session request could not be sent.', 'bp-peer-session' ), 'error' );
				}
			}
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/' . $session_id );
	}
}
add_action( 'bp_actions', 'bp_peer_session_peer_session_save' );

function bp_peer_session_peer_session_cancel() {

	if ( bp_is_peer_session_component() && bp_is_current_action( 'cancel-peer-session' ) ) {
		// The logged in user has clicked on the 'create peer session' link

		if ( bp_is_my_profile() ) {
			// Don't let users create peer sessions with themselves
			bp_core_add_message( __( 'No self-session-cancels! :)', 'bp-peer-session' ), 'error' );
		} else {
			$session_id = bp_peer_session_cancel_peer_session( bp_displayed_user_id(), bp_loggedin_user_id() );
			if ( $session_id ) {
				bp_core_add_message( __( 'Session canceled!', 'bp-peer-session' ) );
			} else {
				bp_core_add_message( __( 'Session cancel could not be done.', 'bp-peer-session' ), 'error' );
			}
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/' . $session_id);
	}
}
add_action( 'bp_actions', 'bp_peer_session_peer_session_cancel' );

function bp_peer_session_peer_session_start() {
	// echo "<br>DEBUG action ".bp_current_action();
	// echo "<br>DEBUG variable ";
	// print_r(bp_action_variables());
	if ( bp_is_peer_session_component() && bp_is_current_action( 'start-peer-session' ) ) {
		// The logged in user has clicked on the 'create peer session' link
		$session_id = bp_action_variables()[0];

		$start_result = bp_peer_session_start_peer_session($session_id);
		// bp_peer_session_start_peer_session( bp_displayed_user_id(), bp_loggedin_user_id() );
		if ( $start_result ) {
			bp_core_add_message( __( 'Session started!', 'bp-peer-session' ) );
		} else {
			bp_core_add_message( __( 'Session start could not be done.', 'bp-peer-session' ), 'error' );
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/' . $session_id);
	}
}
add_action( 'bp_actions', 'bp_peer_session_peer_session_start' );

function bp_peer_session_peer_session_finish() {
	// echo "<br>DEBUG action ".bp_current_action();
	// echo "<br>DEBUG variable ";
	// print_r(bp_action_variables());
	if ( bp_is_peer_session_component() && bp_is_current_action( 'finish-peer-session' ) ) {
		// The logged in user has clicked on the 'create peer session' link
		$session_id = bp_action_variables()[0];

		$finish_result = bp_peer_session_finish_peer_session($session_id);
		// bp_peer_session_finish_peer_session( bp_displayed_user_id(), bp_loggedin_user_id() );
		if ( $finish_result ) {
			bp_core_add_message( __( 'Session finished!', 'bp-peer-session' ) );
		} else {
			bp_core_add_message( __( 'Session finish could not be done.', 'bp-peer-session' ), 'error' );
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/' . $session_id);
	}
}
add_action( 'bp_actions', 'bp_peer_session_peer_session_finish' );

?>
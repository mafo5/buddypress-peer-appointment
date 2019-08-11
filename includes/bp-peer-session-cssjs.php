<?php

/**
 * NOTE: You should always use the wp_enqueue_script() and wp_enqueue_style() functions to include
 * javascript and css files.
 */

function bp_peer_session_add_files() {
	$pluginPath = plugin_dir_url( __FILE__ );
	$cssfile =  $pluginPath. "/js/index.css";
	$cssfile2 =  $pluginPath. "/js/session-form.css";
	$jsfile = $pluginPath . "/js/index.js";

	wp_enqueue_script( 'simpleRTCScript', $jsfile, false, false, true );
	wp_enqueue_style( 'simpleRTCStyle', $cssfile, false );
	wp_enqueue_style( 'sessionFormStyle', $cssfile2, false );
}

/**
 * bp_peer_session_add_js()
 *
 * This function will enqueue the components javascript file, so that you can make
 * use of any javascript you bundle with your component within your interface screens.
 */
function bp_peer_session_add_js() {
	global $bp;

	if ( $bp->current_component == $bp->peerSession->slug ) {
		wp_enqueue_script( 'bp-peer-session-js', plugin_dir_url( __FILE__ ) . '/js/general.js', array( 'jquery' ) );
		bp_peer_session_add_files();
	}
}
add_action( 'template_redirect', 'bp_peer_session_add_js', 1 );

?>
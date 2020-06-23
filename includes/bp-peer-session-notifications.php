<?php



/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */


/**
 * bp_peer_session_screen_notification_settings()
 *
 * Adds notification settings for the component, so that a user can turn off email
 * notifications set on specific component actions.
 */
function bp_peer_session_screen_notification_settings() {
	global $current_user;

	/**
	 * Under Settings > Notifications within a users profile page they will see
	 * settings to turn off notifications for each component.
	 *
	 * You can plug your custom notification settings into this page, so that when your
	 * component is active, the user will see options to turn off notifications that are
	 * specific to your component.
	 */

	 /**
	  * Each option is stored in a posted array notifications[SETTING_NAME]
	  * When saved, the SETTING_NAME is stored as usermeta for that user.
	  *
	  * For example, notifications[notification_friends_friendship_accepted] could be
	  * used like this:
	  *
	  * if ( 'no' == get_user_meta( $bp->displayed_user->ID, 'notification_friends_friendship_accepted', true ) )
	  *		// don't send the email notification
	  *	else
	  *		// send the email notification.
      */

	?>
	<table class="notification-settings" id="bp-peer-session-notification-settings">

		<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Peer Session', 'bp-peer-session' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'bp-peer-session' ) ?></th>
			<th class="no"><?php _e( 'No', 'bp-peer-session' )?></th>
		</tr>
		</thead>

		<tbody>
		<tr>
			<td></td>
			<td><?php _e( 'When a user requested a session with you', 'bp-peer-session' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_peer_session_new_peer_session]" value="yes" <?php if ( !get_user_meta( $current_user->ID, 'notification_peer_session_new_peer_session', true ) || 'yes' == get_user_meta( $current_user->ID, 'notification_peer_session_new_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_peer_session_new_peer_session]" value="no" <?php if ( get_user_meta( $current_user->ID, 'notification_peer_session_new_peer_session', true) == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'When data of session with you were changed', 'bp-peer-session' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_peer_session_update_peer_session]" value="yes" <?php if ( !get_user_meta( $current_user->ID, 'notification_peer_session_update_peer_session', true ) || 'yes' == get_user_meta( $current_user->ID, 'notification_peer_session_update_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_peer_session_update_peer_session]" value="no" <?php if ( 'no' == get_user_meta( $current_user->ID, 'notification_peer_session_update_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'When a session with you was started', 'bp-peer-session' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_peer_session_start_peer_session]" value="yes" <?php if ( !get_user_meta( $current_user->ID, 'notification_peer_session_start_peer_session', true ) || 'yes' == get_user_meta( $current_user->ID, 'notification_peer_session_start_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_peer_session_start_peer_session]" value="no" <?php if ( 'no' == get_user_meta( $current_user->ID, 'notification_peer_session_start_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'When a session with you was finished', 'bp-peer-session' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_peer_session_finish_peer_session]" value="yes" <?php if ( !get_user_meta( $current_user->ID, 'notification_peer_session_finish_peer_session', true ) || 'yes' == get_user_meta( $current_user->ID, 'notification_peer_session_finish_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_peer_session_finish_peer_session]" value="no" <?php if ( 'no' == get_user_meta( $current_user->ID, 'notification_peer_session_finish_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'When a session with you was canceled', 'bp-peer-session' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_peer_session_cancel_peer_session]" value="yes" <?php if ( !get_user_meta( $current_user->ID, 'notification_peer_session_cancel_peer_session', true ) || 'yes' == get_user_meta( $current_user->ID, 'notification_peer_session_cancel_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_peer_session_cancel_peer_session]" value="no" <?php if ( 'no' == get_user_meta( $current_user->ID, 'notification_peer_session_cancel_peer_session', true ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>

		<?php do_action( 'bp_peer_session_notification_settings' ); ?>

		</tbody>
	</table>
<?php
}
add_action( 'bp_notification_settings', 'bp_peer_session_screen_notification_settings' );


/**
 * bp_peer_session_remove_screen_notifications()
 *
 * Remove a screen notification for a user.
 */
function bp_peer_session_remove_screen_notifications() {
	global $bp;

	/**
	 * When clicking on a screen notification, we need to remove it from the menu.
	 * The following command will do so.
	  */
	// echo "<br>DEBUG: ";
	// print_r($bp->loggedin_user);
	// echo "<br>DEBUG: ";
	// print_r($bp->peerSession->slug);
	if (isset($bp->loggedin_user->ID)) {
		$user_id = $bp->loggedin_user->ID;
	} else {
		$user_id = $bp->loggedin_user->id;
	}
	BP_Core_Notification::delete_for_user_by_type( $user_id, $bp->peerSession->slug, 'new_peer_session' );
}
add_action( 'bp_peer_session_peer_session_details', 'bp_peer_session_remove_screen_notifications' );
add_action( 'xprofile_screen_display_profile', 'bp_peer_session_remove_screen_notifications' );


/**
 * bp_peer_session_format_notifications()
 *
 * The format notification function will take DB entries for notifications and format them
 * so that they can be displayed and read on the screen.
 *
 * Notifications are "screen" notifications, that is, they appear on the notifications menu
 * in the site wide navigation bar. They are not for email notifications.
 *
 *
 * The recording is done by using bp_core_add_notification() which you can search for in this file for
 * examples of usage.
 */
function bp_peer_session_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	// echo "<br>DEBUG format notification: ";
	$item = bp_peer_session_get_specific(array('peer_session_id' => $item_id));
	$user_id = bp_loggedin_user_id();
	$other_user = get_other_user_id($item, $user_id);
	$other_user_fullname = bp_core_get_user_displayname( $other_user, false );
	$item_url = bp_peer_session_opposite_user_domain( $item ). $bp->peerSession->slug . '/' . $item_id;

	switch ( $action ) {
		case 'new_peer_session':
			/* In this case, $item_id is the user ID of the user who requested the peer session. */

			/***
			 * We don't want a whole list of similar notifications in a users list, so we group them.
			 * If the user has more than one action from the same component, they are counted and the
			 * notification is rendered differently.
			 */
			return apply_filters( 'bp_peer_session_single_new_peer_session_notification', '<a href="' . $item_url . '">' . sprintf( __( '%s requested a peer session with you!', 'bp-peer-session' ), $other_user_fullname ) . '</a>', $other_user_fullname );
			
		break;
		case 'update_peer_session':
			return apply_filters( 'bp_peer_session_single_update_peer_session_notification', '<a href="' . $item_url . '">' . sprintf( __( '%s updated a peer session with you!', 'bp-peer-session' ), $other_user_fullname ) . '</a>', $other_user_fullname );
		break;
		case 'cancel_peer_session':
			return apply_filters( 'bp_peer_session_single_cancel_peer_session_notification', '<a href="' . $item_url . '">' . sprintf( __( '%s canceled a peer session with you!', 'bp-peer-session' ), $other_user_fullname ) . '</a>', $other_user_fullname );
		break;
		case 'start_peer_session':
			return apply_filters( 'bp_peer_session_single_start_peer_session_notification', '<a href="' . $item_url . '">' . sprintf( __( '%s started a peer session with you!', 'bp-peer-session' ), $other_user_fullname ) . '</a>', $other_user_fullname );
		break;
		case 'finish_peer_session':
			return apply_filters( 'bp_peer_session_single_finish_peer_session_notification', '<a href="' . $item_url . '">' . sprintf( __( '%s finished a peer session with you!', 'bp-peer-session' ), $other_user_fullname ) . '</a>', $other_user_fullname );
		break;
	}

	do_action( 'bp_peer_session_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

/**
 * Notification functions are used to send email notifications to users on specific events
 * They will check to see the users notification settings first, if the user has the notifications
 * turned on, they will be sent a formatted email notification.
 *
 * You should use your own custom actions to determine when an email notification should be sent.
 */

function bp_peer_session_get_notification_data( $to_user_id, $from_user_id, $session_id ) {
	global $bp;

	/* Get the userdata for the receiver and sender, this will include usernames and emails that we need. */
	$receiver_ud = get_userdata( $to_user_id );
	$sender_ud = get_userdata( $from_user_id );

	return array(
		'sender_name' => bp_core_get_user_displayname( $from_user_id, false ),
		'receiver_name' => bp_core_get_user_displayname( $to_user_id, false ),
		'sender_profile_link' => site_url( BP_MEMBERS_SLUG . '/' . $sender_ud->user_login . '/' . $bp->profile->slug ),
		'receiver_profile_link' => site_url( BP_MEMBERS_SLUG . '/' . $receiver_ud->user_login . '/' . $bp->profile->slug ),
		'session_list_link' => site_url( BP_MEMBERS_SLUG . '/' . $receiver_ud->user_login . '/' . $bp->peerSession->slug ),
		'session_link' => site_url( BP_MEMBERS_SLUG . '/' . $sender_ud->user_login . '/' . $bp->peerSession->slug . '/' . $session_id ),
		'session_request_link' => site_url( BP_MEMBERS_SLUG . '/' . $sender_ud->user_login . '/' . $bp->peerSession->slug . '/create-peer-session' ),
		'feedback_link' => 'https://app.ux-mentorship.com/feedback/',
		'session_review_link' => 'https://app.ux-mentorship.com/session-review/',
		'members_link' => site_url( BP_MEMBERS_SLUG),
	);
}

/* Set up and send the message */
function bp_peer_session_send_email_notification($to_user_id, $headline, $message) {
	$receiver_ud = get_userdata( $to_user_id );
	$to = $receiver_ud->user_email;
	$receiver_settings_link = site_url( BP_MEMBERS_SLUG . '/' . $receiver_ud->user_login . '/settings/notifications' );

	$subject = '' . get_blog_option( 1, 'blogname' ) . ' ' . $headline;
	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'bp-peer-session' ), $receiver_settings_link );

	// Send it!
	wp_mail( $to, $subject, $message );
}

function bp_peer_session_create_peer_session_notification( $to_user_id, $from_user_id,  $session_id ) {
	/* We need to check to see if the recipient has opted not to recieve peer session emails */
	if ( 'no' == get_user_meta( (int)$to_user_id, 'notification_peer_session_new_peer_session', true ) )
		return false;
		
	$notification_data = bp_peer_session_get_notification_data($to_user_id, $from_user_id, $session_id);
	$headline = sprintf( __( '- New Session Request - %s invited you to a peer session', 'bp-peer-session' ), stripslashes($notification_data['sender_name']) );
	$message = sprintf( __('Hi %s,

	%s invited you to a peer session. Take a look at %ss profile to see if the two of you are a good fit.
	
	%s\'s Profile: %s
	
	Join the session: %s
	
	---------------------
	', 'bp-peer-session' ), $notification_data['receiver_name'], $notification_data['sender_name'], $notification_data['sender_name'], $notification_data['sender_name'], $notification_data['sender_profile_link'], $notification_data['session_link'] );
	bp_peer_session_send_email_notification($to_user_id, $headline, $message);
}
add_action( 'bp_peer_session_create_peer_session', 'bp_peer_session_create_peer_session_notification', 10, 3 );

function bp_peer_session_update_peer_session_notification( $to_user_id, $from_user_id, $session_id ) {
	/* We need to check to see if the recipient has opted not to recieve peer session emails */
	if ( 'no' == get_user_meta( (int)$to_user_id, 'notification_peer_session_update_peer_session', true ) )
		return false;
		
	$notification_data = bp_peer_session_get_notification_data($to_user_id, $from_user_id,  $session_id);
	$headline = sprintf( __( '- Session Updated - %s updated your session', 'bp-peer-session' ), stripslashes($notification_data['sender_name']) );
	$message = sprintf( __('Hi %s, 

	%s updated your peer session. You can check out the updates and make your own additions on the sessions page.

	View Updates: %s

	---------------------
	', 'bp-peer-session' ), $notification_data['receiver_name'], $notification_data['sender_name'], $notification_data['session_link'] );
	bp_peer_session_send_email_notification($to_user_id, $headline, $message);
}
add_action( 'bp_peer_session_update_peer_session', 'bp_peer_session_update_peer_session_notification', 10, 3 );

function bp_peer_session_cancel_peer_session_notification( $to_user_id, $from_user_id,  $session_id ) {
	/* We need to check to see if the recipient has opted not to recieve peer session emails */
	if ( 'no' == get_user_meta( (int)$to_user_id, 'notification_peer_session_cancel_peer_session', true ) )
		return false;
		
	$notification_data = bp_peer_session_get_notification_data($to_user_id, $from_user_id,  $session_id);
	$headline = sprintf( __( '- Session Canceled - %s canceled your session', 'bp-peer-session' ), stripslashes($notification_data['sender_name']) );
	$message = sprintf( __('Hi %s,

	We\'re sorry to inform you, that %s has canceled your session.
	Maybe you couldn\'t find a date or maybe the two of you just aren\'t a good fit.
	
	Take a look at our members to find someone else to run a session with: %s
	
	Join the session: %s

	---------------------
	', 'bp-peer-session' ), $notification_data['receiver_name'], $notification_data['sender_name'], $notification_data['members_link'], $notification_data['session_link'] );
	bp_peer_session_send_email_notification($to_user_id, $headline, $message);
}
add_action( 'bp_peer_session_cancel_peer_session', 'bp_peer_session_cancel_peer_session_notification', 10, 3 );

function bp_peer_session_start_peer_session_notification( $to_user_id, $from_user_id, $session_id ) {
	/* We need to check to see if the recipient has opted not to recieve peer session emails */
	if ( 'no' == get_user_meta( (int)$to_user_id, 'notification_peer_session_start_peer_session', true ) )
		return false;
		
	$notification_data = bp_peer_session_get_notification_data($to_user_id, $from_user_id, $session_id);
	$headline = sprintf( __( '- Session Started - %s started your session', 'bp-peer-session' ), stripslashes($notification_data['sender_name']) );
	$message = sprintf( __('Hi %s,
	
	%s started a peer session with you! Take a look at the profile and join your video peer session.

	%s\'s profile: %s
	
	Join your video peer session: %s
	
	---------------------
	', 'bp-peer-session' ), $notification_data['sender_name'], $notification_data['sender_name'], $notification_data['sender_profile_link'], $notification_data['session_link'] );
	bp_peer_session_send_email_notification($to_user_id, $headline, $message);
}
add_action( 'bp_peer_session_start_peer_session', 'bp_peer_session_start_peer_session_notification', 10, 3 );

function bp_peer_session_finish_peer_session_notification( $to_user_id, $from_user_id, $session_id ) {
	/* We need to check to see if the recipient has opted not to recieve peer session emails */
	if ( 'no' == get_user_meta( (int)$to_user_id, 'notification_peer_session_finish_peer_session', true ) )
		return false;
		
	$notification_data = bp_peer_session_get_notification_data($to_user_id, $from_user_id, $session_id);
	$headline = sprintf( __( '- Session Review - Please review your session with %s', 'bp-peer-session' ), stripslashes($notification_data['sender_name']) );
	$message = sprintf( __('Hi %s,

	Kudos to you, you just finished your session with %s.
	We would love to know how it went to improve your experience.

	Please take the time to review your session at %s
	
	---------------------
	', 'bp-peer-session' ), $notification_data['receiver_name'], $notification_data['sender_name'], $notification_data['session_review_link'] );
	bp_peer_session_send_email_notification($to_user_id, $headline, $message);
}
add_action( 'bp_peer_session_finish_peer_session', 'bp_peer_session_finish_peer_session_notification', 10, 3 );

?>

<?php

/**
 * The -functions.php file is a good place to store miscellaneous functions needed by your plugin.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */

/**
 * bp_peer_session_load_template_filter()
 *
 * You can define a custom load template filter for your component. This will allow
 * you to store and load template files from your plugin directory.
 *
 * This will also allow users to override these templates in their active theme and
 * replace the ones that are stored in the plugin directory.
 *
 * If you're not interested in using template files, then you don't need this function.
 *
 * This will become clearer in the function bp_peer_session_peer_session_details() when you want to load
 * a template file.
 */
function bp_peer_session_load_template_filter( $found_template, $templates ) {
	global $bp;

	/**
	 * Only filter the template location when we're on the peer session component pages.
	 */
	if ( $bp->current_component != $bp->peerSession->slug )
		return $found_template;

	// $found_template is not empty when the older template files are found in the
	// parent and child theme
	//
	//  /wp-content/themes/YOUR-THEME/members/single/peer-session.php
	//
	// The older template files utilize a full template ( get_header() +
	// get_footer() ), which sucks for themes and theme compat.
	//
	// When the older template files are not found, we use our new template method,
	// which will act more like a template part.
	if ( empty( $found_template ) ) {
		// register our theme compat directory
		//
		// this tells BP to look for templates in our plugin directory last
		// when the template isn't found in the parent / child theme
		bp_register_template_stack( 'bp_peer_session_get_template_directory', 14 );
		// locate_template() will attempt to find the plugins.php template in the
		// child and parent theme and return the located template when found
		//
		// plugins.php is the preferred template to use, since all we'd need to do is
		// inject our content into BP
		//
		// note: this is only really relevant for bp-default themes as theme compat
		// will kick in on its own when this template isn't found
		$found_template = locate_template( 'members/single/plugins.php', false, false );
		// add our hook to inject content into BP
		//
		// note the new template name for our template part
		add_action( 'bp_template_content', function() use ($templates) {
			foreach ($templates as $template) {
				bp_get_template_part(str_replace(".php", "", $template));
			}
		} );
	}

	return apply_filters( 'bp_peer_session_load_template_filter', $found_template );
}
add_filter( 'bp_located_template', 'bp_peer_session_load_template_filter', 10, 2 );

/**
 * Get the BP Peer Session template directory.
 *
 * @since 1.7
 *
 * @uses apply_filters()
 * @return string
 */
function bp_peer_session_get_template_directory() {
	return apply_filters( 'bp_peer_session_get_template_directory', constant( 'BP_PEER_SESSION_PLUGIN_DIR' ) . '/includes/templates' );
}

/***
 * From now on you will want to add your own functions that are specific to the component you are developing.
 * For example, in this section in the friends component, there would be functions like:
 *    friends_add_friend()
 *    friends_remove_friend()
 *    friends_check_friendship()
 *
 * Some guidelines:
 *    - Don't set up error messages in these functions, just return false if you hit a problem and
 *	deal with error messages in screen or action functions.
 *
 *    - Don't directly query the database in any of these functions. Use database access classes
 * 	or functions in your bp-peer-session-classes.php file to fetch what you need. Spraying database
 * 	access all over your plugin turns into a maintenance nightmare, trust me.
 *
 *    - Try to include add_action() functions within all of these functions. That way others will
 *	find it easy to extend your component without hacking it to pieces.
 */

/**
 * bp_peer_session_accept_terms()
 *
 * Accepts the terms and conditions screen for the logged in user.
 * Records an activity stream item for the user.
 */
function bp_peer_session_accept_terms() {
	global $bp;

	/**
	 * First check the nonce to make sure that the user has initiated this
	 * action. Remember the wp_nonce_url() call? The second parameter is what
	 * you need to check for.
	 */
	check_admin_referer( 'bp_peer_session_accept_terms' );

	/***
	 * Here is a good example of where we can post something to a users activity stream.
	 * The user has excepted the terms on screen two, and now we want to post
	 * "Andy accepted the really exciting terms and conditions!" to the stream.
	 */
	$user_link = bp_core_get_userlink( $bp->loggedin_user->id );

	bp_peer_session_record_activity( array(
		'type' => 'accepted_terms',
		'action' => apply_filters( 'bp_peer_session_accepted_terms_activity_action', sprintf( __( '%s accepted the really exciting terms and conditions!', 'bp-peer-session' ), $user_link ), $user_link ),
	) );

	/* See bp_peer_session_reject_terms() for an explanation of deleting activity items */
	if ( function_exists( 'bp_activity_delete') )
		bp_activity_delete( array( 'type' => 'rejected_terms', 'user_id' => $bp->loggedin_user->id ) );

	/* Add a do_action here so other plugins can hook in */
	do_action( 'bp_peer_session_accept_terms', $bp->loggedin_user->id );

	/***
	 * You'd want to do something here, like set a flag in the database, or set usermeta.
	 * just for the sake of the demo we're going to return true.
	 */

	return true;
}

/**
 * bp_peer_session_reject_terms()
 *
 * Rejects the terms and conditions screen for the logged in user.
 * Records an activity stream item for the user.
 */
function bp_peer_session_reject_terms() {
	global $bp;

	check_admin_referer( 'bp_peer_session_reject_terms' );

	/***
	 * In this peer session component, the user can reject the terms even after they have
	 * previously accepted them.
	 *
	 * If a user has accepted the terms previously, then this will be in their activity
	 * stream. We don't want both 'accepted' and 'rejected' in the activity stream, so
	 * we should remove references to the user accepting from all activity streams.
	 * A real world example of this would be a user deleting a published blog post.
	 */

	$user_link = bp_core_get_userlink( $bp->loggedin_user->id );

	/* Now record the new 'rejected' activity item */
	bp_peer_session_record_activity( array(
		'type' => 'rejected_terms',
		'action' => apply_filters( 'bp_peer_session_rejected_terms_activity_action', sprintf( __( '%s rejected the really exciting terms and conditions.', 'bp-peer-session' ), $user_link ), $user_link ),
	) );

	/* Delete any accepted_terms activity items for the user */
	if ( function_exists( 'bp_activity_delete') )
		bp_activity_delete( array( 'type' => 'accepted_terms', 'user_id' => $bp->loggedin_user->id ) );

	do_action( 'bp_peer_session_reject_terms', $bp->loggedin_user->id );

	return true;
}

function bp_peer_session_have_unfinished_session( $to_user_id, $from_user_id ) {
	$peer_session = find_session($to_user_id, $from_user_id);
	// echo "<br>DEBUG found session ";
	// print_r($peer_session);
	if ($peer_session && ($peer_session->state == 0 ||  $peer_session->state == 1)) {
		return $peer_session->id;
	}
	return false;
}

/**
 * bp_peer_session_create_peer_session()
 *
 * Sends a peer session message to a user. Registers an notification to the user
 * via their notifications menu, as well as sends an email to the user.
 *
 * Also records an activity stream item saying "User 1 requested a session with User 2".
 */
function bp_peer_session_create_peer_session( $to_user_id, $from_user_id ) {
	global $bp;

	// echo "<br>DEBUG create session ";
	// echo "<br>DEBUG to ".$to_user_id;
	// echo "<br>DEBUG from ".$from_user_id;

	check_admin_referer( 'bp_peer_session_create_peer_session' );

	/**
	 * We'll store peer-session as usermeta, so we don't actually need
	 * to do any database querying. If we did, and we were storing them
	 * in a custom DB table, we'd want to reference a function in
	 * bp-peer-session-classes.php that would run the SQL query.
	 */
	delete_user_meta( $to_user_id, 'peer-session' );
	/* Get existing fives */
	$existing_session_list = maybe_unserialize( get_user_meta( $to_user_id, 'peer-session', true ) );
	if (!$existing_session_list) {
		$existing_session_list = array();
	}

	/* Check to see if the user has already created a peer session. That's okay, but lets not
	 * store duplicate peer-session in the database. What's the point, right?
	 */
	if ( !in_array( $from_user_id, (array)$existing_session_list ) ) {
		$existing_session_list[] = (int)$from_user_id;

		/* Now wrap it up and fire it back to the database overlords. */
		update_user_meta( $to_user_id, 'peer-session', serialize( $existing_session_list ) );

		// FIXME currently user meta doesn't work
		// we look into the DB if there is already a peer session 
		$peer_session = find_session($to_user_id, $from_user_id);
		// echo "<br>DEBUG found session ";
		// print_r($peer_session);
		if (!$peer_session || $peer_session->state > 1) {
			// no existing peer sessions
			// Let's also record it in our custom database tables
			$db_args = array(
				'recipient_id'  => (int)$to_user_id,
				'peer_session_creator_id' => (int)$from_user_id
			);

			// echo "<br> DEBUG create session: ";
			// print_r($db_args);

			$peer_session = new BP_Peer_Session_Peer_Session( $db_args );
			$peer_session_id = $peer_session->save();

			/***
			 * Now we've registered the new peer session, lets work on some notification and activity
			 * stream magic.
			 */
		
			/***
			 * Post a screen notification to the user's notifications menu.
			 * Remember, like activity streams we need to tell the activity stream component how to format
			 * this notification in bp_peer_session_format_notifications() using the 'new_peer_session' action.
			 */
			bp_notifications_add_notification( array(
				'item_id'           => $peer_session_id,
				'user_id'           => $to_user_id,
				'component_name'    => $bp->peerSession->slug,
				'component_action'  => 'new_peer_session',
				'secondary_item_id' => 0,
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
				'allow_duplicate'   => false
			));
		
			/* Now record the new 'new_peer_session' activity item */
			$to_user_link = bp_core_get_userlink( $to_user_id );
			$from_user_link = bp_core_get_userlink( $from_user_id );
		
			bp_peer_session_record_activity( array(
				'type' => 'rejected_terms',
				'action' => apply_filters( 'bp_peer_session_new_peer_session_activity_action', sprintf( __( '%s requested a peer session with %s!', 'bp-peer-session' ), $from_user_link, $to_user_link ), $from_user_link, $to_user_link ),
				'item_id' => $to_user_id,
			) );
		
			/* We'll use this do_action call to send the email notification. See bp-peer-session-notifications.php */
			do_action( 'bp_peer_session_create_peer_session', $to_user_id, $from_user_id, $peer_session_id );
		}
	}

	if ( isset( $peer_session_id) ) {
		return $peer_session_id;
	}

	if ( isset($peer_session) ) {
		// echo "<br>DEBUG session ";
		// print_r($peer_session);
		return $peer_session->id;
	}
	return false;
}

function find_session($to_user_id, $from_user_id) {
	$search_args = array(
		'recipient_id'  => (int)$to_user_id,
		'peer_session_creator_id' => (int)$from_user_id
	);
	$peer_session = new BP_Peer_Session_Peer_Session();
	$peer_session->get( $search_args );

	if ($peer_session->have_posts()) {
		// echo "<br>DEBUG find session ";
		// print_r($peer_session);
		return $peer_session->the_post();
	}
	// not the recipient - looking for creator
	$search_args = array(
		'peer_session_creator_id'  => (int)$to_user_id,
		'recipient_id' => (int)$from_user_id
	);
	$peer_session = new BP_Peer_Session_Peer_Session();
	$peer_session->get( $search_args );
		
	if ($peer_session->have_posts()) {
		// echo "<br>DEBUG find session ";
		// print_r($peer_session);
		return $peer_session->the_post();
	}

	// nothing found
	return null;
}

function bp_peer_session_cancel_peer_session( $to_user_id, $from_user_id ) {
	global $bp;

	check_admin_referer( 'bp_peer_session_cancel_peer_session' );

	$peer_session = find_session($to_user_id, $from_user_id);

	if ($peer_session) {
		$peer_session->state = 3;
		$output = $peer_session->save();
		if ($output) {
			bp_notifications_add_notification( array(
				'item_id'           => $peer_session->id,
				'user_id'           => $to_user_id,
				'component_name'    => $bp->peerSession->slug,
				'component_action'  => 'cancel_peer_session',
				'secondary_item_id' => 0,
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
				'allow_duplicate'   => false
			));
		
			/* We'll use this do_action call to send the email notification. See bp-peer-session-notifications.php */
			do_action( 'bp_peer_session_cancel_peer_session', $to_user_id, $from_user_id, $peer_session->id );
		}
		return $output;
	}
	return false;
}

function bp_peer_session_start_peer_session( $session_id ) {
	global $bp;

	check_admin_referer( 'bp_peer_session_start_peer_session' );

	$peer_session = bp_peer_session_get_specific(array('peer_session_id' => $session_id));

	if ($peer_session) {
		$peer_session->state = 1;
		$peer_session->date_started = new DateTime('now');
		$output = $peer_session->save();
		if (isset($output)) {
			$user_id = bp_loggedin_user_id();
			$to_user_id = get_other_user_id($peer_session, $user_id);
			bp_notifications_add_notification( array(
				'item_id'           => $session_id,
				'user_id'           => $to_user_id,
				'component_name'    => $bp->peerSession->slug,
				'component_action'  => 'start_peer_session',
				'secondary_item_id' => 0,
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
				'allow_duplicate'   => false
			));
		
			/* We'll use this do_action call to send the email notification. See bp-peer-session-notifications.php */
			do_action( 'bp_peer_session_start_peer_session', $to_user_id, $user_id, $session_id );
		}
		return $output;
	}
	return false;
}

function bp_peer_session_finish_peer_session( $session_id ) {
	global $bp;

	check_admin_referer( 'bp_peer_session_finish_peer_session' );

	$peer_session = bp_peer_session_get_specific(array('peer_session_id' => $session_id));

	if ($peer_session) {
		$peer_session->state = 2;
		$peer_session->date_finished = new DateTime('now');
		$output = $peer_session->save();
		if ($output) {
			$user_id = bp_loggedin_user_id();
			$to_user_id = get_other_user_id($peer_session, $user_id);
			bp_notifications_add_notification( array(
				'item_id'           => $session_id,
				'user_id'           => $to_user_id,
				'component_name'    => $bp->peerSession->slug,
				'component_action'  => 'finish_peer_session',
				'secondary_item_id' => 0,
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
				'allow_duplicate'   => false
			));
		
			/* We'll use this do_action call to send the email notification. See bp-peer-session-notifications.php */
			do_action( 'bp_peer_session_finish_peer_session', $to_user_id, $user_id, $session_id );
			do_action( 'bp_peer_session_finish_peer_session', $user_id, $to_user_id, $session_id );
		}
		return $output;
	}
	return false;
}

/**
 * bp_peer_session_update_peer_session()
 *
 * Updated session data from POST
 */
function bp_peer_session_update_peer_session($peer_session) {
	global $bp;
	if ( isset( $_POST['_wpnonce'] ) ) {

		check_admin_referer( 'bp_peer_session_update_peer_session' );

		// echo "DEBUG POST ";
		// print_r($_POST);

		$user_id = bp_loggedin_user_id();
		$is_creator = $user_id == $peer_session->peer_session_creator_id;
		// get field values
		$agenda = $_POST['agenda_user'];

		if (!$peer_session->date_selected && isset($_POST['data_accepted']) ) {
			$date_suggestion_accepted = $_POST['data_accepted'];
			// echo "<br>DEBUG change data accept to ".$date_suggestion_accepted;
			// echo "<br>DEBUG select from: ";
			$date_suggestion_accepted = new DateTime($date_suggestion_accepted);
			// print_r($peer_session->date_suggestion_list);
			foreach ($peer_session->date_suggestion_list as $date_suggestion) {
				// echo "<br>DEBUG date suggestion: ";
				// print_r($date_suggestion);
				if ($date_suggestion->value == $date_suggestion_accepted) {
					$peer_session->date_selected = $date_suggestion_accepted;
					// echo "<br>DEBUG selected date ";
					// print_r($peer_session->date_selected);
				}
			}
			if (!$peer_session->date_selected) {
				bp_core_add_message( __( 'Please select one of the dates!', 'bp-peer-session' ), 'error' );
			}
		} else if (!$peer_session->date_selected) {
			// echo "<br>DEBUG date_suggestion_list before save ";
			// print_r($peer_session->date_suggestion_list);
			$suggestion_list = array();
			for ($i = 1; $i <= 161; $i++) {
				if ( isset($_POST['date'.$i]) ) {
					$date_suggestion_value = $_POST['date'.$i];
					// echo "<br>DEBUG create ".$index.". data with ".$date_suggestion_value;
					$date = new DateTime($date_suggestion_value);
					// echo "<br>DEBUG date: ";
					// print_r($date);
					array_push($suggestion_list, new BP_Peer_Session_Peer_Session_Date(array(
						'creator_id' => $user_id,
						'value' => $date,
						'accepted' => false
					)));
				}
			}
			// echo "<br>DEBUG suggestion_list after save ";
			// print_r($suggestion_list);

			if (count($suggestion_list)) {
				$peer_session->date_suggestion_list = $suggestion_list;
			} else {
				bp_core_add_message( __( 'Please select a date!', 'bp-peer-session' ), 'error' );
			}
		}

		$skill = $_POST['skill_list'];
		// update fields
		if ($is_creator) {
			// creator
			$peer_session->agenda_creator = $agenda;
			$peer_session->skill_selection_creator = $skill;
		} else {
			// recipient
			$peer_session->agenda_recipient = $agenda;
			$peer_session->skill_selection_recipient = $skill;
		}

		// save 
		$peer_session->save();
		bp_core_add_message( __( 'Session changes saved.', 'bp-peer-session' ) );
		$to_user_id = get_other_user_id($peer_session, $user_id);
		bp_notifications_add_notification( array(
			'item_id'           => $peer_session->id,
			'user_id'           => $to_user_id,
			'component_name'    => $bp->peerSession->slug,
			'component_action'  => 'update_peer_session',
			'secondary_item_id' => 0,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
			'allow_duplicate'   => true
		));
		
		/* We'll use this do_action call to send the email notification. See bp-peer-session-notifications.php */
		do_action( 'bp_peer_session_update_peer_session', $to_user_id, $user_id, $peer_session->id );
	}
}
add_action( 'bp_peer_session_screen_single_peer_session_permalink', 'bp_peer_session_update_peer_session' );

/**
 * bp_peer_session_get_peer_sessions_for_user()
 *
 * Returns an array of user ID's for users who have requested a peer session with the user passed to the function.
 */
function bp_peer_session_get_peer_sessions_for_user( $user_id ) {
	global $bp;

	if ( !$user_id )
		return false;

	return maybe_unserialize( get_user_meta( $user_id, 'peer-session', true ) );
}

/**
 * Fetch specific peer session items.
 *
 * @since 1.2.0
 *
 * @see BP_Peer_Session_Peer_Session::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Peer_Session_Peer_Session::get(),
 *     except for the following:
 *     @type string|int|array Single peer session ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $peer_session See BP_Peer_Session_Peer_Session::get() for description.
 */
function bp_peer_session_get_specific( $args = '' ) {

	$r = bp_parse_args( $args, array(
		'peer_session_id'      => false,      // A single peer_session_id or array of IDs.
		'display_comments'  => false,      // True or false to display threaded comments for these specific activity items.
		'max'               => false,      // Maximum number of results to return.
		'page'              => 1,          // Page 1 without a per_page will result in no pagination.
		'per_page'          => false,      // Results per page.
		'show_hidden'       => true,       // When fetching specific items, show all.
		'sort'              => 'DESC',     // Sort ASC or DESC
		'spam'              => 'ham_only', // Retrieve items marked as spam.
		'update_meta_cache' => true,
	), 'peer_session_get_specific' );

	$get_args = array(
		//'display_comments'  => $r['display_comments'],
		'id'                => $r['peer_session_id'],
		//'max'               => $r['max'],
		//'page'              => $r['page'],
		//'per_page'          => $r['per_page'],
		//'show_hidden'       => $r['show_hidden'],
		//'sort'              => $r['sort'],
		//'spam'              => $r['spam'],
		//'update_meta_cache' => $r['update_meta_cache'],
	);

	/**
	 * Filters the requested specific peer session item.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Peer_Session_Peer_Session $peer_session Requested peer session object.
	 * @param array                $args     Original passed in arguments.
	 * @param array                $get_args Constructed arguments used with request.
	 */
	$peer_session = new BP_Peer_Session_Peer_Session($get_args);
	return apply_filters( 'bp_peer_session_get_specific', $peer_session , $args, $get_args );
}

function bp_peer_session_user_can_read( $peer_session, $user_id = 0 ) {
	$retval = false;

	// Fallback.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ($peer_session->peer_session_creator_id == $user_id) {
		$retval = true;
	}

	if ($peer_session->recipient_id == $user_id) {
		$retval = true;
	}

	return $retval;
}

/**
 * bp_peer_session_remove_data()
 *
 * It's always wise to clean up after a user is deleted. This stops the database from filling up with
 * redundant information.
 */
function bp_peer_session_remove_data( $user_id ) {
	/* You'll want to run a function here that will delete all information from any component tables
	   for this $user_id */

	/* Remember to remove usermeta for this component for the user being deleted */
	delete_user_meta( $user_id, 'bp_peer_session_some_setting' );

	do_action( 'bp_peer_session_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_peer_session_remove_data', 1 );
add_action( 'delete_user', 'bp_peer_session_remove_data', 1 );



/**
 * Register notifications filters for the activity component.
 *
 * @since 3.0.0
 */
function bp_nouveau_peer_session_notification_filters() {
	$notifications = array(
		array(
			'id'       => 'new_peer_session',
			'label'    => __( 'New session request', 'peer-session' ),
			'position' => 5,
		),
		array(
			'id'       => 'update_peer_session',
			'label'    => __( 'Session was updated', 'peer-session' ),
			'position' => 15,
		),
		array(
			'id'       => 'start_peer_session',
			'label'    => __( 'Session was started', 'peer-session' ),
			'position' => 25,
		),
		array(
			'id'       => 'cancel_peer_session',
			'label'    => __( 'Session was canceled', 'peer-session' ),
			'position' => 35,
		),
		array(
			'id'       => 'finish_peer_session',
			'label'    => __( 'Session was finished', 'peer-session' ),
			'position' => 45,
		),
	);

	foreach ( $notifications as $notification ) {
		bp_nouveau_notifications_register_filter( $notification );
	}
}

/***
 * Object Caching Support ----
 *
 * It's a good idea to implement object caching support in your component if it is fairly database
 * intensive. This is not a requirement, but it will help ensure your component works better under
 * high load environments.
 *
 * In parts of this peer session component you will see calls to wp_cache_get() often in template tags
 * or custom loops where database access is common. This is where cached data is being fetched instead
 * of querying the database.
 *
 * However, you will need to make sure the cache is cleared and updated when something changes. For example,
 * the groups component caches groups details (such as description, name, news, number of members etc).
 * But when those details are updated by a group admin, we need to clear the group's cache so the new
 * details are shown when users view the group or find it in search results.
 *
 * We know that there is a do_action() call when the group details are updated called 'groups_settings_updated'
 * and the group_id is passed in that action. We need to create a function that will clear the cache for the
 * group, and then add an action that calls that function when the 'groups_settings_updated' is fired.
 *
 * Peer Session:
 *
 *   function groups_clear_group_object_cache( $group_id ) {
 *	     wp_cache_delete( 'groups_group_' . $group_id );
 *	 }
 *	 add_action( 'groups_settings_updated', 'groups_clear_group_object_cache' );
 *
 * The "'groups_group_' . $group_id" part refers to the unique identifier you gave the cached object in the
 * wp_cache_set() call in your code.
 *
 * If this has completely confused you, check the function documentation here:
 * http://codex.wordpress.org/Function_Reference/WP_Cache
 *
 * If you're still confused, check how it works in other BuddyPress components, or just don't use it,
 * but you should try to if you can (it makes a big difference). :)
 */

?>
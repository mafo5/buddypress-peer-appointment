<?php

/**
 * In this file you should define template tag functions that end users can add to their template
 * files.
 *
 * It's a general practice in WordPress that template tag functions have two versions, one that
 * returns the requested value, and one that echoes the value of the first function. The naming
 * convention is usually something like 'bp_peer_session_get_item_name()' for the function that returns
 * the value, and 'bp_peer_session_item_name()' for the function that echoes.
 */

/**
 * If you want to go a step further, you can create your own custom WordPress loop for your component.
 * By doing this you could output a number of items within a loop, just as you would output a number
 * of blog posts within a standard WordPress loop.
 *
 * The peer session template class below would allow you do the following in the template file:
 *
 * 	<?php if ( bp_get_peer_session_has_items() ) : ?>
 *
 *		<?php while ( bp_get_peer_session_items() ) : bp_get_peer_session_the_item(); ?>
*
*			<p><?php bp_get_peer_session_item_name() ?></p>
*
*		<?php endwhile; ?>
*
*	<?php else : ?>
*
*		<p class="error">No items!</p>
*
*	<?php endif; ?>
*
* Obviously, you'd want to be more specific than the word 'item'.
*
* In our example here, we've used a custom post type for storing and fetching our content. Though
* the custom post type method is recommended, you can also create custom database tables for this
* purpose. See bp-peer-session-classes.php for more details.
*
*/

function bp_peer_session_has_items( $args = '' ) {
	global $bp, $items_template;

	// This keeps us from firing the query more than once
	if ( empty( $items_template ) ) {
		$items_template = callDB($args);
	}

	return $items_template->have_posts();
}

function callDB($args) {
	/***
	 * This function should accept arguments passes as a string, just the same
	 * way a 'query_posts()' call accepts parameters.
	 * At a minimum you should accept 'per_page' and 'max' parameters to determine
	 * the number of items to show per page, and the total number to return.
	 *
	 * e.g. bp_get_peer_session_has_items( 'per_page=10&max=50' );
	 */

	/***
	 * Set the defaults for the parameters you are accepting via the "bp_get_peer_session_has_items()"
	 * function call
	 */
	$defaults = array(
		'peer_session_creator_id' => 0,
		'recipient_id'  => 0,
		'per_page'      => 10,
		'paged'		=> 1
	);

	/***
	 * This function will extract all the parameters passed in the string, and turn them into
	 * proper variables you can use in the code - $per_page, $max
	 */
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$items_template = new BP_Peer_Session_Peer_Session();
	$items_template->get( $r );
	return $items_template;
}

function bp_peer_session_has_items_for_displayed_user( $args = '' ) {
	global $items_template;
	if ( empty( $items_template ) ) {
		$display_creator = callDB(
			wp_parse_args(array(
				'peer_session_creator_id' => bp_displayed_user_id()
			), $args)
		);
		$have_session_for_display_creator = $display_creator->have_posts();
		$display_recipient = callDB(
			wp_parse_args(array(
				'recipient_id' => bp_displayed_user_id()
			), $args)
		);
		$have_session_for_display_recipient = $display_recipient->have_posts();
		if ($have_session_for_display_creator && $have_session_for_display_recipient) {
			$wp_query = new WP_Query();
			$wp_query->posts = array_merge( $display_creator->query->posts, $display_recipient->query->posts );
			$wp_query->post_count = count($wp_query->posts);
			$items_template = new BP_Peer_Session_Peer_Session();
			$items_template->query = $wp_query;
		} elseif ($have_session_for_display_creator && !$have_session_for_display_recipient) {
			$items_template = $display_creator;
		} else {
			$items_template = $display_recipient;
		}
	}
	return $items_template->have_posts();
}

function bp_peer_session_the_item() {
	global $items_template;
	return $items_template->the_post();
}

function bp_peer_session_item_id() {
	echo bp_peer_session_get_item_id();
}

function bp_peer_session_get_item_id() {
	global $items_template;
	echo apply_filters( 'bp_peer_session_get_item_id', get_the_ID() );
}

function bp_peer_session_item_name() {
	echo bp_peer_session_get_item_name();
}

function get_other_user_id($peer_session, $user_id) {
	if ($peer_session->peer_session_creator_id == $user_id) {
		return $peer_session->recipient_id;
	} else {
		return $peer_session->peer_session_creator_id;
	}
}

function javascript_stingify($v) {
	return("`".$v."`");
}

function stingify($textarea_input) {
	$textarea_array = explode("\n", str_replace("\r", "", $textarea_input));
	return implode(",", array_map("javascript_stingify", $textarea_array));
}

/* Always provide a "get" function for each template tag, that will return, not echo. */
function bp_peer_session_get_item_name() {
	global $items_template;
	return apply_filters( 'bp_peer_session_get_item_name', $items_template->item->name ); // Peer Session: $items_template->item->name;
}

function bp_peer_session_agend_for_user($item, $user_id) {
	echo nl2br(bp_peer_session_get_agend_for_user($item, $user_id));
}

function bp_peer_session_get_agend_for_user($item, $user_id) {
	if ($item->peer_session_creator_id == $user_id) {
		$retval = $item->agenda_creator;
	} else  {
		$retval = $item->agenda_recipient;
	}
	if (is_array($retval)) {
		if (count($retval)) {
			$retval = $retval[0];
		} else {
			$retval = '';
		}
	}
	return apply_filters( 'bp_peer_session_get_agend_for_user', $retval, $user_id);
}

/**
 * Echo "Viewing x of y pages"
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_pagination_count() {
	echo bp_peer_session_get_pagination_count();
}

/**
 * Return "Viewing x of y pages"
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_get_pagination_count() {
	global $items_template;

	$pagination_count = sprintf( __( 'Viewing page %1$s of %2$s', 'bp-peer-session' ), $items_template->query->query_vars['paged'], $items_template->query->max_num_pages );

	return apply_filters( 'bp_peer_session_get_pagination_count', $pagination_count );
}

/**
 * Echo pagination links
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_item_pagination() {
	echo bp_peer_session_get_item_pagination();
}

/**
 * return pagination links
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_get_item_pagination() {
	global $items_template;
	return apply_filters( 'bp_peer_session_get_item_pagination', $items_template->pag_links );
}

/**
 * Echo the peer session avatar (post author)
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_peer_session_creator_avatar( $args = array() ) {
	echo bp_peer_session_get_peer_session_creator_avatar( wp_parse_args($args, array(
		'user_id' => get_the_author_meta( 'ID' )
	)) );
}


function bp_peer_session_peer_session_user_avatar( $args = array() ) {
	echo bp_peer_session_get_peer_session_creator_avatar( $args );
}

/**
 * Return the peer session avatar (the post author)
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @param mixed $args Accepts WP style arguments - either a string of URL params, or an array
 * @return str The HTML for a user avatar
 */
function bp_peer_session_get_peer_session_creator_avatar( $args = array() ) {
	$defaults = array(
		'item_id' => $args['user_id'],
		'object'  => 'user'
	);

	$r = wp_parse_args( $args, $defaults );

	return bp_core_fetch_avatar( $r );
}

function bp_peer_session_peer_session_state($item) {
	echo bp_peer_session_get_peer_session_state( $item );
}
function bp_peer_session_get_peer_session_state($item) {
	switch ($item->state) {
		case 0:
			return "created";
			break;
		case 1:
			return "started";
			break;
		case 2:
			return "finished";
			break;
		case 3:
			return "canceled";
			break;
	}
	return 'undefined';
}

function user_is_session_participant() {
	$user_id = bp_loggedin_user_id();
	$creator_id = get_the_author_meta( 'ID' );
	$recipient_id    = get_post_meta( get_the_ID(), 'bp_peer_session_recipient_id', true );
	return $user_id == $creator_id || $user_id == $recipient_id;
}

/**
 * Echo the "title" of the peer session
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_peer_session_title() {
	echo bp_peer_session_get_peer_session_title();
}

/**
 * Return the "title" of the peer session
 *
 * We'll assemble the title out of the available information. This way, we can insert
 * fancy stuff link links, and secondary avatars.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_get_peer_session_title() {
	// First, set up the peer session creator information
	$peer_session_creator_link = bp_core_get_userlink( get_the_author_meta( 'ID' ) );

	// Next, get the information for the peer session recipient
	$recipient_id    = get_post_meta( get_the_ID(), 'bp_peer_session_recipient_id', true );
	$recipient_link  = bp_core_get_userlink( $recipient_id );
	$user_id = bp_loggedin_user_id();
	$creator_id = get_the_author_meta( 'ID' );

	// Use sprintf() to make a translatable message
	if ($user_id == $recipient_id) {
		$title 		 = sprintf( __( '%1$s requested a peer session with you', 'bp-peer-session' ), $peer_session_creator_link );
	} elseif ($user_id == $creator_id) {
		$title 		 = sprintf( __( 'You requested a peer session with %1$s', 'bp-peer-session' ), $recipient_link );
	} else {
		$title 		 = sprintf( __( '%1$s requested a peer session with %2$s', 'bp-peer-session' ), $peer_session_creator_link, $recipient_link );
	}
	
	return apply_filters( 'bp_peer_session_get_peer_session_title', $title, $peer_session_creator_link, $recipient_link );
}

/**
 * Is this page part of the Peer Session component?
 *
 * Having a special function just for this purpose makes our code more readable elsewhere, and also
 * allows us to place filter 'bp_is_peer_session_component' for other components to interact with.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @uses bp_is_current_component()
 * @uses apply_filters() to allow this value to be filtered
 * @return bool True if it's the peer session component, false otherwise
 */
function bp_is_peer_session_component() {
	$is_peer_session_component = bp_is_current_component( 'peer-session' );

	return apply_filters( 'bp_is_peer_session_component', $is_peer_session_component );
}

/**
 * Echo the component's slug
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_slug() {
	echo bp_get_peer_session_slug();
}

/**
 * Return the component's slug
 *
 * Having a template function for this purpose is not absolutely necessary, but it helps to
 * avoid too-frequent direct calls to the $bp global.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @uses apply_filters() Filter 'bp_get_peer_session_slug' to change the output
 * @return str $peer_session_slug The slug from $bp->peerSession->slug, if it exists
 */
function bp_get_peer_session_slug() {
	global $bp;

	// Avoid PHP warnings, in case the value is not set for some reason
	$peer_session_slug = isset( $bp->peerSession->slug ) ? $bp->peerSession->slug : '';

	return apply_filters( 'bp_get_peer_session_slug', $peer_session_slug );
}

/**
 * Echo the component's root slug
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_root_slug() {
	echo bp_get_peer_session_root_slug();
}

/**
 * Return the component's root slug
 *
 * Having a template function for this purpose is not absolutely necessary, but it helps to
 * avoid too-frequent direct calls to the $bp global.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @uses apply_filters() Filter 'bp_get_peer_session_root_slug' to change the output
 * @return str $peer_session_root_slug The slug from $bp->peerSession->root_slug, if it exists
 */
function bp_get_peer_session_root_slug() {
	global $bp;

	// Avoid PHP warnings, in case the value is not set for some reason
	$peer_session_root_slug = isset( $bp->peerSession->root_slug ) ? $bp->peerSession->root_slug : '';

	return apply_filters( 'bp_get_peer_session_root_slug', $peer_session_root_slug );
}

/**
 * Echo the total of all peer sessions across the site
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_total_peer_session_count() {
	echo bp_peer_session_get_total_peer_session_count();
}

/**
 * Return the total of all peer sessions across the site
 *
 * The most straightforward way to get a post count is to run a WP_Query. In your own plugin
 * you might consider storing data like this with update_option(), incrementing each time
 * a new item is published.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @return int
 */
function bp_peer_session_get_total_peer_session_count() {
	$peer_session_list = new BP_Peer_Session_Peer_Session();
	$peer_session_list->get();

	return apply_filters( 'bp_peer_session_get_total_peer_session_count', $peer_session_list->query->found_posts, $peer_session_list );
}

/**
 * Echo the total of all peer sessions given to a particular user
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_total_peer_session_count_for_user( $user_id = false ) {
	echo bp_peer_session_get_total_peer_session_count_for_user( $user_id = false );
}

function bp_peer_session_total_peer_session_count_for_displayed_user() {
	echo bp_peer_session_get_total_peer_session_count_for_displayed_user();
}

/**
 * Return the total of all peer sessions given to a particular user
 *
 * The most straightforward way to get a post count is to run a WP_Query. In your own plugin
 * you might consider storing data like this with update_option(), incrementing each time
 * a new item is published.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @return int
 */
function bp_peer_session_get_total_peer_session_count_for_user( $user_id = false ) {
	// If no explicit user id is passed, fall back on the loggedin user
	if ( !$user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( !$user_id ) {
		return 0;
	}

	$peer_session_list = new BP_Peer_Session_Peer_Session();
	$peer_session_list->get( array( 'recipient_id' => $user_id ) );

	return apply_filters( 'bp_peer_session_get_total_peer_session_count', $peer_session_list->query->found_posts, $peer_session_list );
}


function bp_peer_session_get_total_peer_session_count_for_displayed_user() {
	$user_id = bp_displayed_user_id();

	$peer_session_recipient_list = new BP_Peer_Session_Peer_Session();
	$peer_session_recipient_list->get( array( 'recipient_id' => $user_id ) );
	$peer_session_author_list = new BP_Peer_Session_Peer_Session();
	$peer_session_author_list->get( array( 'peer_session_creator_id' => $user_id ) );

	$wp_query = new WP_Query();
	$wp_query->posts = array_merge( $peer_session_recipient_list->query->posts, $peer_session_author_list->query->posts );

	return apply_filters( 'bp_peer_session_get_total_peer_session_count_for_displayed_user', count($wp_query->posts), $peer_session_recipient_list, $peer_session_author_list );
}


function bp_peer_session_create_peer_session_button( $args = '' ) {
	echo bp_peer_session_get_create_peer_session_button( $args );
}

/**
 * Return button for sending peer sessions.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 *
 * @param array|string $args {
 *     All arguments are optional. See {@link BP_Button} for complete
 *     descriptions.
 *     @type string $id                Default: 'bp_peer_session_peer_session'.
 *     @type string $component         Default: 'member'.
 *     @type bool   $must_be_logged_in Default: true.
 *     @type bool   $block_self        Default: true.
 *     @type string $wrapper_id        Default: 'bp-peer-session-create-peer-session'.
 *     @type string $link_href         Default: the public message link for
 *                                     the current member in the loop.
 *     @type string $link_text         Default: 'Create Session!'.
 *     @type string $link_class        Default: 'bp-peer-session-button bp-peer-session-peer-session'.
 * }
 * @return string The button for creating a peer session.
 */
function bp_peer_session_get_create_peer_session_button( $args = '' ) {

	$r = bp_parse_args( $args, array(
		'id'                => 'bp_peer_session_create_peer_session',
		'component'         => 'peer-session',
		'must_be_logged_in' => true,
		'block_self'        => true,
		'wrapper_id'        => 'bp-peer-session-create-peer-session',
		'link_href'         => bp_peer_session_get_create_peer_session_link(),
		'link_text'         => __( 'Request Session', 'bp-peer-session' ),
		'link_class'        => 'bp-peer-session-button bp-peer-session-peer-session',
		'parent_element'	=> bp_get_theme_package_id() == 'nouveau' ? 'li' : 'div'
	) );

	return bp_get_button( apply_filters( 'bp_peer_session_get_create_peer_session_button', $r ) );
}

function bp_peer_session_get_create_peer_session_link() {
	return wp_nonce_url( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/create-peer-session/', 'bp_peer_session_create_peer_session');
}

function bp_peer_session_get_fade_offset() {
	$now = new DateTime('now');
	$hours = $now->format("H");
	$minutes = $now->format("i");
	$minutes_offset = $minutes > 29 ? 1: 0;
	return $hours * 2 + $minutes_offset;
}

function bp_peer_session_display_skill_list($user_id) {
	$args = array(
		'user_id' => $user_id,
        'field'   => 'Skills',
        );
	return bp_get_profile_field_data( $args );
}

function bp_peer_session_display_user_name($user_id) {
	$userdata = get_userdata($user_id);
	if (strlen($userdata->first_name) > 0) {
		return $userdata->first_name;
	} elseif (strlen($userdata->last_name) > 0) {
		return $userdata->last_name;
	} else {
		return $userdata->user_login;
	}
}

function bp_peer_session_display_skill_list_check($user_id, $selection_list) {
	$skillList = bp_peer_session_display_skill_list($user_id);
	if (!isset($skillList) || !is_array($skillList)) {
		echo "no skills selected by ".bp_peer_session_display_user_name($user_id);
		return;
	}
	foreach ($skillList as $skill) {
		echo "<li><label>".$skill."<input name=\"skill_list[]\" type=\"checkbox\" value=\"".$skill."\"";
		foreach ($selection_list as $selection) {
			if ($selection == $skill) {
				echo " checked=\"checked\"";
			}
		}
		echo "></label></li>";
	}
}

function bp_peer_session_cleanup_suggestions($selection_list) {
	$filteredList = [];
	$now = new DateTime('now');

	foreach ($selection_list as $selection) {
		if ($selection->value && $selection->value >= $now) {
			array_push($filteredList, $selection);
		}
	}
	return $filteredList;
}

function bp_peer_session_display_date_suggestion_list($date, $selection, $select_mode) {
	$now = new DateTime('now');

	$display_date = $date;
	for ($i = 0; $i <= 6; $i++) {
		$day_value = $display_date->format("d.m");
		echo "<div class=\"date-suggestion\"><div class=\"date-suggestion__head\">".$day_value."</div><div class=\"date-suggestion__content fadeable__container\"><div class=\"fadeable__content\">";
		bp_peer_session_display_date_suggestion_list_day($i * 23, $display_date, $now, $selection, $select_mode);
		//$display_date->add(new DateInterval('P1D'));
		echo "</div></div></div>";
	}
}

function bp_peer_session_display_date_suggestion_list_day($index, $display_date, $now, $selection, $select_mode) {
	$display_date->setTime(0,0,0);
	for ($i = 1; $i <= 48; $i++) {
		bp_peer_session_display_date_selection($i + $index, $display_date, $now, $selection, $select_mode);
		$display_date->add(new DateInterval('PT30M'));
	}

}

function bp_peer_session_display_date_selection($index, $date, $now, $selection_list, $select_mode) {
	$string_value = $date->format("c");
	$time_value = $date->format("H:i");
	$time_in_seconds = $date->format("U");
	if ($select_mode) {
		$disabled = true;
		foreach ($selection_list as $selection) {
			if ($selection->value && $selection->value == $date && $disabled) {
				$disabled = false;
			}
		}
		if (!$disabled) {
			echo "<div class=\"date-suggestion\"><input id=\"date".$time_in_seconds."\" class=\"date-suggestion__value\" name=\"data_accepted\" type=\"radio\" value=\"".$string_value."\" required";
			echo " ><label class=\"date-suggestion__label\" for=\"date".$time_in_seconds."\" >".$time_value."</label></div>";
		}
	} else {
		echo "<div class=\"date-suggestion";
		if ($date <= $now) {
			echo " date-suggestion--disabled";
		}
		echo "\"><input id=\"date".$time_in_seconds."\" class=\"date-suggestion__value\" name=\"date".$index."\" type=\"checkbox\" value=\"".$string_value."\"";
		if ($date <= $now) {
			echo " disabled=\"disabled\"";
		}
		foreach ($selection_list as $selection) {
			if ($selection->value && $selection->value == $date) {
				echo " checked=\"checked\"";
			}
		}
		echo " ><label class=\"date-suggestion__label\" for=\"date".$time_in_seconds."\" >".$time_value."</label></div>";
	}
	
}

function bp_peer_session_opposite_user($item) {
	return get_other_user_id($item, bp_loggedin_user_id());
}

function bp_peer_session_opposite_user_domain($item) {
	$other_user_id = bp_peer_session_opposite_user($item);
	return bp_core_get_userlink($other_user_id, false, true);
}


?>
<?php

/***
 * This file is used to add site administration menus to the WordPress backend.
 *
 * If you need to provide configuration options for your component that can only
 * be modified by a site administrator, this is the best place to do it.
 *
 * However, if your component has settings that need to be configured on a user
 * by user basis - it's best to hook into the front end "Settings" menu.
 */

/**
 * bp_peer_session_add_admin_menu()
 *
 * This function will add a WordPress wp-admin admin menu for your component under the
 * "BuddyPress" menu.
 */
function bp_peer_session_add_admin_menu() {
	global $bp;

	if ( !is_super_admin() )
		return false;

	add_submenu_page( 'bp-general-settings', __( 'Peer Session Admin', 'bp-peer-session' ), __( 'Peer Session Admin', 'bp-peer-session' ), 'manage_options', 'bp-peer-session-settings', 'bp_peer_session_admin' );
}
// The bp_core_admin_hook() function returns the correct hook (admin_menu or network_admin_menu),
// depending on how WordPress and BuddyPress are configured
add_action( bp_core_admin_hook(), 'bp_peer_session_add_admin_menu' );

function getSessionCountForState($state, $start_date, $end_date) {
	$session_requested_count_query = new WP_Query(array(
		'post_type' => 'peer-session', 
		'meta_query' => array(
			array(
			  'key' => 'bp_peer_session_state',
			  'value' => $state,
			)
		),
		'date_query' => [
			'after' => $start_date,
			'before' => $end_date,
		],
		'inclusive' => true,
		) 
	);
	return $session_requested_count_query->found_posts;
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT);
}

/**
 * bp_peer_session_admin()
 *
 * Checks for form submission, saves component settings and outputs admin screen HTML.
 */
function bp_peer_session_admin() {
	global $bp;

	/* If the form has been submitted and the admin referrer checks out, save the settings */
	if ( isset( $_POST['submit'] ) && check_admin_referer('peer-session-settings') ) {
		update_option( 'peer-session-setting-one', $_POST['peer-session-setting-one'] );
		update_option( 'peer-session-setting-two', $_POST['peer-session-setting-two'] );

		$updated = true;
	}

	$setting_one = get_option( 'peer-session-setting-one' );
	$setting_two = get_option( 'peer-session-setting-two' );
	if (!$setting_one) {
		$setting_one = '1 January 2019';
	}
	if (!$setting_two) {
		$setting_two = '31 December 2019';
	}
	// Declare and define two dates 
	$date1 = new DateTime($setting_one);  
	$date2 = new DateTime($setting_two);  
	// Formulate the Difference between two dates 
	$diff = $date2->diff($date1);  
	$month_count = $diff->y * 12 + $diff->m + ($diff->d == 0 ? 0 : ($diff->d / 31));

	$session_count_query = new WP_Query(array(
		'post_type' => 'peer-session',
		'date_query' => [
			'after' => $setting_one,
			'before' => $setting_two,
		],
		'meta_key' => 'bp_peer_session_date_started',
		'inclusive' => true,
		'posts_per_page'=>'-1',
		'orderby' => 'meta_value',
		'order' => 'ASC'
		) 
	);
	$session_count = $session_count_query->found_posts;

	$user_query = new WP_User_Query( array(
        'fields'    => 'ID',
    ) );
	$users = $user_query->get_results();
	$selected_user = [];
	$user_session_count_matrix = [];

    foreach( $users as $user ) {
        $user_object = get_userdata( $user );
		$cutoffdate = $date2->format('Y-m-d H:i:s');// '2013-07-01 00:00:01';

        if( $user_object->user_registered < $cutoffdate ) {
			array_push($selected_user, $user_object);
        }
    }

	$session_requested_count = getSessionCountForState('0', $setting_one, $setting_two);
	$session_canceled_count = getSessionCountForState('3', $setting_one, $setting_two);
	$session_finished_count = getSessionCountForState('2', $setting_one, $setting_two);
	$session_started_count = getSessionCountForState('1', $setting_one, $setting_two);
	$user_count = count($selected_user); //['avail_roles']['subscriber'];

?>
	<div class="wrap">
		<h2><?php _e( 'Peer Session Stats', 'bp-peer-session' ) ?></h2>
		<br />

		<?php
			$average_session_time = 0;
			$session_count_with_time = 0;
			$first_session_creation_time = null;
			$last_session_creation_time = null;
			$first_session_time = null;
			$last_session_time = null;
			// Check that we have query results.
			if ($session_count_query->have_posts()) {
				// Start looping over the query results.
				while ( $session_count_query->have_posts() ) {
					$session_count_query->the_post();
					$session_started = get_post_meta( get_the_ID(), 'bp_peer_session_date_started', true );
					$session_ended = get_post_meta( get_the_ID(), 'bp_peer_session_date_finished', true );
					// echo "<br><br>session: ".get_the_ID();
					// echo "<br>started: ";
					// print_r($session_started);
					// echo "<br>ended: ";
					// print_r($session_ended);
					if ($first_session_creation_time == null) {
						$first_session_creation_time = new DateTime(get_the_date());
					}
					// echo "<br>debug: ";
					// print_r($session_started);
					$last_session_creation_time = new DateTime(get_the_date());
					if ($session_started != null) {
						if ($first_session_time == null) {
							$first_session_time = $session_started;
						}
						// echo "<br>debug: ";
						// print_r($session_started);
						$last_session_time = $session_started;
						// echo "<br>debug last: ";
						// print_r($last_session_time);

						// add session in state 1 or 2 into matrix for recurring sessions
						$sender_id = get_the_author_meta( 'ID' );
						$receiver_id = get_post_meta( get_the_ID(), 'bp_peer_session_recipient_id', true );
						if (isset($user_session_count_matrix[$sender_id])) {
							if (isset($user_session_count_matrix[$sender_id][$receiver_id])) {
								$user_session_count_matrix[$sender_id][$receiver_id] = $user_session_count_matrix[$sender_id][$receiver_id] +1;
							} else {
								$user_session_count_matrix[$sender_id][$receiver_id] = 1;
							}
						} else {
							$user_session_count_matrix[$sender_id] = [];
							$user_session_count_matrix[$sender_id][$receiver_id] = 1;
						}
					}
					if ($session_started != null && $session_ended != null) {
						$session_durance = $session_ended->getTimestamp() - $session_started->getTimestamp();
	
						if ($session_durance > 0) {
							$session_count_with_time = $session_count_with_time + 1;
							$average_session_time = $average_session_time + $session_durance;
						}
					}
			
					// echo "DEBUG:";
					// echo " started: ".$session_started;
					// echo " ended: ".$session_ended;
				}

				// echo "DEBUG:";
				$average_session_time_value = ($session_count_with_time == 0 ? 0 : intdiv($average_session_time, $session_count_with_time));
				$average_session_time_interval = secondsToTime($average_session_time_value);
				$session_time_interval = secondsToTime($average_session_time);
				// echo " average time: ";
			}
			// Restore original post data.
			wp_reset_postdata();

			$month_count_f2l = 0;
			if ($first_session_creation_time != null && $last_session_creation_time != null) {
				$now = new DateTime('now');
				$diff_f2l = $now->diff($first_session_creation_time);
				$month_count_f2l= $diff_f2l->y * 12 + $diff_f2l->m + ($diff_f2l->d == 0 ? 0 : ($diff_f2l->d / 31));
			}

			// calculate recurring
			$recurring_session_list = [];
			foreach( $user_session_count_matrix as $recurring_user_id => $recurring_partner_list ) {
				if (isset($recurring_partner_list)) {
					foreach( $recurring_partner_list as $recurring_partner_id => $recurring_count ) {
						if (isset($recurring_count) && $recurring_count > 0) {
							if (isset($recurring_session_list[$recurring_count])) {
								$recurring_session_list[$recurring_count] = $recurring_session_list[$recurring_count] + 1;
							} else {
								$recurring_session_list[$recurring_count] = 1;
							}
						}
					}
				}
			}
		?>

		<?php if ( isset($updated) ) : ?><?php echo "<div id='message' class='updated fade'><p>" . __( 'Settings Updated.', 'bp-peer-session' ) . "</p></div>" ?><?php endif; ?>

		<form action="<?php echo site_url() . '/wp-admin/admin.php?page=bp-peer-session-settings' ?>" name="peer-session-settings-form" id="peer-session-settings-form" method="post">

			<h3>in time priode from <?php echo $setting_one; ?> till <?php echo $setting_two; ?></h3>
			<h4><?php printf("%d years, %d months, %d days", $diff->y, $diff->m, $diff->d); ?></h4>
			<ul>
				<li>Sessions: <?php echo $session_count; ?></li>
				<li>Session Requests: <?php echo $session_requested_count; ?></li>
				<li>Session Started: <?php echo $session_started_count; ?></li>
				<li>Session Canceled: <?php echo $session_canceled_count; ?></li>
				<li>Session Finshed: <?php echo $session_finished_count; ?></li>
				<li>Session Request/Startet: <?php if ($session_started_count + $session_finished_count > 0) { echo ($session_count / ($session_started_count + $session_finished_count)); } else { echo 0; } ?></li>
				<li>Users: <?php echo ($user_count == 0 ? 0 : $user_count); ?></li>
				<li>Sessions per User: <?php echo ($user_count == 0 ? 0 : $session_count / $user_count); ?></li>
				<li>Session Requests per User: <?php echo ($user_count == 0 ? 0 : $session_requested_count / $user_count); ?></li>
				<li>Session Started per User: <?php echo ($user_count == 0 ? 0 : $session_started_count / $user_count); ?></li>
				<li>Session Canceled per User: <?php echo ($user_count == 0 ? 0 : $session_canceled_count / $user_count); ?></li>
				<li>Session Finshed per User: <?php echo ($user_count == 0 ? 0 : $session_finished_count / $user_count); ?></li>
				<li>Sessions per User per Month (in Period): <?php echo ($user_count == 0 ? 0 : $session_count / $user_count / $month_count); ?></li>
				<li>Sessions per User per Month (from first to now): <?php if ($user_count) { if ($month_count_f2l > 0) {  echo ($user_count == 0 ? 0 : $session_count / $user_count / $month_count_f2l); } else { echo (1 / $user_count); } } else { echo 0; } ?></li>
				<li>Sessions held: <?php echo $session_count_with_time; ?></li>
				<li>Sessions overall time: <?php if (isset($session_time_interval )) { printf("%d years, %d months, %d days, %d hours, %d minutes, %d seconds", $session_time_interval->y, $session_time_interval->m, $session_time_interval->d, $session_time_interval->h, $session_time_interval->i, $session_time_interval->s); } ?></li>
				<li>Sessions average duration: <?php if (isset($average_session_time_interval )) { printf("%d years, %d months, %d days, %d hours, %d minutes, %d seconds", $average_session_time_interval->y, $average_session_time_interval->m, $average_session_time_interval->d, $average_session_time_interval->h, $average_session_time_interval->i, $average_session_time_interval->s); } ?></li>
				<li>First Sessions started: <?php if (isset($first_session_time )) { echo $first_session_time->format('d.m.Y H:i:s'); } ?></li>
				<li>Last Sessions started: <?php if (isset($last_session_time )) { echo $last_session_time->format('d.m.Y H:i:s'); } ?></li>
				<li>Recurring Sessions: (started and finished) 
					<?php
						foreach( $recurring_session_list as $recurring_count => $recurring_session_count ) {
							echo "<br>";
							if ($recurring_count == 1) {
								printf("%d times a unique session", $recurring_session_count);
							} else {
								printf("%d times a session with %d recurring", $recurring_session_count, $recurring_count - 1);
							}
						}
					?>
				</li>
			</ul>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="target_uri"><?php _e( 'Stats Start Date', 'bp-peer-session' ) ?></label></th>
					<td>
						<input name="peer-session-setting-one" type="text" id="peer-session-setting-one" value="<?php echo esc_attr( $setting_one ); ?>" size="60" />
					</td>
				</tr>
					<th scope="row"><label for="target_uri"><?php _e( 'Stats End Date', 'bp-peer-session' ) ?></label></th>
					<td>
						<input name="peer-session-setting-two" type="text" id="peer-session-setting-two" value="<?php echo esc_attr( $setting_two ); ?>" size="60" />
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="submit" value="<?php _e( 'Save Settings', 'bp-peer-session' ) ?>"/>
			</p>

			<?php
			/* This is very important, don't leave it out. */
			wp_nonce_field( 'peer-session-settings' );
			?>
		</form>
	</div>
<?php
}

/**
 * Test to see if the necessary database tables are installed, and if not, install them
 *
 * You will only need a function like this if you need to install database tables. It is not
 * recommended that you do so if you can help it; it clutters up users' databases, and it creates
 * problems when attempting to interact with the rest of WordPress. You are highly encouraged
 * to use WordPress custom post types instead.
 *
 * Doing this check in the admin, instead of at activation time, adds a bit of overhead. But the
 * WordPress core developers have expressed a dislike for activation functions, so we do it this
 * way instead. Don't worry - dbDelta() is quite smart about not overwriting anything.
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */
function bp_peer_session_install_tables() {
	global $wpdb;

	if ( !is_super_admin() )
		return;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	/**
	 * If you want to create new tables you'll need to install them on
	 * activation.
	 *
	 * You should try your best to use existing tables if you can. The
	 * activity stream and meta tables are very flexible.
	 *
	 * Write your table definition below, you can define multiple
	 * tables by adding SQL to the $sql array.
	 */
	$sql = array();
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}bp_peer_session (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		peer_session_creator_id bigint(20) NOT NULL,
		  		recipient_id bigint(20) NOT NULL,
		  		date_notified datetime NOT NULL,
			    KEY peer_session_creator_id (peer_session_creator_id),
			    KEY recipient_id (recipient_id)
		 	   ) {$charset_collate};";

	//require_once( ABSPATH . 'wp-admin/upgrade.php' );

	/**
	 * The dbDelta call is commented out so the peer session table is not installed.
	 * Once you define the SQL for your new table, uncomment this line to install
	 * the table. (Make sure you increment the BP_PEER_SESSION_DB_VERSION constant though).
	 */
	dbDelta($sql);

	update_site_option( 'bp-peer-session-db-version', BP_PEER_SESSION_DB_VERSION );
}
//add_action( 'admin_init', 'bp_peer_session_install_tables' );

add_action("add_meta_boxes", "bp_peer_session_add_meta_box");

function bp_peer_session_add_meta_box() {
    add_meta_box("peer-session-meta-box", "Peer Session Data", "bp_peer_session_meta_box_callback", "peer-session", "normal", null, null);
}
function bp_peer_session_meta_box_callback($object) {
		$item = bp_peer_session_get_specific(array('peer_session_id' => $object->ID));
    ?>
    <div>
        <ul>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Creator:</label><span class="display: inline-block;"><?php echo get_userdata($item->peer_session_creator_id)->user_login; ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Recipient:</label><span class="display: inline-block;"><?php echo get_userdata($item->recipient_id)->user_login; ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Created at:</label><span class="display: inline-block;"><?php if ($item->date) echo $item->date; ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">State:</label><span class="display: inline-block;"><?php echo bp_peer_session_get_peer_session_state($item); ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Agenda of Creator:</label><span class="display: inline-block;"><?php echo $item->agenda_creator; ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Agenda of Recipient:</label><span class="display: inline-block;"><?php echo $item->agenda_recipient; ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Suggested Dates:</label>
				<ul style="margin-left: 150px;">
				<?php foreach($item->date_suggestion_list as $suggestion) {
					if ($suggestion->value) {
						echo "<li>".$suggestion->value->format('d.m.Y H:i:s')."</li>";
					}
				} ?>
				</ul>
			</li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Selected Date:</label><span class="display: inline-block;"><?php if ($item->date_selected) echo $item->date_selected->format('d.m.Y H:i:s'); ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Started at:</label><span class="display: inline-block;"><?php if ($item->date_started) echo $item->date_started->format('d.m.Y H:i:s'); ?></span></li>
			<li><label style="margin-bottom: 5px; font-weight: bold; width: 150px; display: inline-block;">Finished at:</label><span class="display: inline-block;"><?php if ($item->date_finished) echo $item->date_finished->format('d.m.Y H:i:s'); ?></span></li>
		</ul>
    </div>
    <?php
}
?>
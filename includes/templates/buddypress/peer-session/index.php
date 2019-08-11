<?php

/**
 * BuddyPress - Peer Session Directory
 *
 * @package BuddyPress_Peer_Session_Component
 */

?>

<form action="" method="post" id="peer-session-directory-form" class="dir-form">

	<h3><?php _e( 'Session Directory', 'bp-peer-session' ); ?></h3>

	<?php do_action( 'bp_before_directory_peer_session_content' ); ?>

	<div class="item-list-tabs no-ajax" role="navigation">
		<ul>
			<li class="selected" id="groups-all">
				<?php 
					$count = bp_peer_session_get_total_peer_session_count_for_displayed_user();
					if ($count == 1) {
						printf( __( '<span>%s</span> Session', 'bp-peer-session' ), bp_peer_session_get_total_peer_session_count_for_displayed_user() );
					} else {
						printf( __( '<span>%s</span> Sessions', 'bp-peer-session' ), bp_peer_session_get_total_peer_session_count_for_displayed_user() );
					}
				?>
			</li>

			<?php do_action( 'bp_peer_session_directory_peer_session_filter' ); ?>

		</ul>
	</div><!-- .item-list-tabs -->

	<div id="peer-session-dir-list" class="peer-session dir-list">

		<?php bp_get_template_part( 'peer-session/peer-session-loop' ); ?>

	</div><!-- #peer-sessions-dir-list -->

	<?php do_action( 'bp_directory_peer_session_content' ); ?>

	<?php wp_nonce_field( 'directory_peer_session', '_wpnonce-peer-session-filter' ); ?>

	<?php do_action( 'bp_after_directory_peer_session_content' ); ?>

</form><!-- #peer-session-directory-form -->


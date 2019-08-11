<?php

/**
 *
 * @package BuddyPress_Peer_Session_Component
 * @since 1.6
 */

?>

<?php do_action( 'bp_before_peer_session_loop' ); ?>

<?php if ( bp_peer_session_has_items_for_displayed_user( bp_ajax_querystring( 'peer-session' ) ) ) : ?>
<?php // global $items_template; var_dump( $items_template ) ?>
	<!-- <div id="pag-top" class="pagination">

		<div class="pag-count" id="peer-session-dir-count-top">

			<?php bp_peer_session_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="peer-session-dir-pag-top">

			<?php bp_peer_session_item_pagination(); ?>

		</div>

	</div> -->

	<?php do_action( 'bp_before_directory_peer_session_list' ); ?>

	<ul id="peer-session-list" class="item-list" role="main">

	<?php while ( bp_peer_session_has_items_for_displayed_user() ) : $item = bp_peer_session_the_item(); ?>

		<li class="item-state--<?php bp_peer_session_peer_session_state($item) ?>">
			<div class="item-avatar">
				<?php bp_peer_session_peer_session_user_avatar( array('type' => 'thumb', 'width' => 50, 'height'=>50, 'user_id' => bp_peer_session_opposite_user($item)) ); ?>
			</div>

			<div class="item">
				<div class="item-title"><?php bp_peer_session_peer_session_title() ?></div>

				<?php do_action( 'bp_directory_peer_session_item' ); ?>

			</div>
			
			<?php if (user_is_session_participant()) : ?>
				<a class="item-action item-action--open" href="<?php echo bp_peer_session_opposite_user_domain($item). bp_get_peer_session_slug().'/';  bp_peer_session_item_id(); ?>">OPEN</a>
				<?php if ($item->date_selected && $item->state == 0) : ?>
					<a class="item-action item-action--start" href="<?php echo wp_nonce_url( bp_peer_session_opposite_user_domain($item) . bp_get_peer_session_slug() . '/start-peer-session/' . $item->id, 'bp_peer_session_start_peer_session') ?>">START</a>
				<?php endif; ?>
				<?php if ($item->state == 1) : ?>
					<a class="item-action item-action--finish" href="<?php echo wp_nonce_url( bp_peer_session_opposite_user_domain($item) . bp_get_peer_session_slug() . '/finish-peer-session/' . $item->id, 'bp_peer_session_finish_peer_session') ?>">FINISH</a>
				<?php endif; ?>
				<?php if ($item->state == 2 || $item->state == 3) : ?>
					<a class="item-action item-action--rerequest" href="<?php echo wp_nonce_url( bp_peer_session_opposite_user_domain($item) . bp_get_peer_session_slug() . '/create-peer-session/', 'bp_peer_session_create_peer_session') ?>">REQUEST</a>
				<?php endif; ?>
			<?php endif; ?>
			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_peer_session_list' ); ?>

	<!-- <div id="pag-bottom" class="pagination">

		<div class="pag-count" id="peer-session-dir-count-bottom">

			<?php bp_peer_session_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="peer-session-dir-pag-bottom">

			<?php bp_peer_session_item_pagination(); ?>

		</div>

	</div> -->

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Our <a href="/how-to-2">session guide</a> can help you get started', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_peer_session_loop' ); ?>

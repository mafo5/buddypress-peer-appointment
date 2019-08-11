<?php do_action( 'bp_before_member_session_' . bp_current_action() . '_content' ); ?>

<?php 
	// if the peer session is not yet started
	$item = bp_peer_session_get_specific( array( 'peer_session_id' => bp_current_action() ) );
	$state = $item->state;
	$creator_id = $item->peer_session_creator_id;
	// echo "<br>DEBUG item: ";
	// print_r($item);
	$is_editable = $state == 0;
	$is_canceled = $state == 3;
	$is_started = $state == 1;
	$is_finished = $state == 2;
	// echo "DEBUG state: ".$state;
	// some values
	$other_user_id = get_other_user_id($item, bp_loggedin_user_id());
	$user_id = bp_loggedin_user_id();

	$user_agenda = bp_peer_session_get_agend_for_user($item, $user_id);
	$other_agenda = bp_peer_session_get_agend_for_user($item, $other_user_id);
	$other_has_agenda = strlen($other_agenda) > 0;
	$user_name = bp_peer_session_display_user_name($user_id);
	$other_name = bp_peer_session_display_user_name($other_user_id);
	$user_up = "coming soon";
	$user_down = "coming soon";
	$other_up = "coming soon";
	$other_down = "coming soon";

	$skillList = [];
	$other_skillList = [];
	if ($creator_id == $user_id) {
		if (isset(get_object_vars($item)['skill_selection_creator'])) {
			$skillList = $item->skill_selection_creator;
		}
		if (isset(get_object_vars($item)['skill_selection_recipient'])) {
			$other_skillList = $item->skill_selection_recipient;
		}
	} else {
		if (isset(get_object_vars($item)['skill_selection_recipient'])) {
			$skillList = $item->skill_selection_recipient;
		}
		if (isset(get_object_vars($item)['skill_selection_creator'])) {
			$other_skillList = $item->skill_selection_creator;
		}
	}
	$other_has_skillList = is_array($other_skillList) && count($other_skillList) > 0;

	if (  $is_started ) : 

		$room = (int)$item->id;
		$password = "YOUR_ROOM_PASSWORD";
		$local_up = $user_up;
		$local_down = $user_down;
		$remote_up = $other_up;
		$remote_down = $other_down;
        $local_agenda_string = $user_agenda;
        $remote_agenda_string = $other_agenda;
	?>
		<script type="text/javascript">
			window.mentoClient = {
				username: '<?php echo $user_name; ?>',
				room: {
					name: '<?php echo $room; ?>',
					password: '<?php echo $password; ?>',
				},
				local: {
					up: `<?php echo $local_up; ?>`,
					down: `<?php echo $local_down; ?>`,
					agenda: [<?php echo stingify($local_agenda_string); ?>],
				},
				remote: {
					up: `<?php echo $remote_up; ?>`,
					down: `<?php echo $remote_down; ?>`,
					agenda: [<?php echo stingify($remote_agenda_string); ?>],
				},
			}
			console.log('Mento Data', window.mentoClient);
		</script>
		<div class="app--container">
			<div id="app"></div>
			<div class="session__bar">
				<a class="session__button session__button--finish" href="<?php echo wp_nonce_url( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/finish-peer-session/' .$item->id, 'bp_peer_session_finish_peer_session') ?>">
					<?php _e( 'Finish session', 'bp-peer-session' ) ?>
				</a>
			</div>
		</div>
	<?php else :
		$date_suggestion_list = bp_peer_session_cleanup_suggestions($item->date_suggestion_list);

	?>
	<section class="session__form
		<?php if ($item->date_selected) { echo "session--startable"; } ?>
		<?php if ($creator_id == $user_id) { echo "session--creator"; } else { echo "session--recipient"; } ?>
	">
		<h1>
			<?php _e( 'Session', 'bp-peer-session' ) ?>
			<?php if ($is_canceled) : ?>
				<small><?php _e( 'was canceled', 'bp-peer-session' ) ?></small>
			<?php endif; ?>
			<?php if ($is_finished) : ?>
				<small><?php _e( 'was finished', 'bp-peer-session' ) ?></small>
			<?php endif; ?>
			<?php if ($is_editable) : ?>
				<a class="session__button session__button--text session__button--abort" href="<?php echo wp_nonce_url( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/cancel-peer-session/', 'bp_peer_session_cancel_peer_session') ?>">
					<?php _e( 'Cancel Session', 'bp-peer-session' ) ?>
				</a>
			<?php endif; ?>
			<?php if ($is_canceled || $is_canceled) : ?>
				<a class="session__button session__button--rerequest" href="<?php echo wp_nonce_url( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/create-peer-session/', 'bp_peer_session_create_peer_session') ?>">
					<?php _e( 'Request Next Session', 'bp-peer-session' ) ?>
				</a>
			<?php endif; ?>
		</h1>
		<form action="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/' .bp_current_action() ); ?>" name="bp-peer-session-session-create-form" id="asession-create-form" class="bp-peer-session-session-create-form" method="post">
			<!-- TIME -->
			<formset>
				<div class="<?php if ($creator_id == $user_id) { echo "fadeable fadeable--fade fadeable--fade".bp_peer_session_get_fade_offset(); } ?>">
					<?php 
						$selection_start_date = new DateTime("now");
						if ($item->date_selected) {
							?>
							<?php
								if ($creator_id == $user_id) {
									echo "<p>".sprintf(__( 'You agreed to the date: %s', 'bp-peer-session'), $item->date_selected->format('d.m.Y H:i:s'));
								} else {
									echo "<p>".sprintf(__( '%s has agreed to the date: %s', 'bp-peer-session'), $other_name, $item->date_selected->format('d.m.Y H:i:s'));
								}
							?>
							<?php if (!$is_finished && !$is_canceled) : ?>
								<a class="session__button session__button--start" href="<?php echo wp_nonce_url( bp_displayed_user_domain() . bp_get_peer_session_slug() . '/start-peer-session/' . $item->id, 'bp_peer_session_start_peer_session') ?>">
									<?php _e( 'Start session', 'bp-peer-session' ) ?>
								</a>
							<?php endif; 
								echo "</p>";
							?>
							<?php if (isset($item->date_started) && $item->date_started) {
								echo "<p>".sprintf(__( 'You started the session: %s', 'bp-peer-session'), $item->date_started->format('d.m.Y H:i:s'))."</p>";
							} ?>
							<?php if (isset($item->date_finished) && $item->date_finished) {
								echo "<p>".sprintf(__( 'You finished the session: %s', 'bp-peer-session'), $item->date_finished->format('d.m.Y H:i:s'))."</p>";
							} ?>
							<?php
						} elseif ($is_canceled) {
							?>
							<div class="session-form__column">
								<?php _e( 'There has been no suggestions for a time and date been made.', 'bp-peer-session' ) ?>
							</div>
							<?php
						} elseif ($creator_id == $user_id) {
							?>
							<h2>
								<?php echo sprintf(__( 'Select up to 3 time slots to suggest %s.', 'bp-peer-session' ), $other_name); ?>
							</h2>
							<div class="session-form__column session-form__column--span2 <?php echo "fadeable fadeable--fade fadeable--fade".bp_peer_session_get_fade_offset() ?>">
								<ul>
									<?php bp_peer_session_display_date_suggestion_list($selection_start_date, $date_suggestion_list, false); ?>
								</ul>
								<div class="fadeable__action fadeable__action--show"><button class="session__button session__button--text" type="button" onclick="bsPeerSessionUnfade()">Reveal more options</a></div>
								<div class="fadeable__action fadeable__action--hide"><button class="session__button session__button--text" type="button" onclick="bsPeerSessionFade()">hide options</a></div>
							</div>
							<?php
						} else {
							?>
							<?php if (count($date_suggestion_list) > 0) { ?>
								<h2>
									<?php _e( 'Choose suggestions for a date and time', 'bp-peer-session' ) ?>
								</h2>
								<div class="session-form__column session-form__column--span2">
									<ul>
										<?php bp_peer_session_display_date_suggestion_list($selection_start_date, $date_suggestion_list, true); ?>
									</ul>
								</div>
							<?php } else { ?> 
								<div class="session-form__column session-form__column--span2">
									<?php echo sprintf(__( 'Please wait till %s has suggested a date and time', 'bp-peer-session'), $other_name) ?>
								</div>
							<?php } ?>
							<?php
						}
					?>
				</div>
			</formset>
			<!-- SKILL LIST -->
			<formset>	
				<h2>
					<?php echo sprintf(__( 'Select what skills you want to learn from %s.', 'bp-peer-session' ), $other_name); ?>
				</h2>	
				<?php if ($is_editable) : ?>
					<?php if (isset($skillList)) : ?>
						<ul class="skill_list">
							<?php bp_peer_session_display_skill_list_check($other_user_id, $skillList); ?>
						</ul>
					<?php else : ?>
						<p><?php echo sprintf(__( '%s hasn\'t selected any skills yet.', 'bp-peer-session' ), $other_name); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p>
						<?php $skillString = (is_array($skillList) && count($skillList) > 0) ? implode(" / ", $skillString) : "No skills have been selected."; ?>
						<?php echo $skillString; ?>
					</p>
				<?php endif; ?>
			</formset>
			<!-- AGENDA -->
			<formset>
				<h2>
					<?php echo sprintf(__( 'Let %s know what you want to discuss in your session.', 'bp-peer-session' ), $other_name); ?>
				</h2>
				<?php if ($is_editable) : ?>
					<textarea type="text" name="agenda_user" id="agenda_user"><?php echo $user_agenda; ?></textarea>
				<?php else : 
						echo "<p>".nl2br($user_agenda)."</p>";
					endif; ?>
			</formset>
			<!-- OTHERS VALUE -->
			<?php  if ($other_has_skillList || $other_has_agenda) : ?>
				<formset>
					<h2>
						<?php echo sprintf(__( '%s wants to discuss the following topics with you:', 'bp-peer-session' ), $other_name); ?>
					</h2>
					<?php
						if ($other_has_skillList) {
							$skillString = implode(" / ", $other_skillList);
							echo '<p>'.$skillString.'</p>';
						}
					?>
					<?php
						if ($other_has_agenda) {
							echo '<p>'.nl2br($other_agenda).'</p>';
						}
					?>
				</formset>
			<?php endif; ?>
			<!-- ACTIONS -->
			<div class="session__bar">
				<?php if ($is_editable) : ?>
					<button class="session__button session__button--submit" type="submit">
						<?php _e( 'SAVE YOUR SESSION', 'bp-peer-session' ) ?>
					</button>
				<?php endif; ?>
				<a class="session__button session__button--text session__button--back" href="<?php echo bp_loggedin_user_domain() . bp_get_peer_session_slug() ?>">
					<?php _e( 'Return to list', 'bp-peer-session' ) ?>
				</a>
				<?php 
					/* This is very important, don't leave it out. */
					wp_nonce_field( 'bp_peer_session_update_peer_session' );
				?>
			</div>
		</form>
	</sesction>
<?php endif; ?>

<?php do_action( 'bp_after_member_session_' . bp_current_action() . '_content' ); ?>
<?php

/**
 * In this file you should create and register widgets for your component.
 *
 * Widgets should be small, contained functionality that a site administrator can drop into
 * a widget enabled zone (column, sidebar etc)
 *
 * Good examples of suitable widget functionality would be short lists of updates or featured content.
 *
 * For example the friends and groups components have widgets to show the active, newest and most popular
 * of each.
 */

function bp_peer_session_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Peer_Session_Widget");') );
}
add_action( 'plugins_loaded', 'bp_peer_session_register_widgets' );

class BP_Peer_Session_Widget extends WP_Widget {

	function bp_peer_session_widget() {
		parent::WP_Widget( false, $name = __( 'Peer Session Widget', 'buddypress' ) );
	}

	function widget( $args, $instance ) {
		global $bp;

		extract( $args );

		echo $before_widget;
		echo $before_title .
		     $widget_name .
		     $after_title; ?>

	<?php

	/***
	 * This is where you add your HTML and render what you want your widget to display.
	 */

	?>

	<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* This is where you update options for this widget */

		$instance['max_items'] = strip_tags( $new_instance['max_items'] );
		$instance['per_page'] = strip_tags( $new_instance['per_page'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_items' => 200, 'per_page' => 25 ) );
		$per_page = strip_tags( $instance['per_page'] );
		$max_items = strip_tags( $instance['max_items'] );
		?>

		<p><label for="bp-peer-session-widget-per-page"><?php _e( 'Number of Items Per Page:', 'bp-peer-session' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'per_page' ); ?>" name="<?php echo $this->get_field_name( 'per_page' ); ?>" type="text" value="<?php echo attribute_escape( $per_page ); ?>" style="width: 30%" /></label></p>
		<p><label for="bp-peer-session-widget-max"><?php _e( 'Max items to show:', 'bp-peer-session' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo attribute_escape( $max_items ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

?>
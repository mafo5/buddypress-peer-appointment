<?php

/**
 * This function should include all classes and functions that access the database.
 * In most BuddyPress components the database access classes are treated like a model,
 * where each table has a class that can be used to create an object populated with a row
 * from the corresponding database table.
 *
 * By doing this you can easily save, update and delete records using the class, you're also
 * abstracting database access.
 *
 * This function uses WP_Query and wp_insert_post() to fetch and store data, using WordPress custom
 * post types. This method for data storage is highly recommended, as it assures that your data
 * will be maximally compatible with WordPress's security and performance optimization features, in
 * addition to making your plugin easier to extend for other developers. The suggested
 * implementation here (where the WP_Query object is set as the query property on the
 * BP_Peer_Session_Peer_Session object in get()) is one suggested implementation.
 */

class BP_Peer_Session_Peer_Session {
    var $id;
    var $peer_session_creator_id;
    var $recipient_id;
    var $date;
    var $query;

    var $date_suggestion_list;
    var $date_selected;
    var $agenda_creator;
    var $agenda_recipient;
    var $state;
    // 0 - created
    // 1 - started
    // 2 - ended
    // 3 - canceled
    var $date_started;
    var $date_finished;

    /**
     * bp_peer_session_tablename()
     *
     * This is the constructor, it is auto run when the class is instantiated.
     * It will either create a new empty object if no ID is set, or fill the object
     * with a row from the table if an ID is provided.
     */
    function __construct( $args = array() ) {
        // Set some defaults
        $defaults = array(
            'id'		=> 0,
            'peer_session_creator_id' => 0,
            'recipient_id'  => 0,
            'date' 		=> date( 'Y-m-d H:i:s' ),
            'agenda_creator' => '',
            'agenda_recipient' => '',
            'date_suggestion_list' => array(),
            'skill_selection_creator' => array(),
            'skill_selection_recipient' => array(),
            'state' => 0,
        );

        // Parse the defaults with the arguments passed
        $r = wp_parse_args( $args, $defaults );
        extract( $r );

        if ( $id ) {
            $this->id = $id;
            $this->populate( $this->id );
        } else {
            foreach( $r as $key => $value ) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * populate()
     *
     * This method will populate the object with a row from the database, based on the
     * ID passed to the constructor.
     */
    function populate() {
        global $wpdb, $bp, $creds;

        if ( $row = get_post($this->id) ) {
            $this->peer_session_creator_id    = $row->post_author;
            $this->date 	        = $row->post_date;
            if ($meta = get_post_meta($this->id)) {
                // echo "<br>DEBUG DB meta: ";
                // print_r($meta);
                if (is_array($row->bp_peer_session_recipient_id)) {
                    $this->recipient_id     = $row->bp_peer_session_recipient_id[0];
                } else {
                    $this->recipient_id = $row->bp_peer_session_recipient_id;
                }
                if (is_array($row->bp_peer_session_agenda_creator) && count($row->bp_peer_session_agenda_creator)) {
                    $this->agenda_creator   = $row->bp_peer_session_agenda_creator[0];
                } else {
                    $this->agenda_creator   = $row->bp_peer_session_agenda_creator;
                }
                if (is_array($row->bp_peer_session_agenda_recipient) && count($row->bp_peer_session_agenda_recipient)) {
                    $this->agenda_recipient   = $row->bp_peer_session_agenda_recipient[0];
                } else {
                    $this->agenda_recipient   = $row->bp_peer_session_agenda_recipient;
                }

                $this->date_suggestion_list = array();
                $this->date_selected = $row->bp_peer_session_date_selected;
                $this->date_started = $row->bp_peer_session_date_started;
                $this->date_finished = $row->bp_peer_session_date_finished;
                $this->skill_selection_creator = $row->bp_peer_session_skill_selection_creator;
                $this->skill_selection_recipient = $row->bp_peer_session_skill_selection_recipient;
                // echo "<br>DEBUG loaded selected date ";
                // print_r($this->date_selected);
                $date_suggestion_list = $row->bp_peer_session_date_suggestion_value_list;
                if ($date_suggestion_list) {
                    $date_suggestion_creator_list = $row->bp_peer_session_date_suggestion_creator_list;
                    $date_suggestion_creator_list_count = 0;
                    if ($date_suggestion_creator_list) {
                        $date_suggestion_creator_list_count = count($date_suggestion_creator_list);
                    }
                    foreach ($date_suggestion_list as $index=>$date_suggestion) {
                        if ($index < $date_suggestion_creator_list_count) {
                            $creator_id = $date_suggestion_creator_list_count[$index];
                        }
                        if (!$creator_id) {
                            $creator_id = $this->peer_session_creator_id;
                        }
                        if ($this->date_selected) {
                            $accepted = $date_suggestion == $this->date_selected;
                        } else {
                            $accepted = false;
                        }
                        array_push($this->date_suggestion_list, new BP_Peer_Session_Peer_Session_Date(array(
                            'creator_id' => $creator_id,
                            'value' => $date_suggestion,
                            'accepted' => $accepted
                        )));

                        // echo "<br>DEBUG loaded date is selected ".$accepted;
                    }
                }
                // echo "<br>DEBUG loaded date suggestion: ";
                // print_r($this->date_suggestion_list);
                $this->state = $row->bp_peer_session_state;
            }
        }
    }

    /**
     * save()
     *
     * This method will save an object to the database. It will dynamically switch between
     * INSERT and UPDATE depending on whether or not the object already exists in the database.
     */

    function save() {
        global $wpdb, $bp;

        /***
         * In this save() method, you should add pre-save filters to all the values you are
         * saving to the database. This helps with two things -
         *
         * 1. Blanket filtering of values by plugins (for example if a plugin wanted to
         * force a specific value for all saves)
         *
         * 2. Security - attaching a wp_filter_kses() call to all filters, so you are not
         * saving potentially dangerous values to the database.
         *
         * It's very important that for number 2 above, you add a call like this for each
         * filter to 'bp-peer-session-filters.php'
         *
         *   add_filter( 'peer_session_data_fieldname1_before_save', 'wp_filter_kses' );
         */

        $this->peer_session_creator_id = apply_filters( 'bp_peer_session_data_peer_session_creator_id_before_save', $this->peer_session_creator_id, $this->id );
        $this->recipient_id     	   = apply_filters( 'bp_peer_session_data_recipient_id_before_save', $this->recipient_id, $this->id );
        $this->date	            	   = apply_filters( 'bp_peer_session_data_date_before_save', $this->date, $this->id );
        $this->agenda_creator   	   = apply_filters( 'bp_peer_session_data_agenda_creator_before_save', $this->agenda_creator, $this->id );
        $this->agenda_recipient 	   = apply_filters( 'bp_peer_session_data_agenda_recipient_before_save', $this->agenda_recipient, $this->id );
        $this->date_suggestion_list    = apply_filters( 'bp_peer_session_data_date_suggestion_list_before_save', $this->date_suggestion_list, $this->id );
        $this->state 				   = apply_filters( 'bp_peer_session_data_state_before_save', $this->state, $this->id);
        $this->date_selected	       = apply_filters( 'bp_peer_session_data_date_selected_before_save', $this->date_selected, $this->id );
        $this->date_started	           = apply_filters( 'bp_peer_session_data_date_started_before_save', $this->date_started, $this->id );
        $this->date_finished	       = apply_filters( 'bp_peer_session_data_date_finished_before_save', $this->date_finished, $this->id );
        $this->skill_selection_creator = apply_filters( 'bp_peer_session_data_skill_selection_creator', $this->skill_selection_creator, $this->id );
        $this->skill_selection_recipient = apply_filters( 'bp_peer_session_data_skill_selection_recipient', $this->skill_selection_recipient, $this->id );
        
        // Call a before save action here
        do_action( 'bp_peer_session_data_before_save', $this );

        $date_suggestion_value_list = array();
        $date_suggestion_creator_list = array();

        foreach($this->date_suggestion_list as $date_suggestion) {
            array_push($date_suggestion_value_list, $date_suggestion->value);
            array_push($date_suggestion_creator_list, $date_suggestion->creator_id);
        }
        // echo "<br>DEBUG date_suggestion_value_list: ";
        // print_r($date_suggestion_value_list);
        // echo "<br>DEBUG date_suggestion_creator_list: ";
        // print_r($date_suggestion_creator_list);

        if ( $this->id ) {
            // Set up the arguments for wp_insert_post()
            $wp_update_post_args = array(
                'ID'		=> $this->id,
                'post_author'	=> $this->peer_session_creator_id,
                'post_title'	=> sprintf( __( '%1$s sessions %2$s', 'bp-peer-session' ), bp_core_get_user_displayname( $this->peer_session_creator_id ), bp_core_get_user_displayname( $this->recipient_id ) )
            );

            // Save the post
            $result = wp_update_post( $wp_update_post_args );

            // We'll store the receiver's ID as postmeta
            if ( $result ) {
                update_post_meta( $result, 'bp_peer_session_recipient_id', $this->recipient_id );
                update_post_meta( $result, 'bp_peer_session_agenda_creator', $this->agenda_creator );
                update_post_meta( $result, 'bp_peer_session_agenda_recipient', $this->agenda_recipient );
                update_post_meta( $result, 'bp_peer_session_date_suggestion_value_list', $date_suggestion_value_list );
                update_post_meta( $result, 'bp_peer_session_date_suggestion_creator_list', $date_suggestion_creator_list );
                update_post_meta( $result, 'bp_peer_session_date_selected', $this->date_selected );
                update_post_meta( $result, 'bp_peer_session_state', $this->state );
                update_post_meta( $result, 'bp_peer_session_date_started', $this->date_started );
                update_post_meta( $result, 'bp_peer_session_date_finished', $this->date_finished );
                update_post_meta( $result, 'bp_peer_session_skill_selection_creator', $this->skill_selection_creator );
                update_post_meta( $result, 'bp_peer_session_skill_selection_recipient', $this->skill_selection_recipient );
            }
        } else {
            // Set up the arguments for wp_insert_post()
            $wp_insert_post_args = array(
                'post_status'	=> 'publish',
                'post_type'	=> 'peer-session',
                'post_author'	=> $this->peer_session_creator_id,
                'post_title'	=> sprintf( __( '%1$s sessions %2$s', 'bp-peer-session' ), bp_core_get_user_displayname( $this->peer_session_creator_id ), bp_core_get_user_displayname( $this->recipient_id ) )
            );

            // Save the post
            $result = wp_insert_post( $wp_insert_post_args );

            // We'll store the receiver's ID as postmeta
            if ( $result ) {
                update_post_meta( $result, 'bp_peer_session_recipient_id', $this->recipient_id );
                update_post_meta( $result, 'bp_peer_session_agenda_creator', $this->agenda_creator );
                update_post_meta( $result, 'bp_peer_session_agenda_recipient', $this->agenda_recipient );
                update_post_meta( $result, 'bp_peer_session_date_suggestion_value_list', $date_suggestion_value_list );
                update_post_meta( $result, 'bp_peer_session_date_suggestion_creator_list', $date_suggestion_creator_list );
                update_post_meta( $result, 'bp_peer_session_date_selected', $this->date_selected );
                update_post_meta( $result, 'bp_peer_session_state', $this->state );
                update_post_meta( $result, 'bp_peer_session_date_started', $this->date_started );
                update_post_meta( $result, 'bp_peer_session_date_finished', $this->date_finished );
                update_post_meta( $result, 'bp_peer_session_skill_selection_creator', $this->skill_selection_creator );
                update_post_meta( $result, 'bp_peer_session_skill_selection_recipient', $this->skill_selection_recipient );
            }
        }

        /* Add an after save action here */
        do_action( 'bp_peer_session_data_after_save', $this );

        return $result;
    }

    /**
     * Fire the WP_Query
     *
     * @package BuddyPress_Peer_Session_Component
     * @since 1.6
     */
    function get( $args = array() ) {
        // Only run the query once
        if ( empty( $this->query ) ) {
            $defaults = array(
                'peer_session_creator_id'	=> 0,
                'recipient_id'	=> 0,
                'per_page'	=> 10,
                'paged'		=> 1,
                'id' => false
            );

            $r = wp_parse_args( $args, $defaults );
            extract( $r );

            $query_args = array(
                'post_status'	 => 'publish',
                'post_type'	 => 'peer-session',
                'posts_per_page' => $per_page,
                'paged'		 => $paged,
                'meta_query'	 => array()
            );

            // Some optional query args
            // Note that some values are cast as arrays. This allows you to query for multiple
            // authors/recipients at a time
            if ( $peer_session_creator_id ) {
                $query_args['author'] = $peer_session_creator_id;
            }

            // We can filter by postmeta by adding a meta_query argument. Note that
            if ( $recipient_id ) {
                $query_args['meta_query'][] = array(
                    'key'	  => 'bp_peer_session_recipient_id',
                    'value'	  => (array)$recipient_id,
                    'compare' => 'IN' // Allows $recipient_id to be an array
                );
            }

            if ($id) {
                $query_args = array(
                    'p' => $id,
                    'post_type'	 => 'peer-session',
                );
            }

            // Run the query, and store as an object property, so we can access from
            // other methods
            $this->query = new WP_Query( $query_args );
            // echo "Last SQL-Query: {$this->query->request}";

            // Let's also set up some pagination
            $this->pag_links = paginate_links( array(
                'base' => add_query_arg( 'items_page', '%#%' ),
                'format' => '',
                'total' => ceil( (int) $this->query->found_posts / (int) $this->query->query_vars['posts_per_page'] ),
                'current' => (int) $paged,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
                'mid_size' => 1
            ) );
        }
    }

    /**
     * Part of our bp_peer_session_has_peer_sessions() loop
     *
     * @package BuddyPress_Peer_Session_Component
     * @since 1.6
     */
    function have_posts() {
        return $this->query->have_posts();
    }

    /**
     * Part of our bp_peer_session_has_peer_sessions() loop
     *
     * @package BuddyPress_Peer_Session_Component
     * @since 1.6
     */
    function the_post() {
        $this->query->the_post();
        // echo "<br>DEBUG the_post id: ".get_the_ID();
        return new BP_Peer_Session_Peer_Session(array('id' => get_the_ID()));
    }

    /**
     * delete()
     *
     * This method will delete the corresponding row for an object from the database.
     */
    function delete() {
        return wp_trash_post( $this->id );
    }

    /* Static Functions */

    /**
     * Static functions can be used to bulk delete items in a table, or do something that
     * doesn't necessarily warrant the instantiation of the class.
     *
     * Look at bp-core-classes.php for peer sessions of mass delete.
     */

    function delete_all() {

    }

    function delete_by_user_id() {

    }
}

class BP_Peer_Session_Peer_Session_Date {
    var $value;
    var $accepted;
    var $creator_id;

    function __construct( $args = array() ) {
        // Set some defaults
        $defaults = array(
            'value'		=> null,
            'accepted' => false,
            'creator_id'  => null
        );

        // Parse the defaults with the arguments passed
        $r = wp_parse_args( $args, $defaults );
        foreach($r as $_key => $_value)
        {
            $this->$_key = $_value;
        }
    }
}

?>
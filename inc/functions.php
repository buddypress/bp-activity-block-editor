<?php
/**
 * BuddyPress Activity Block Editor functions.
 *
 * @package bp-activity-block-editor\inc
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The BP Activity Block Editor needs BuddyPress Activity Block functions.
add_filter( 'bp_is_activity_blocks_active', '__return_true' );

/**
 * Adds some specific data to the Activity REST API response.
 *
 * @since 1.0.0
 *
 * @param WP_REST_Response     $response The response object.
 * @param WP_REST_Request      $request  The request object.
 * @param BP_Activity_Activity $activity BP_Activity_Activity object.
 * @return WP_REST_Response $response The response object.
 */
function bp_activity_wall_rest_activity_prepare_value( $response, $request, $activity ) {
	$referer      = $request->get_header( 'referer' );
	$referer_path = '';
	if ( $referer ) {
		$referer_path = wp_parse_url( $referer, PHP_URL_PATH );
	}

	$is_bp_activity_admin = $request->get_param( '_is_bp_activity_admin' ) || '/wp-admin/admin.php' === $referer_path;
	$data                 = $response->get_data();

	if ( $data ) {
		$activity_id       = (int) $data['id'];
		$data['timediff']  = bp_core_time_since( $activity->date_recorded );
		$data['timestamp'] = strtotime( $activity->date_recorded );

		if ( ! isset( $activity->children ) ) {
			$top_level_parent_id   = 'activity_comment' === $activity->type ? $activity->item_id : 0;
			$activity_comments     = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right, 'ham_only', $top_level_parent_id );
			$data['comment_count'] = count( $activity_comments );
		}

		if ( (int) bp_loggedin_user_id() === (int) $data['user_id'] && bp_activity_has_blocks( $activity->content ) ) {
			$data['edit_link'] = bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-edit-activity',
						'aid'  => $activity_id,
					),
					'admin.php'
				)
			);
		}

		if ( ! empty( $is_bp_activity_admin ) ) {
			$data['link'] = bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-view-activity',
						'aid'  => $activity_id,
					),
					'admin.php'
				)
			);
		}

		// Activity comment capability.
		$can_comment = false;
		if ( 'activity_comment' === $data['type'] ) {
			$can_comment_arg = $activity;
			$filter          = 'bp_activity_can_comment_reply';
			$can_comment     = bp_activity_can_comment_reply( $activity );
		} else {
			$can_comment_arg = $data['type'];
			$filter          = 'bp_activity_can_comment';
			$can_comment     = bp_activity_type_supports( $data['type'], 'comment-reply' );
		}

		/*
		 * BuddyPress `bp_groups_filter_activity_can_comment()` needs the $activities_template to be set.
		 * We probably need to make this unnecessary in BuddyPress, in the meantime let's simulate this global.
		 */
		if ( isset( $GLOBALS['activities_template'] ) ) {
			$reset_activities_template = $GLOBALS['activities_template'];
		} else {
			$reset_activities_template = null;
		}

		$GLOBALS['activities_template']           = new stdClass();
		$GLOBALS['activities_template']->activity = $activity;

		/** This filter is documented in wp-content/plugins/buddypress/bp-activity/bp-activity-template.php */
		$data['can_comment'] = apply_filters( $filter, $can_comment, $can_comment_arg );

		// Activity favorite capability.
		$data['can_favorite'] = bp_activity_can_favorite();

		// Activity delete capability.
		$data['can_delete'] = bp_activity_user_can_delete( $activity );

		// Update the response.
		$response->set_data( $data );

		// Reset the global.
		$GLOBALS['activities_template'] = $reset_activities_template;
	}

	return $response;
}
add_filter( 'bp_rest_activity_prepare_value', 'bp_activity_wall_rest_activity_prepare_value', 10, 3 );

/**
 * Fetches emojis according to given args.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Associative array of arguments list to query for emojis.
 *
 *     @type integer $page      The current page.
 *     @type integer $per_page  Emoji items per page.
 *     @type string  $search    The search terms to restrict the list of emojis with matching names.
 * }
 * @return array An associative array containing the emojis and the total amount of available emojis.
 */
function bp_activity_get_emojis( $args = array() ) {
	$results = array(
		'emojis'       => array(),
		'total_emojis' => 0,
	);

	if ( empty( $GLOBALS['wpdb'] ) ) {
		return $results;
	}

	$wpdb = $GLOBALS['wpdb'];
	$r    = bp_parse_args(
		$args,
		array(
			'page'     => 1,
			'per_page' => 10,
			'search'   => '',
		)
	);

	// Select conditions.
	$table     = bp_core_get_table_prefix() . 'bp_emojis';
	$sql       = "SELECT * FROM {$table} e";
	$sql_total = "SELECT count( DISTINCT e.id ) FROM {$table} e";

	// Where conditions.
	$where_conditions = array();

	if ( $r['search'] ) {
		$search_like                = '%' . bp_esc_like( $r['search'] ) . '%';
		$where_conditions['search'] = $wpdb->prepare( 'e.name LIKE %s', $search_like );
	}

	$where_sql = '';
	if ( $where_conditions ) {
		// Join the where conditions together.
		$where_sql = ' WHERE ' . join( ' AND ', $where_conditions );
	}

	// Sanitize page and per_page parameters.
	$page     = absint( $r['page'] );
	$per_page = absint( $r['per_page'] );

	$pag_sql = '';
	if ( $page && $per_page ) {
		$pag_sql = $wpdb->prepare( " LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page );
	}

	$emojis = $wpdb->get_results( $sql . $where_sql . $pag_sql );
	if ( $emojis ) {
		foreach ( $emojis as $emoji ) {
			$emoji->id = (int) $emoji->id;
		}
	}

	return array(
		'emojis'       => $emojis,
		'total_emojis' => (int) $wpdb->get_var( $sql_total . $where_sql ),
	);
}

/**
 * Register the BP Activity Block Editor Emojis REST controller.
 *
 * @since 1.0.0
 */
function bp_activity_emojis_set_rest_controller() {
	require_once plugin_dir_path( dirname( __FILE__ ) ) . '/bp-activity/classes/class-bp-activity-block-editor-emojis-rest-controller.php';

	$controller = new BP_Activity_Block_Editor_Emojis_REST_Controller();
	$controller->register_routes();
}
add_action( 'bp_rest_api_init', 'bp_activity_emojis_set_rest_controller', 10 );

/**
 * Registers the `buddypress` theme feature.
 *
 * @todo This function should be implemented into BuddyPress core.
 *
 * @since 1.0.0
 */
function bp_register_buddypress_theme_feature() {
	register_theme_feature(
		'buddypress',
		array(
			'type'        => 'array',
			'variadic'    => true,
			'description' => __( 'Whether the Theme supports BuddyPress and possibly BP Modern features', 'bp-activity-block-editor' ),
		)
	);
}
add_action( 'bp_init', 'bp_register_buddypress_theme_feature' );

/**
 * Checks whether a theme is supporting a BP Component's feature.
 *
 * @todo This filter should be implemented into BuddyPress core.
 *
 * @since 1.0.0
 *
 * @param bool   $supports Whether the active theme supports the given feature. Default false.
 * @param array  $args     Array of arguments for the feature.
 * @param string $feature  The theme feature.
 * @return boolean True if the feature is supported. False otherwise.
 */
function bp_current_theme_supports( $supports = false, $args = array(), $feature = null ) {

	if ( true === $supports && $args ) {
		$component         = key( $args[0] );
		$component_feature = $args[0][ $component ];

		if ( ! is_array( $feature ) ) {
			$supports = false;
		} else {
			$theme_feature = $feature[0];

			// Check the theme is supporting the component's feature.
			$supports = isset( $theme_feature[ $component ] ) && in_array( $component_feature, $theme_feature[ $component ], true );
		}
	}

	return $supports;
}
add_filter( 'current_theme_supports-buddypress', 'bp_current_theme_supports', 10, 3 );

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
	if ( '/buddypress/v1/activity' === $request->get_route() ) {
		$is_bp_activity_admin = $request->get_param( '_is_bp_activity_admin' );
		$data                 = $response->get_data();

		if ( $data ) {
			$activity_id       = (int) $data['id'];
			$data['timediff']  = bp_core_time_since( $activity->date_recorded );
			$data['timestamp'] = strtotime( $activity->date_recorded );

			if ( (int) bp_loggedin_user_id() === (int) $data['user_id'] ) {
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

			/** This filter is documented in wp-content/plugins/buddypress/bp-activity/bp-activity-template.php */
			$data['can_comment'] = apply_filters( $filter, $can_comment, $can_comment_arg );

			// Activity favorite capability.
			$data['can_favorite'] = bp_activity_can_favorite();

			// Activity delete capability.
			$data['can_delete'] = bp_activity_user_can_delete( $activity );

			// Update the response.
			$response->set_data( $data );
		}
	}

	return $response;
}
add_filter( 'bp_rest_activity_prepare_value', 'bp_activity_wall_rest_activity_prepare_value', 10, 3 );

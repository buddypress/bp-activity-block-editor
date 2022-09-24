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
		$data = $response->get_data();

		if ( $data ) {
			$data['author_link']   = esc_url( bp_core_get_user_domain( $activity->user_id ) );
			$data['timediff']      = bp_core_time_since( $activity->date_recorded );
			$data['timestamp']     = strtotime( $activity->date_recorded );

			// Update the response.
			$response->set_data( $data );
		}
	}

	return $response;
}
add_filter( 'bp_rest_activity_prepare_value', 'bp_activity_wall_rest_activity_prepare_value', 10, 3 );

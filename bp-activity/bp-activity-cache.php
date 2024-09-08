<?php
/**
 * BuddyPress Activity filters.
 *
 * @package bp-activity
 * @since 1.0.0
 */

namespace BP\Activity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clear a cached activity item when that item is updated.
 *
 * @since 2.0.0
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_clear_cache_for_activity( $activity ) {
	wp_cache_delete( $activity->id, 'bp_activity' );
	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	// Clear the reactions cache for the parent activity ID.
	if ( ! empty( $activity->item_id ) ) {
		if ( 'activity_comment' === $activity->type ) {
			wp_cache_delete( $activity->item_id, 'bp_activity_comments' );
		} elseif ( in_array( $activity->type, bp_get_activity_types_for_role( 'reaction' ), true ) ) {
			wp_cache_delete( $activity->item_id, 'bp_activity_reactions' );
		}
	}
}
add_action( 'bp_activity_after_save', __NAMESPACE__ . '\bp_activity_clear_cache_for_activity' );

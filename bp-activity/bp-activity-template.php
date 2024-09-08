<?php
/**
 * BuddyPress Activity template functions.
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
 * Are Activity likes supported by this site?
 *
 * @since 14.0.0
 * @todo Build the function!
 *
 * @return boolean True if Activity likes are supported. False otherwise.
 */
function bp_activity_supports_likes() {
	/*
	 * This is where we'll need to check for:
	 * current_theme_supports( 'buddypress', ['activity', ['likes'] ] );
	 */
	return true;
}

/**
 * Return whether an activity is liked by a user.
 *
 * @since 14.0.0
 *
 * @param integer $user_id     The user ID. Optional.
 *                             Defaults to the current user ID.
 * @param integer $activity_id The Activity object. Optional.
 *                             Defaults to 0.
 * @return boolean True if the user liked the activity. False otherwise.
 */
function bp_activity_is_liked( $user_id = 0, $activity_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( $activity_id ) {
		$user_like = bp_activity_get(
			array(
				'type'             => 'activity_like',
				'filter'           => array(
					'user_id'    => $user_id,
					'primary_id' => $activity_id,
				),
				'display_comments' => 'stream',
			)
		);
		$user_like = wp_list_pluck( $user_like['activities'], 'user_id' );

	} elseif ( isset( $GLOBALS['activities_template']->activity->reactions ) ) {
		$user_like = wp_filter_object_list( $GLOBALS['activities_template']->activity->reactions, array( 'type' => 'activity_like', 'user_id' => bp_loggedin_user_id() ), 'AND', 'user_id' );
	}

	return ! empty( $user_like ) && 1 === count( $user_like );
}

/**
 * Informs about the number of times an activity was liked.
 *
 * @since 14.0.0
 *
 * @param integer $activity_id The Activity ID. Optional.
 *                             Defaults to 0.
 * @return integer The number of times an activity was liked.
 */
function bp_activity_get_like_count( $activity_id = 0 ) {
	$count = 0;

	if ( $activity_id ) {
		$likes = bp_activity_get(
			array(
				'type'             => 'activity_like',
				'filter'           => array(
					'primary_id' => $activity_id,
				),
				'display_comments' => 'stream',
				'count_total_only' => true,
			)
		);

		$count = (int) $likes['total'];

	} elseif ( isset( $GLOBALS['activities_template']->activity->reactions ) ) {
		$likes = wp_list_filter( $GLOBALS['activities_template']->activity->reactions, array( 'type' => 'activity_like' ) );

		if ( $likes ) {
			$count = count( $likes );
		}
	}

	return $count;
}

/**
 * Gets the link to like an activity.
 *
 * @since 14.0.0
 *
 * @param string               $type     Whether to add or remove the like.
 * @param BP_Activity_Activity $activity An activity object. Optional.
 * @return string The link to like an activity.
 */
function bp_get_activity_like_link( $type = 'add', $activity = null ) {
	$url         = '';
	$activity_id = 0;

	if ( $activity instanceof BP_Activity_Activity ) {
		$activity_id = (int) $activity->id;

	} elseif ( isset( $GLOBALS['activities_template']->activity->id ) ) {
		$activity_id = (int) $GLOBALS['activities_template']->activity->id;
	}

	$slug = 'like';
	if ( 'remove' === $type ) {
		$slug = 'dislike';
	}

	if ( $activity_id ) {
		$url = bp_rewrites_get_url(
			array(
				'component_id'                 => 'activity',
				'single_item_action'           => $slug,
				'single_item_action_variables' => array( $activity_id ),
			)
		);

		$url = wp_nonce_url( $url, 'bp_activity_like' );
	}

	/**
	 * Filters the link to like an activity.
	 *
	 * @since 14.0.0
	 *
	 * @param string               $url      Constructed link for liking the activity.
	 * @param string               $type     Whether to `add` or `remove` the like.
	 * @param string               $slug     The computed slug according to the type (`like` or `dislike`).
	 * @param BP_Activity_Activity $activity The Activity object to add a like to.
	 */
	return apply_filters( 'bp_get_activity_like_link', $url, $type, $slug, $activity );
}

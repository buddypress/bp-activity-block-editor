<?php
/**
 * BuddyPress Gutenberg Functions.
 *
 * @package bp-gutenberg\inc
 * @since 1.0.0
 */

namespace BP\Gutenberg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether an activity or its content string has blocks.
 *
 * @since 1.0.0
 * @see parse_blocks()
 *
 * @param string|int|BP_Activity_Activity|null $activity Activity content, Activity ID, or Activity object.
 * @return bool Whether the post has blocks.
 */
function activity_has_blocks( $activity = null ) {
	if ( is_null( $activity ) ) {
		return false;
	}

	if ( ! is_string( $activity ) ) {
		if ( is_int( $activity ) ) {
			$bp_activity = new BP_Activity_Activity( $activity );
		} else {
			$bp_activity = $activity;
		}

		if ( $bp_activity instanceof BP_Activity_Activity ) {
			$activity = $bp_activity->content;
		}
	}

	return has_blocks( $activity );
}

/**
 * If `bp_activity_do_blocks()` needs to remove `wpautop()` from the `bp_get_activity_content_body` filter, this re-adds it afterwards,
 * for subsequent `bp_get_activity_content_body` usage.
 *
 * @since 1.0.0
 *
 * @param string $content The activity content running through this filter.
 * @return string The unmodified activity content.
 */
function activity_restore_wpautop_hook( $content ) {
	$current_priority = has_filter( 'bp_get_activity_content_body', __NAMESPACE__ . '\activity_restore_wpautop_hook' );

	add_filter( 'bp_get_activity_content_body', 'wpautop', $current_priority - 1 );
	remove_filter( 'bp_get_activity_content_body', __NAMESPACE__ . '\activity_restore_wpautop_hook', $current_priority );

	return $content;
}

/**
 * Parses dynamic blocks out of activity content and re-renders them.
 *
 * @since 1.0.0
 *
 * @param string $content Activity content.
 * @return string Updated activity content.
 */
function activity_do_blocks( $content ) {
	$blocks = parse_blocks( $content );
	$output = '';

	foreach ( $blocks as $block ) {
		$output .= render_block( $block );
	}

	// If there are blocks in this content, we shouldn't run wpautop() on it later.
	$priority = has_filter( 'bp_get_activity_content_body', 'wpautop' );
	if ( false !== $priority && doing_filter( 'bp_get_activity_content_body' ) && activity_has_blocks( $content ) ) {
		remove_filter( 'bp_get_activity_content_body', 'wpautop', $priority );
		add_filter( 'bp_get_activity_content_body', __NAMESPACE__ . '\activity_restore_wpautop_hook', $priority + 1 );
	}

	return $output;
}
add_filter( 'bp_get_activity_content_body', __NAMESPACE__ . '\activity_do_blocks', 9 );

/**
 * Make sure the WP Emoji output is containing all needed attributes.
 *
 * @since 1.0.0
 *
 * @param string $activity_content The activity content.
 * @return string The sanitized activity content.
 */
function activity_blocks_kses( $activity_content ) {
	$emojis = array();

	preg_match_all( '%(<!--.*?(-->|$))|(<[^>]*(>|$)|>)%', $activity_content, $tags );
	$tags = reset( $tags );

	if ( $tags ) {
		foreach ( $tags as $tag ) {
			preg_match( '%^<\s*(/\s*)?([a-zA-Z0-9-]+)([^>]*)>?$%', wp_kses_stripslashes( $tag ), $matches );

			if ( isset( $matches[2] ) && 'img' === $matches[2] ) {
				$attributes = wp_kses_hair( $tag, wp_allowed_protocols() );

				if ( isset( $attributes['class']['value'] ) && in_array( $attributes['class']['value'], array( 'wp-smiley', 'emoji' ), true ) ) {
					if ( 'wp-smiley' === $attributes['class']['value'] ) {
						$bp_should_allow = wp_kses(
							$tag,
							array(
								'img' => array(
									'src'   => true,
									'alt'   => true,
									'class' => true,
									'style' => true,
								),
							)
						);
					} else {
						$bp_should_allow = wp_kses(
							$tag,
							array(
								'img' => array(
									'draggable' => true,
									'role'      => true,
									'class'     => true,
									'alt'       => true,
									'src'       => true,
								),
							)
						);
					}

					$emojis[] = array(
						'bp_should_allow' => $bp_should_allow,
						'bp_sanitized'    => bp_activity_filter_kses( $tag ),
					);
				}
			}
		}
	}

	$bp_sanitized = bp_activity_filter_kses( $activity_content );

	if ( ! $emojis ) {
		return $bp_sanitized;
	}

	foreach ( $emojis as $emoji ) {
		$bp_sanitized = str_replace( $emoji['bp_sanitized'], $emoji['bp_should_allow'], $bp_sanitized );
	}

	return $bp_sanitized;
}

// Disable too restrictive filters.
remove_filter( 'bp_get_activity_content_body', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_get_activity_parent_content', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_get_activity_latest_update', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_get_activity_feed_item_description', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_activity_content_before_save', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_activity_action_before_save', 'bp_activity_filter_kses', 1 );
remove_filter( 'bp_activity_latest_update_content', 'bp_activity_filter_kses', 1 );

// Replace the disabled filters by one suitable for activity blocks
add_filter( 'bp_get_activity_content_body', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_get_activity_parent_content', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_get_activity_latest_update', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_get_activity_latest_update_excerpt', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_get_activity_feed_item_description', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_activity_content_before_save', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_activity_action_before_save', __NAMESPACE__ . '\activity_blocks_kses', 1 );
add_filter( 'bp_activity_latest_update_content', __NAMESPACE__ . '\activity_blocks_kses', 1 );

/**
 * Allow usage of the paragraph tag and the link’s target attribute into Activities content.
 *
 * @since 1.0.0
 *
 * @param array $tags The activity allowed tags.
 * @return array The activity allowed tags.
 */
function activity_blocks_allowed_tags( $tags = array() ) {
	if ( isset( $tags['a'] ) && ! isset( $tags['a']['target'] ) ) {
		$tags['a']['target'] = true;
	}

	return array_merge( $tags, array( 'p' => true ) );
}
add_filter( 'bp_activity_allowed_tags', __NAMESPACE__ . '\activity_blocks_allowed_tags' );

/**
 * Moderate the posted activity item, if it contains moderate keywords.
 *
 * @since 1.0.0
 *
 * @param BP_Activity_Activity $activity The activity object to check.
 */
function activity_check_moderation_keys( $activity ) {

	// Only check specific types of activity updates.
	if ( ! in_array( $activity->type, bp_activity_get_moderated_activity_types(), true ) ) {
		return;
	}

	// Remove WP Emojis from the content to moderate.
	$content_to_moderate = preg_replace( '/src=\"(https|http):\/\/s.w.org\/images\/core\/emoji.*?\"/', '', $activity->content );

	// Send back the error so activity update fails.
	$moderate = bp_core_check_for_moderation( $activity->user_id, '', $content_to_moderate, 'wp_error' );
	if ( is_wp_error( $moderate ) ) {
		$activity->errors = $moderate;

		// Backpat.
		$activity->component = false;
	}
}

// Activity links moderation shouldn't take WP Emoji links in account.
remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2, 1 );

// Use a more suitable moderation function for activity blocks.
add_action( 'bp_activity_before_save', __NAMESPACE__ . '\activity_check_moderation_keys', 2, 1 );

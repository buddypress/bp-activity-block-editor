<?php
/**
 * BuddyPress Activity Block Editor functions.
 *
 * @package bp-activity-block-editor\bp-activity
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the Block Categories for the BP Activity context.
 *
 * @since 1.0.0
 *
 * @return array The list of block categories for the activity context.
 */
function bp_activity_get_block_categories() {
	$block_categories = get_default_block_categories();
	$embed_category   = array();
	foreach ( $block_categories as $position => $category ) {
		if ( isset( $category['slug'] ) && in_array( $category['slug'], array( 'embed', 'reusable', 'theme' ), true ) ) {
			unset( $block_categories[ $position ] );

			if ( 'embed' === $category['slug'] ) {
				$embed_category = array( $category );
			}
		}
	}

	/**
	 * Filter here to include your custom block categories for the activity context.
	 *
	 * @since 1.0.0
	 *
	 * @param $block_categories array The list of block categories for the activity context.
	 */
	$bp_activity_block_categories = apply_filters( 'bp_activity_block_categories', array_values( $block_categories ) );

	return array_merge( $bp_activity_block_categories, $embed_category );
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
function bp_activity_has_blocks( $activity = null ) {
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
function bp_activity_restore_wpautop_hook( $content ) {
	$current_priority = has_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook' );

	add_filter( 'bp_get_activity_content_body', 'wpautop', $current_priority - 1 );
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook', $current_priority );

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
function bp_activity_do_blocks( $content ) {
	$blocks = parse_blocks( $content );
	$output = '';

	foreach ( $blocks as $block ) {
		$output .= render_block( $block );
	}

	// If there are blocks in this content, we shouldn't run wpautop() on it later.
	$priority = has_filter( 'bp_get_activity_content_body', 'wpautop' );
	if ( false !== $priority && doing_filter( 'bp_get_activity_content_body' ) && bp_activity_has_blocks( $content ) ) {
		remove_filter( 'bp_get_activity_content_body', 'wpautop', $priority );
		add_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook', $priority + 1 );
	}

	return $output;
}
add_filter( 'bp_get_activity_content_body', 'bp_activity_do_blocks', 9 );

/**
 * Make sure only Emoji chars are saved into the DB.
 *
 * @since 1.0.0
 *
 * @param string $activity_content The activity content.
 * @return string The sanitized activity content.
 */
function bp_activity_blocks_preserve_emoji_chars( $activity_content ) {
	preg_match_all( '/\<img[^>]*alt=\"([^"]*)\".?\>/', $activity_content, $matches );

	if ( isset( $matches[0][0] ) && isset( $matches[1][0] ) ) {
		foreach ( $matches[0] as $key => $match ) {
			if ( false !== strpos( $matches[0][ $key ], 's.w.org/images/core/emoji' ) && isset( $matches[1][ $key ] ) ) {
				$activity_content = str_replace( $matches[0][ $key ], $matches[1][ $key ], $activity_content );
			}
		}
	}

	return $activity_content;
}
add_filter( 'bp_activity_content_before_save', 'bp_activity_blocks_preserve_emoji_chars', 2 );

/**
 * Allow usage of the paragraph tag and the link’s target attribute into Activities content.
 *
 * @since 1.0.0
 *
 * @param array $tags The activity allowed tags.
 * @return array The activity allowed tags.
 */
function bp_activity_blocks_allowed_tags( $tags = array() ) {
	if ( isset( $tags['a'] ) && ! isset( $tags['a']['target'] ) ) {
		$tags['a']['target'] = true;
	}

	return array_merge( $tags, array( 'p' => true ) );
}
add_filter( 'bp_activity_allowed_tags', 'bp_activity_blocks_allowed_tags' );

/**
 * Enqueues script and styles for Activity blocks.
 *
 * NB: BP Activity blocks need to use include `activity` into their `buddypress_contexts` property.
 *
 * @since 1.0.0
 */
function bp_activity_enqueue_block_editor_assets() {
	$block_registry = WP_Block_Type_Registry::get_instance();

	foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
		if ( empty( $block_type->buddypress_contexts ) || ! in_array( 'activity', $block_type->buddypress_contexts, true ) ) {
			continue;
		}

		// Front-end styles.
		if ( ! empty( $block_type->style ) ) {
			wp_enqueue_style( $block_type->style );
		}

		// Front-end script.
		if ( ! empty( $block_type->script ) ) {
			wp_enqueue_script( $block_type->script );
		}

		// Editor styles.
		if ( ! empty( $block_type->editor_style ) ) {
			wp_enqueue_style( $block_type->editor_style );
		}

		// Editor script.
		if ( ! empty( $block_type->editor_script ) ) {
			wp_enqueue_script( $block_type->editor_script );
		}
	}
}
add_action( 'bp_activity_enqueue_block_editor_assets', 'bp_activity_enqueue_block_editor_assets', 1 );

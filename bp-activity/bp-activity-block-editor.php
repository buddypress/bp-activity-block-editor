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
		if ( isset( $category['slug'] ) && 'embed' === $category['slug'] ) {
			unset( $block_categories[ $position ] );
			$embed_category = array( $category );
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

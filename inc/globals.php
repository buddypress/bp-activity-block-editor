<?php
/**
 * BuddyPress Activity Block Editor Globals.
 *
 * @package bp-activity-block-editor\inc
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin globals.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_setup_globals() {
	$bp_plugin = bp_activity_block_editor();

	// Version.
	$bp_plugin->version = '1.0.0';

	// Path.
	$bp_plugin->dir = plugin_dir_path( dirname( __FILE__ ) );

	// URL.
	$bp_plugin->url = plugins_url( '', dirname( __FILE__ ) );

	// Edited activity.
	$bp_plugin->edit_activity = null;
}
add_action( 'bp_loaded', 'bp_activity_block_editor_setup_globals', 1 );

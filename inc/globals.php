<?php
/**
 * BuddyPress Activity Block Editor Globals.
 *
 * @package bp-activity-block-editor\inc
 * @since 1.0.0
 */

namespace BP\Activity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin globals.
 *
 * @since 1.0.0
 */
function setup_globals() {
	$main = bp_activity();

	// Version.
	$main->version = '1.1.0';

	// Path.
	$main->dir = \plugin_dir_path( dirname( __FILE__ ) );

	// URL.
	$main->url = \plugins_url( '', dirname( __FILE__ ) );

	// Edited activity.
	$main->edit_activity = null;

	// Viewed activity.
	$main->view_activity = null;
}
add_action( 'bp_loaded', __NAMESPACE__ . '\setup_globals', 1 );

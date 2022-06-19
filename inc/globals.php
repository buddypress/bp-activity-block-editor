<?php
/**
 * BuddyPress Gutenberg Globals.
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
 * Register plugin globals.
 *
 * @since 1.0.0
 */
function globals() {
	$bp_plugin = plugin();

	$bp_plugin->version = '1.0.0';

	// Path.
	$bp_plugin->dir = plugin_dir_path( dirname( __FILE__ ) );

	// URL.
	$bp_plugin->url = plugins_url( '', dirname( __FILE__ ) );
}
add_action( 'bp_loaded', __NAMESPACE__ . '\globals', 1 );

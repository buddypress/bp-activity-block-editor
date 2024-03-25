<?php
/**
 * BuddyPress Activity Block Editor installer.
 *
 * @package bp-activity-block-editor\inc
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs the emojis database and populates it.
 *
 * @since 1.0.0
 */
function bp_activity_install_emojis_db() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$prefix          = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$prefix}bp_emojis (
		`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`emoji_id` varchar(100) NOT NULL default '',
		`name` varchar(255) NOT NULL default '',
		`char` varchar(255) NOT NULL default '',
		`src` text NOT NULL default '',
		`category` varchar(100) NOT NULL default '',
		KEY `emoji_id` (`emoji_id`)
	) {$charset_collate};";

	$inserts = require_once plugin_dir_path( __FILE__ ) . '/inserts.php';
	$sql     = array_merge( $sql, $inserts );

	dbDelta( $sql );
}

<?php
/**
 * BuddyPress Activity Block Editor development plugin.
 *
 * @package   bp-activity-block-editor
 * @author    The BuddyPress Community
 * @license   GPL-2.0+
 * @link      https://buddypress.org
 *
 * @buddypress-plugin
 * Plugin Name:       BP Activity Block Editor
 * Plugin URI:        https://github.com/buddypress/bp-activity-block-editor
 * Description:       Brings the power of the WordPress Blocks API into BuddyPress activities.
 * Version:           1.0.0
 * Author:            The BuddyPress Community
 * Author URI:        https://buddypress.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * Text Domain:       bp-activity-block-editor
 * GitHub Plugin URI: https://github.com/buddypress/bp-activity-block-editor
 * Requires at least: 6.5
 * Requires PHP:      5.6
 * Requires Plugins:  buddypress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class BP_Activity_Block_Editor {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Used to store dynamic properties.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Load Globals & Functions.
		$path = plugin_dir_path( __FILE__ );

		require $path . 'inc/globals.php';
		require $path . 'inc/functions.php';
		require $path . 'bp-activity/bp-activity-block-editor.php';

		if ( is_admin() ) {
			require $path . 'bp-activity/bp-activity-admin.php';
		}
	}

	/**
	 * Magic method for checking the existence of a plugin global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to check the set status for.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Magic method for getting a plugin global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to return the value for.
	 * @return mixed
	 */
	public function __get( $key ) {
		$retval = null;
		if ( isset( $this->data[ $key ] ) ) {
			$retval = $this->data[ $key ];
		}

		return $retval;
	}

	/**
	 * Magic method for setting a plugin global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	/**
	 * Magic method for unsetting a plugin global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to unset a value for.
	 */
	public function __unset( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
		}
	}

	/**
	 * Checks whether BuddyPress is active.
	 *
	 * @since 1.0.0
	 */
	public static function is_buddypress_active() {
		$bp_plugin_basename   = 'buddypress/bp-loader.php';
		$is_buddypress_active = false;
		$sitewide_plugins     = (array) get_site_option( 'active_sitewide_plugins', array() );

		if ( $sitewide_plugins ) {
			$is_buddypress_active = isset( $sitewide_plugins[ $bp_plugin_basename ] );
		}

		if ( ! $is_buddypress_active ) {
			$plugins              = (array) get_option( 'active_plugins', array() );
			$is_buddypress_active = in_array( $bp_plugin_basename, $plugins, true );
		}

		return $is_buddypress_active;
	}

	/**
	 * Installs the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		if ( ! self::is_buddypress_active() ) {
			return;
		}

		// Install only once.
		if ( ! bp_get_option( '_bp_activity_block_editor_version', '' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'inc/install.php';
			bp_activity_install_emojis_db();
			bp_update_option( '_bp_activity_block_editor_version', '1.0.0' );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		// This plugin is only usable with BuddyPress.
		if ( ! self::is_buddypress_active() ) {
			return false;
		}

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Start plugin.
 *
 * @since 1.0.0
 *
 * @return BP_Activity_Block_Editor The main instance of the plugin.
 */
function bp_activity_block_editor() {
	return BP_Activity_Block_Editor::start();
}
add_action( 'bp_loaded', 'bp_activity_block_editor', -1 );

register_activation_hook( __FILE__, array( 'BP_Activity_Block_Editor', 'install' ) );

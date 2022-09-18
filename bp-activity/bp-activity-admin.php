<?php
/**
 * BuddyPress Activity Admin functions.
 *
 * @package bp-activity-block-editor\bp-activity
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Activity editor assets.
 *
 * @since 1.0.0
 */
function bp_activity_admin_load_screen() {
	$script_assets = require_once plugin_dir_path( __FILE__ ) . 'block-editor/index.asset.php';

	wp_register_script(
		'bp-activity-block-editor',
		plugins_url( 'block-editor/index.js', __FILE__ ),
		array_merge( $script_assets['dependencies'], array( 'bp-block-components' ) ),
		$script_assets['version'],
		true
	);

	wp_register_style(
		'bp-activity-block-editor',
		plugins_url( 'block-editor/style-index.css', __FILE__ ),
		array(
			'wp-format-library',
			'wp-components',
			'wp-editor',
			'wp-reset-editor-styles',
			'wp-edit-post',
		),
		$script_assets['version']
	);

	if ( isset( $_GET['aid'] ) && isset( $_GET['action'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		$activity_id = absint( wp_unslash( $_GET['aid'] ) );
		$activities  = bp_activity_get(
			array(
				'in'          => $activity_id,
				'show_hidden' => true,
			)
		);

		$activity = null;
		if ( isset( $activities['activities'] ) ) {
			$activity = reset( $activities['activities'] );
		}

		// Starts easy before dealing with more complex capabilities.
		if ( bp_loggedin_user_id() !== (int) $activity->user_id ) {
			wp_die( __( 'You are not the author of this activity. Only Activity authors can edit their activities.', 'bp-activity-block-editor' ) );
		}

		if ( isset( $activity->user_id ) ) {
			bp_activity_block_editor()->edit_activity = $activity;
		}
	}

	add_action( 'bp_admin_enqueue_scripts', 'bp_activity_block_editor_enqueue_assets' );
	add_filter( 'admin_body_class', 'bp_activity_admin_body_class' );

	/**
	 * This hook is used to register blocks for the BuddyPress Activity Block Editor.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_activity_enqueue_block_editor_assets' );
}

/**
 * Sets the Activity block editor settings.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_get_settings() {
	/**
	 * This filter is used to allow blocks to add their settings to the BuddyPress Activity Block Editor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $custom_editor_settings Block custom settings.
	 */
	$custom_editor_settings = apply_filters( 'bp_activity_block_editor_settings', array() );

	$settings = array(
		'iso'    => array(
			'footer' => true,
			'blocks' => array(
				'allowBlocks' => array( 'core/paragraph', 'core/embed', 'bp/image-attachment' ),
			),
			'toolbar' => array(
				'inspector'         => true,
				'documentInspector' => __( 'Activity', 'bp-activity-block-editor' ),
			),
			'moreMenu' => array(
				'topToolbar' => true,
				'fullscreen' => true,
			),
		),
		'editor' => array_merge(
			array(
				'disableCustomColors'                  => get_theme_support( 'disable-custom-colors' ),
				'disableCustomFontSizes'               => get_theme_support( 'disable-custom-font-sizes' ),
				'isRTL'                                => is_rtl(),
				'codeEditingEnabled'                   => false,
				'__experimentalBlockPatterns'          => array(),
				'__experimentalBlockPatternCategories' => array(),
				'activeComponents'                     => array_values( bp_core_get_active_components() ),
				'bodyPlaceholder'                      => sprintf( __( 'What’s new %s?', 'bp-activity-block-editor' ), bp_core_get_user_displayname( get_current_user_id() ) ),
				'canLockBlocks'                        => false,
			),
			$custom_editor_settings
		),
	);

	list( $color_palette, ) = (array) get_theme_support( 'editor-color-palette' );
	list( $font_sizes, )    = (array) get_theme_support( 'editor-font-sizes' );

	if ( false !== $color_palette ) {
		$settings['editor']['colors'] = $color_palette;
	}

	if ( false !== $font_sizes ) {
		$settings['editor']['fontSizes'] = $font_sizes;
	}

	return $settings;
}

/**
 * Enqueues the Activity Editor assets.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_enqueue_assets() {
	$settings                           = bp_activity_block_editor_get_settings();
	$settings['editor']['activityEdit'] = bp_activity_block_editor()->edit_activity;

	$paths = array(
		'/buddypress/v1/members/me?context=edit',
	);

	if ( bp_is_active( 'groups' ) ) {
		$paths[] = '/buddypress/v1/groups/me?context=edit';
	}

	/**
	 * Filter here to add your preloaded paths.
	 *
	 * @since TBD
	 *
	 * @param array $paths the list of preloaded paths.
	 */
	$preload_paths = apply_filters(
		'bp_activity_blocks_editor_preload_paths',
		$paths
	);

	// Preloads BP Activity's data.
	$preload_data = array_reduce(
		$preload_paths,
		'rest_preload_api_request',
		array()
	);

	// Create the Fetch API Preloading middleware.
	wp_add_inline_script(
		'wp-api-fetch',
		sprintf( 'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );', wp_json_encode( $preload_data ) ),
		'after'
	);

	wp_enqueue_script( 'bp-activity-block-editor' );

	if ( defined( 'IFRAME_REQUEST' ) && isset( $_GET['url'] ) && $_GET['url'] ) { // phpcs:ignore
		wp_add_inline_style(
			'common',
			'html { overflow: hidden }
			#adminmenumain { display: none; }
			#wpcontent  { margin: 0; }
			@media only screen and (max-width: 960px) {
				.auto-fold #wpcontent { margin-left: 0 !important; }
			}'
		);
	}

	/**
	 * Add a setting to inform whether the Activity Block Editor
	 * is used form the Activity Admin screen or not.
	 */
	$settings['editor']['isActivityAdminScreen'] = ! defined( 'IFRAME_REQUEST' ) && is_admin();

	wp_add_inline_script(
		'bp-activity-block-editor',
		'window.bpActivityBlockEditor = ' . wp_json_encode( $settings ) . ';'
	);

	// Preload server-registered block schemas.
	wp_add_inline_script(
		'wp-blocks',
		'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
	);

	// Editor default styles.
	wp_enqueue_style( 'bp-activity-block-editor' );
}

/**
 * Adds specific needed admin body classes.
 *
 * @since 1.0.0
 *
 * @param string $admin_body_class The Admin screen body classes.
 * @return string The Admin screen body classes.
 */
function bp_activity_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;
	$screen_class = preg_replace( '/[^a-z0-9_-]+/i', '-', $hook_suffix );

	if ( 'toplevel_page_bp-activities' !== $screen_class ) {
		$admin_body_class .= ' toplevel_page_bp-activities';
	}

	if ( defined( 'IFRAME_REQUEST' ) ) {
		$admin_body_class .= ' iframe';
	}

	$edit_activity = bp_activity_block_editor()->edit_activity;

	if ( ! is_null( $edit_activity ) ) {
		$admin_body_class .= ' edit-activity';
	}

	return $admin_body_class;
}

/**
 * Activity Editor Screen.
 *
 *  @since 1.0.0
 */
function bp_activity_admin_screen() {
	?>
	<div id="bp-activity-block-editor"></div>
	<div id="bp-activity-block-editor-notices"></div>
	<?php
}

/**
 * Adds an submenu to the Activity Admin menu.
 *
 * @since 1.0.0
 */
function bp_activity_admin_replace_menu() {
	remove_action( bp_core_admin_hook(), 'bp_activity_add_admin_menu' );

	$screen = add_menu_page(
		_x( 'Activity', 'Admin Dashboard SWA page title', 'bp-activity-block-editor' ),
		_x( 'Activity', 'Admin Dashboard SWA menu', 'bp-activity-block-editor' ),
		'exist',
		'bp-activities',
		'bp_activity_admin_screen',
		'dashicons-buddicons-activity'
	);

	add_action( 'load-' . $screen, 'bp_activity_admin_load_screen' );
}
add_action( bp_core_admin_hook(), 'bp_activity_admin_replace_menu', 9 );

function bp_activity_admin_filter_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-activities' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_activity_admin_filter_menu_order' );

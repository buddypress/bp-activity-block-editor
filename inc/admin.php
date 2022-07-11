<?php
/**
 * BuddyPress Admin Functions.
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
 * Register Activity editor assets.
 *
 * @since 1.0.0
 */
function activity_block_editor_load_screen() {
	$script_assets = require_once plugin_dir_path( dirname( __FILE__ ) ) . 'build/index.asset.php';

	wp_register_script(
		'bp-gutenberg-activity-editor',
		plugins_url( 'build/index.js', dirname( __FILE__ ) ),
		$script_assets['dependencies'],
		$script_assets['version'],
		true
	);

	wp_register_style(
		'bp-gutenberg-activity-editor',
		plugins_url( 'build/style-index.css', dirname( __FILE__ ) ),
		array(
			'wp-format-library',
			'wp-components',
			'wp-editor',
			'wp-reset-editor-styles',
			'wp-edit-post',
		),
		$script_assets['version']
	);

	add_action( 'bp_admin_enqueue_scripts', __NAMESPACE__ . '\activity_block_editor_enqueue_assets' );
	add_filter( 'admin_body_class', __NAMESPACE__ . '\activity_block_editor_admin_body_class' );
}

/**
 * Sets the Activity block editor settings.
 *
 * @since 1.0.0
 */
function activity_block_editor_get_settings() {
	$settings = array(
		'iso'    => array(
			'footer' => true,
			'blocks' => array(
				'allowBlocks' => array( 'core/paragraph', 'core/embed' ),
			),
			'toolbar' => array(
				'inspector'         => true,
				'documentInspector' => __( 'Activity', 'bp-gutenberg' ),
			),
			'moreMenu' => array(
				'topToolbar' => true,
				'fullscreen' => true,
			),
		),
		'editor' => array(
			'disableCustomColors'                  => get_theme_support( 'disable-custom-colors' ),
			'disableCustomFontSizes'               => get_theme_support( 'disable-custom-font-sizes' ),
			'isRTL'                                => is_rtl(),
			'codeEditingEnabled'                   => false,
			'__experimentalBlockPatterns'          => array(),
			'__experimentalBlockPatternCategories' => array(),
			'activeComponents'                     => array_values( bp_core_get_active_components() ),
			'bodyPlaceholder'                      => sprintf( __( 'What’s new %s?', 'bp-gutenberg' ), bp_core_get_user_displayname( get_current_user_id() ) ),
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
function activity_block_editor_enqueue_assets() {
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

	wp_enqueue_script( 'bp-gutenberg-activity-editor' );

	$settings = activity_block_editor_get_settings();
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
		'bp-gutenberg-activity-editor',
		'window.bpGutenbergSettings = ' . wp_json_encode( $settings ) . ';'
	);

	// Preload server-registered block schemas.
	wp_add_inline_script(
		'wp-blocks',
		'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
	);

	// Editor default styles.
	wp_enqueue_style( 'bp-gutenberg-activity-editor' );
}

/**
 * Adds specific needed admin body classes.
 *
 * @since 1.0.0
 *
 * @param string $admin_body_class The Admin screen body classes.
 * @return string The Admin screen body classes.
 */
function activity_block_editor_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;
	$screen_class = preg_replace( '/[^a-z0-9_-]+/i', '-', $hook_suffix );

	if ( 'activity_page_bp-activity-new' !== $screen_class ) {
		$admin_body_class .= ' activity_page_bp-activity-new';
	}

	if ( defined( 'IFRAME_REQUEST' ) ) {
		$admin_body_class .= ' iframe';
	}

	return $admin_body_class;
}

/**
 * Activity Editor Screen.
 *
 *  @since 1.0.0
 */
function activity_block_editor_screen() {
	?>
	<div id="bp-gutenberg"></div>
	<div id="bp-gutenberg-notices"></div>
	<?php
}

/**
 * Adds an submenu to the Activity Admin menu.
 *
 * @since 1.0.0
 */
function activity_admin_submenu() {
	$screen = add_submenu_page(
		'bp-activity',
		__( 'Activity Block Editor', 'bp-gutenberg' ),
		__( 'Add new', 'bp-gutenberg' ),
		'exist',
		'bp-activity-new',
		__NAMESPACE__ . '\activity_block_editor_screen'
	);

	add_action( 'load-' . $screen, __NAMESPACE__ . '\activity_block_editor_load_screen' );
}
add_action( bp_core_admin_hook(), __NAMESPACE__ . '\activity_admin_submenu', 100 );

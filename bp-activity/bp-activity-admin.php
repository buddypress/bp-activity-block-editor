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
 * Registers the Activity Block Editor for the WP Admin context.
 *
 * @since 1.0.0
 */
function bp_activity_admin_register_editor() {
	$script_assets  = require_once plugin_dir_path( __FILE__ ) . 'block-editor/index.asset.php';

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

	add_action( 'bp_admin_enqueue_scripts', 'bp_activity_block_editor_enqueue_assets' );
	add_filter( 'admin_body_class', 'bp_activity_admin_body_class' );
}

/**
 * Registers the Activity Wall for the WP Admin context.
 *
 * @since 1.0.0
 */
function bp_activity_admin_register_wall() {
	$plugin_version = bp_activity_block_editor()->version;

	wp_register_script(
		'bp-activity-wall',
		plugins_url( 'activity-wall/index.js', __FILE__ ),
		array( 'lodash', 'wp-dom-ready', 'wp-i18n', 'wp-url' ),
		$plugin_version,
		true
	);

	wp_register_style(
		'bp-activity-wall',
		plugins_url( 'activity-wall/style-index.css', __FILE__ ),
		array( 'bp-admin-common-css' ),
		$plugin_version
	);

	add_action( 'bp_admin_enqueue_scripts', 'bp_activity_admin_enqueue_assets', 9 );
	add_action( 'admin_footer', 'bp_activity_admin_print_wall_templates' );
}

/**
 * Registers Activity assets for the main Activity Admin screen.
 *
 * @since 1.0.0
 */
function bp_activity_admin_load_screen() {
	bp_activity_admin_register_editor();
	bp_activity_admin_register_wall();

	/**
	 * This hook is used to register blocks for the BuddyPress Activity Block Editor.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_activity_enqueue_block_editor_assets' );
}

function bp_activity_admin_get_single_activity( $activity_id = 0 ) {
	$activity = null;

	if ( ! $activity_id ) {
		return $activity;
	}

	$activities  = bp_activity_get(
		array(
			'in'               => $activity_id,
			'show_hidden'      => true,
			'display_comments' => 'stream',
		)
	);

	if ( isset( $activities['activities'] ) ) {
		$activity = reset( $activities['activities'] );
	}

	return $activity;
}

/**
 * Registers Activity assets for the Edit/View Activity Admin screens.
 *
 * @since 1.0.0
 */
function bp_activity_admin_load_single_screen() {
	$allowed_screens = array( 'activity_page_bp-view-activity', 'activity_page_bp-edit-activity' );
	$current_screen  = '';

	if ( function_exists( 'get_current_screen') ) {
		$current_screen = get_current_screen()->id;
	}

	if ( isset( $_GET['aid'] ) && in_array( $current_screen, $allowed_screens, true ) ) {
		$is_edit = 'activity_page_bp-edit-activity' === $current_screen;

		// Register the Block Editor.
		bp_activity_admin_register_editor();

		$activity_id = absint( wp_unslash( $_GET['aid'] ) );
		$activity    = bp_activity_admin_get_single_activity( $activity_id );

		if ( isset( $activity->user_id ) ) {
			if ( $is_edit ) {
				// Starts easy before dealing with more complex capabilities.
				if ( bp_loggedin_user_id() !== (int) $activity->user_id ) {
					wp_die( __( 'You are not the author of this activity. Only Activity authors can edit their activities.', 'bp-activity-block-editor' ) );
				}

				bp_activity_block_editor()->edit_activity = $activity;
			} else {
				bp_activity_block_editor()->view_activity = $activity;
			}

		} else {
			wp_die( __( 'The activity is missing.', 'bp-activity-block-editor' ) );
		}

		if ( ! $is_edit ) {
			bp_activity_admin_register_wall();
		}

		/**
		 * This hook is used to register blocks for the BuddyPress Activity Block Editor.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bp_activity_enqueue_block_editor_assets' );
	} else {
		wp_die( __( 'The activity ID is missing.', 'bp-activity-block-editor' ) );
	}
}

/**
 * Sets the Activity block editor settings.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_get_settings() {
	wp_add_inline_script(
		'wp-blocks',
		sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( bp_activity_get_block_categories() ) ),
		'after'
	);

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
				'allowBlocks' => array(
					'core/paragraph',
					'core/embed',
					'bp/image-attachment',
					'bp/video-attachment',
					'bp/audio-attachment',
					'bp/file-attachment',
				),
			),
			'toolbar' => array(
				'inspector'         => true,
				'documentInspector' => __( 'Activity', 'bp-activity-block-editor' ),
			),
			'moreMenu' => array(
				'topToolbar' => true,
				'fullscreen' => defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST,
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

	$viewed_activity = bp_activity_block_editor()->view_activity;
	if ( isset ( $viewed_activity->user_id ) ) {
		$settings['editor']['bodyPlaceholder'] = sprintf( __( 'Engage into the conversation and reply to %s!', 'bp-activity-block-editor' ), bp_core_get_user_displayname( $viewed_activity->user_id ) );
	}

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
 * Enqueues Admin assets.
 *
 * @since 1.0.0
 */
function bp_activity_admin_enqueue_assets() {
	wp_enqueue_style( 'bp-activity-wall' );

	// Check if we're displaying an activity.
	$activity = bp_activity_block_editor()->view_activity;

	$bp_base = sprintf(
		'/%1$s/%2$s/',
		bp_rest_namespace(),
		bp_rest_version()
	);

	$request_args = array(
		'_embed'                => true,
		'_is_bp_activity_admin' => true,
	);

	// We need this activity comments.
	if ( isset( $activity->id ) ) {
		$request_args = array_merge(
			$request_args,
			array(
				'primary_id'       => $activity->id,
				'type'             => 'activity_comment',
				'display_comments' => true,
				'order'            => 'asc',
			)
		);
	}

	$activity_path = add_query_arg(
		$request_args,
		$bp_base . 'activity'
	);

	$member_path = $bp_base . 'members/me';

	// Preloads BP Members directory data.
	$preload_data = array_reduce(
		array( $activity_path, $member_path ),
		'rest_preload_api_request',
		array()
	);

	$script_strings = array(
		'path'              => ltrim( $activity_path, '/' ),
		'root'              => esc_url_raw( get_rest_url() ),
		'nonce'             => wp_create_nonce( 'wp_rest' ),
		'preloadedActivity' => $preload_data[ $activity_path ],
		'preloadedMember'   => $preload_data[ $member_path ],
	);

	$script_strings['currentActivity'] = null;
	if ( isset( $activity->id ) ) {
		$single_path      = sprintf( $bp_base . 'activity/%d', $activity->id );
		$current_activity = rest_preload_api_request( array(), $single_path );

		$script_strings['currentActivity'] = null;
		if ( isset( $current_activity[ $single_path ]['body'][0] ) ) {
			$script_strings['currentActivity'] = $current_activity[ $single_path ]['body'][0];
		}
	}

	wp_enqueue_script( 'bp-activity-wall' );
	wp_localize_script( 'bp-activity-wall', 'bpActivityWallSettings', $script_strings );
}

/**
 * Enqueues the Activity Editor assets.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_enqueue_assets() {
	$main_instance                        = bp_activity_block_editor();
	$settings                             = bp_activity_block_editor_get_settings();
	$settings['editor']['activityEdit']   = $main_instance->edit_activity;
	$settings['editor']['parentActivity'] = $main_instance->view_activity;

	if ( ! is_null( $settings['editor']['parentActivity'] ) && isset( $settings['iso']['toolbar']['documentInspector'] ) ) {
		unset( $settings['iso']['toolbar']['documentInspector'] );
	}

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

	if ( isset( $_GET['aid'] ) ) {
		wp_add_inline_style(
			'common',
			'#wpbody-content {
				background-color: #f0f0f1;
			}'
		);
	}

	/**
	 * Add a setting to inform whether the Activity Block Editor
	 * is used form the Activity Admin screen or not.
	 */
	$settings['editor']['isDialog']        = defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	$settings['editor']['hasActivityWall'] = ! defined( 'IFRAME_REQUEST' );

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
	$admin_body_class .= ' bp-is-tabbed-screen';

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
 * Activity Admin screen.
 *
 *  @since 1.0.0
 */
function bp_activity_admin_screen() {
	$main_instance = bp_activity_block_editor();
	$context       = 'bp-activity';
	$current_tab   = __( 'Everyone', 'bp-activity-block-editor' );

	if ( ! is_null( $main_instance->edit_activity ) ) {
		$context     = 'bp-edit-activity';
		$current_tab = __( 'Edit Activity', 'bp-activity-block-editor' );
	} elseif ( ! is_null( $main_instance->view_activity ) ) {
		$context     = 'bp-view-activity';
		$current_tab = __( 'View Activity', 'bp-activity-block-editor' );
	}

	bp_core_admin_tabbed_screen_header( __( 'Activity', 'bp-activity-block-editor' ), $current_tab, $context );

	if ( 'bp-view-activity' !== $context ) {
		?>
		<div class="buddypress-body">
			<div id="bp-activity-block-editor"></div>
			<div id="bp-activity-block-editor-notices"></div>
			<div id="bp-activity-wall-items"></div>
		</div>
		<?php
	} else {
		?>
		<div class="buddypress-body">
			<div id="bp-activity-view"></div>
			<div id="bp-activity-wall-items"></div>
			<div id="bp-activity-block-editor"></div>
			<div id="bp-activity-block-editor-notices"></div>
		</div>
		<?php
	}
}

/**
 * Includes Activity Wall templates
 *
 * @since .1.0.0
 */
function bp_activity_admin_print_wall_templates() {
	require_once plugin_dir_path( __FILE__ ) . 'bp-activity-wall-templates.php';
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

	$edit_screen = add_submenu_page(
		'bp-activities',
		_x( 'Edit Activity', 'Admin Dashboard Activity Edit page title', 'bp-activity-block-editor' ),
		_x( 'Edit Activity', 'Admin Dashboard Activity Edit menu', 'bp-activity-block-editor' ),
		'exist',
		'bp-edit-activity',
		'bp_activity_admin_screen',
	);

	$view_screen = add_submenu_page(
		'bp-activities',
		_x( 'View Activity', 'Admin Dashboard Activity Edit page title', 'bp-activity-block-editor' ),
		_x( 'View Activity', 'Admin Dashboard Activity Edit menu', 'bp-activity-block-editor' ),
		'exist',
		'bp-view-activity',
		'bp_activity_admin_screen',
	);

	add_action( 'load-' . $screen, 'bp_activity_admin_load_screen' );
	add_action( 'load-' . $edit_screen, 'bp_activity_admin_load_single_screen' );
	add_action( 'load-' . $view_screen, 'bp_activity_admin_load_single_screen' );
}

/**
 * Adds the Activity menu to custom BuddyPress menus.
 *
 * @since 1.0.0
 *
 * @param array $custom_menus The BuddyPress custom menus.
 * @return array The BuddyPress custom menus.
 */
function bp_activity_admin_filter_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-activities' );
	return $custom_menus;
}

/**
 * Remove Activity submenus.
 *
 * @since 1.0.0
 */
function bp_activity_admin_head() {
	remove_submenu_page( 'bp-activities', 'bp-edit-activity' );
	remove_submenu_page( 'bp-activities', 'bp-view-activity' );
	remove_submenu_page( 'bp-activities', 'bp-activities' );
}

/**
 * Adds tabs to the Activity Admin.
 *
 * @since 1.0.0
 *
 * @param array $tabs A list of Admin tabs.
 * @param string $context The Admin context tabs should be output.
 * @return array The list of Admin tabs for the given context.
 */
function bp_activity_admin_get_tabs( $tabs = array(), $context = '' ) {
	$activity_id  = 0;
	$activity_all = array();

	if ( isset( $_GET['aid'] ) ) {
		$activity_id  = (int) $_GET['aid'];
		$activity_all = array(
			'id'   => 'bp-activity-all',
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-activities' ), 'admin.php' ) ),
			'name' => __( 'All activities', 'bp-activity-block-editor' ),
		);
	}

	if ( 'bp-activity' === $context ) {
		$tabs = array(
			'0' => array(
				'id'   => 'bp-activity-everyone',
				'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-activities' ), 'admin.php' ) ),
				'name' => __( 'Everyone', 'bp-activity-block-editor' ),
			),
			'1' => array(
				'id'   => 'bp-activity-personal',
				'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-activities' ), 'admin.php' ) ),
				'name' => __( 'Personal', 'bp-activity-block-editor' ),
			),
		);
	} elseif ( 'bp-edit-activity' === $context ) {
		$tabs = array(
			'0' => array(
				'id'   => 'bp-activity-edit',
				'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-edit-activity', 'aid' => $activity_id ), 'admin.php' ) ),
				'name' => __( 'Edit Activity', 'bp-activity-block-editor' ),
			),
			'1' => $activity_all,
		);
	} elseif ( 'bp-view-activity' === $context ) {
		$tabs = array(
			'0' => array(
				'id'   => 'bp-activity-view',
				'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-view-activity', 'aid' => $activity_id ), 'admin.php' ) ),
				'name' => __( 'View Activity', 'bp-activity-block-editor' ),
			),
			'1' => $activity_all,
		);
	}

	return $tabs;
}
add_filter( 'bp_core_get_admin_tabs', 'bp_activity_admin_get_tabs', 10, 2 );

/**
 * Inform the Admin user this plugin requires the Activity component to be active.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_admin_notice() {
	printf(
		'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
		esc_html__( 'BP Activity Block Editor needs the BP Activity component to be active.', 'bp-activity-block-editor' )
	);
}

/**
 * Checks the Activity component is active before generating admin menu and screen functions.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_admin_hooks() {
	if ( ! bp_is_active( 'activity' ) ) {
		add_action( 'admin_notices', 'bp_activity_block_editor_admin_notice' );
	} else {
		add_action( bp_core_admin_hook(), 'bp_activity_admin_replace_menu', 9 );
		add_action( 'bp_admin_head', 'bp_activity_admin_head', 998 );
		add_filter( 'bp_admin_menu_order', 'bp_activity_admin_filter_menu_order' );
	}
}
add_action( 'bp_init', 'bp_activity_block_editor_admin_hooks' );

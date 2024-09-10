<?php
/**
 * BuddyPress Activity Admin functions.
 *
 * @package bp-activity-block-editor\bp-activity
 * @since 1.0.0
 */

namespace BP\Activity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Activity Block Editor for the WP Admin context.
 *
 * @since 1.0.0
 */
function bp_activity_admin_register_block_editor() {
	bp_activity_register_block_editor();

	add_action( 'bp_admin_enqueue_scripts', __NAMESPACE__ . '\bp_activity_block_editor_enqueue_assets' );
	add_filter( 'admin_body_class', __NAMESPACE__ . '\bp_activity_admin_body_class' );
}

/**
 * Registers the Activity Wall for the WP Admin context.
 *
 * @since 1.0.0
 */
function bp_activity_admin_register_wall() {
	$plugin_version = bp_activity()->version;

	wp_register_script(
		'bp-activity-wall',
		plugins_url( 'activity-wall/index.js', __FILE__ ),
		array( 'lodash', 'wp-dom-ready', 'wp-i18n', 'wp-url', 'wp-api-fetch' ),
		$plugin_version,
		true
	);

	wp_register_style(
		'bp-activity-wall',
		plugins_url( 'activity-wall/style-index.css', __FILE__ ),
		array( 'bp-admin-common-css', 'dashicons' ),
		$plugin_version
	);

	add_action( 'bp_admin_enqueue_scripts', __NAMESPACE__ . '\bp_activity_admin_enqueue_assets', 9 );
	add_action( 'admin_footer', __NAMESPACE__ . '\bp_activity_admin_print_wall_templates' );
}

/**
 * Registers Activity assets for the main Activity Admin screen.
 *
 * @since 1.0.0
 */
function bp_activity_admin_load_screen() {
	bp_activity_admin_register_block_editor();
	bp_activity_admin_register_wall();

	/**
	 * This hook is used to register blocks for the BuddyPress Activity Block Editor.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_activity_enqueue_block_editor_assets' );
}

/**
 * Get an activity object according to its ID.
 *
 * @since 1.0.0
 *
 * @param int $activity_id The Activity ID.
 * @return BP_Activity_Activity The Activity object.
 */
function bp_activity_admin_get_single_activity( $activity_id = 0 ) {
	$activity = null;

	if ( ! $activity_id ) {
		return $activity;
	}

	$activities = bp_activity_get(
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

	if ( function_exists( 'get_current_screen' ) ) {
		$current_screen = get_current_screen()->id;
	}

	if ( isset( $_GET['aid'] ) && in_array( $current_screen, $allowed_screens, true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$is_edit = 'activity_page_bp-edit-activity' === $current_screen;

		// Register the Block Editor.
		bp_activity_admin_register_block_editor();

		$activity_id = absint( wp_unslash( $_GET['aid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$activity    = bp_activity_admin_get_single_activity( $activity_id );

		if ( isset( $activity->user_id ) ) {
			if ( $is_edit ) {
				// Starts easy before dealing with more complex capabilities.
				if ( bp_loggedin_user_id() !== (int) $activity->user_id ) {
					wp_die( esc_html__( 'You are not the author of this activity. Only Activity authors can edit their activities.', 'bp-activity-block-editor' ) );
				}

				bp_activity()->edit_activity = $activity;
			} else {
				bp_activity()->view_activity = $activity;
			}
		} else {
			wp_die( esc_html__( 'The activity is missing.', 'bp-activity-block-editor' ) );
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
		wp_die( esc_html__( 'The activity ID is missing.', 'bp-activity-block-editor' ) );
	}
}

/**
 * Enqueues Admin assets.
 *
 * @since 1.0.0
 */
function bp_activity_admin_enqueue_assets() {
	//wp_enqueue_style( 'bp-activity-wall' );

	// Check if we're displaying an activity.
	$activity = bp_activity()->view_activity;

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

	//wp_enqueue_script( 'bp-activity-wall' );
	//wp_localize_script( 'bp-activity-wall', 'bpActivityWallSettings', $script_strings );
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

	$edit_activity = bp_activity()->edit_activity;

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
	$main_instance = bp_activity();
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
	?>
	<div class="buddypress-body">
		<dialog id="bp-confirm-action">
			<form method="dialog">
				<p>
					<?php esc_html_e( 'Are you sure you want to do this?', 'bp-activity-block-editor' ); ?>
				</p>
				<menu>
					<button value="cancel" class="button button-secondary"><?php esc_html_e( 'Cancel', 'bp-activity-block-editor' ); ?></button>
					<button value="confirm" class="button button-primary"><?php esc_html_e( 'Ok', 'bp-activity-block-editor' ); ?></button>
				</menu>
			</form>
		</dialog>

	<?php
	if ( 'bp-view-activity' !== $context ) {
		?>
			<div id="bp-activity-block-editor"></div>
			<div id="bp-activity-block-editor-notices"></div>
			<div id="bp-activity-wall-items"></div>
			<!-- Testing a tiny editor. -->
			<div id="bp-activity-editor"></div>
		<?php
	} else {
		?>
			<div id="bp-activity-view"></div>
			<div id="bp-activity-wall-items"></div>
			<div id="bp-activity-block-editor"></div>
			<div id="bp-activity-block-editor-notices"></div>
		<?php
	}
	?>
	</div>
	<?php
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
		__NAMESPACE__ . '\bp_activity_admin_screen',
		'dashicons-buddicons-activity'
	);

	$edit_screen = add_submenu_page(
		'bp-activities',
		_x( 'Edit Activity', 'Admin Dashboard Activity Edit page title', 'bp-activity-block-editor' ),
		_x( 'Edit Activity', 'Admin Dashboard Activity Edit menu', 'bp-activity-block-editor' ),
		'exist',
		'bp-edit-activity',
		__NAMESPACE__ . '\bp_activity_admin_screen'
	);

	$view_screen = add_submenu_page(
		'bp-activities',
		_x( 'View Activity', 'Admin Dashboard Activity Edit page title', 'bp-activity-block-editor' ),
		_x( 'View Activity', 'Admin Dashboard Activity Edit menu', 'bp-activity-block-editor' ),
		'exist',
		'bp-view-activity',
		__NAMESPACE__ . '\bp_activity_admin_screen'
	);

	add_action( 'load-' . $screen, __NAMESPACE__ . '\bp_activity_admin_load_screen' );
	add_action( 'load-' . $edit_screen, __NAMESPACE__ . '\bp_activity_admin_load_single_screen' );
	add_action( 'load-' . $view_screen, __NAMESPACE__ . '\bp_activity_admin_load_single_screen' );
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
 * @param array  $tabs A list of Admin tabs.
 * @param string $context The Admin context tabs should be output.
 * @return array The list of Admin tabs for the given context.
 */
function bp_activity_admin_get_tabs( $tabs = array(), $context = '' ) {
	$activity_id  = 0;
	$activity_all = array();

	if ( isset( $_GET['aid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$activity_id  = (int) $_GET['aid']; // phpcs:ignore WordPress.Security.NonceVerification
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
				'href' => bp_get_admin_url(
					add_query_arg(
						array(
							'page' => 'bp-edit-activity',
							'aid'  => $activity_id,
						),
						'admin.php'
					)
				),
				'name' => __( 'Edit Activity', 'bp-activity-block-editor' ),
			),
			'1' => $activity_all,
		);
	} elseif ( 'bp-view-activity' === $context ) {
		$tabs = array(
			'0' => array(
				'id'   => 'bp-activity-view',
				'href' => bp_get_admin_url(
					add_query_arg(
						array(
							'page' => 'bp-view-activity',
							'aid'  => $activity_id,
						),
						'admin.php'
					)
				),
				'name' => __( 'View Activity', 'bp-activity-block-editor' ),
			),
			'1' => $activity_all,
		);
	}

	return $tabs;
}
add_filter( 'bp_core_get_admin_tabs', __NAMESPACE__ . '\bp_activity_admin_get_tabs', 10, 2 );

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
		add_action( 'admin_notices', __NAMESPACE__ . '\bp_activity_block_editor_admin_notice' );
	} else {
		add_action( bp_core_admin_hook(), __NAMESPACE__ . '\bp_activity_admin_replace_menu', 9 );
		add_action( 'bp_admin_head', __NAMESPACE__ . '\bp_activity_admin_head', 998 );
		add_filter( 'bp_admin_menu_order', __NAMESPACE__ . '\bp_activity_admin_filter_menu_order' );
	}
}
add_action( 'bp_init', __NAMESPACE__ . '\bp_activity_block_editor_admin_hooks' );

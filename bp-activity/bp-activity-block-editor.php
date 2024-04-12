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
 * Sets the Activity block editor settings.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_get_settings() {
	$block_editor_context = new WP_Block_Editor_Context( array( 'name' => 'bp/edit-activity' ) );

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
			'footer'   => true,
			'toolbar'  => array(
				'inserter'          => false,
				'undo'              => false,
				'inspector'         => true,
				'documentInspector' => __( 'Activity', 'bp-activity-block-editor' ),
			),
			'moreMenu' => false,
		),
		'editor' => array_merge(
			array(
				'disableCustomColors'                  => true,
				'disableCustomFontSizes'               => true,
				'isRTL'                                => is_rtl(),
				'codeEditingEnabled'                   => false,
				'__experimentalBlockPatterns'          => array(),
				'__experimentalBlockPatternCategories' => array(),
				'__experimentalFeatures'               => array(
					'typography' => array(
						'dropCap' => false,
					),
				),
				'activeComponents'                     => array_values( bp_core_get_active_components() ),
				'bodyPlaceholder'                      => sprintf(
					/* translators: %s is the user display name. */
					__( 'What’s new %s?', 'bp-activity-block-editor' ),
					bp_core_get_user_displayname( get_current_user_id() )
				),
				'canLockBlocks'                        => false,
				'allowedBlockTypes'                    => get_allowed_block_types( $block_editor_context ),
			),
			$custom_editor_settings
		),
	);

	$viewed_activity = bp_activity_block_editor()->view_activity;
	if ( isset( $viewed_activity->user_id ) ) {
		$settings['editor']['bodyPlaceholder'] = sprintf(
			/* translators: %s is the user display name. */
			__( 'Engage into the conversation and reply to %s!', 'bp-activity-block-editor' ),
			bp_core_get_user_displayname( $viewed_activity->user_id )
		);
	}

	return $settings;
}

function bp_activity_register_block_editor() {
	$script_assets = require_once plugin_dir_path( __FILE__ ) . 'block-editor/index.asset.php';

	wp_register_script(
		'bp-activity-block-editor',
		plugins_url( 'block-editor/index.js', __FILE__ ),
		array_merge( $script_assets['dependencies'], array( 'bp-block-components' ) ),
		$script_assets['version'],
		true
	);

	$autocompleter_assets = require_once plugin_dir_path( __FILE__ ) . 'autocompleter/index.asset.php';

	wp_register_script(
		'bp-activity-block-editor-emojis',
		plugins_url( 'autocompleter/index.js', __FILE__ ),
		$autocompleter_assets['dependencies'],
		$autocompleter_assets['version'],
		true
	);

	if ( is_buddypress() ) {
		$wp_styles = wp_styles();

		// Remove some conflicting dependencies and replace 'wp-edit-blocks' by 'wp-block-editor-content'.
		$wp_styles->registered['wp-reset-editor-styles']->deps = array();
		$wp_styles->registered['wp-edit-post']->deps           = array_diff( $wp_styles->registered['wp-edit-post']->deps, array( 'wp-commands', 'wp-preferences', 'wp-edit-blocks' ) );
		$wp_styles->registered['wp-edit-post']->deps[]         = 'wp-block-editor-content';
	}

	// This stylesheet is needed for template packs.
	wp_register_style(
		'bp-activity-block-editor-front',
		plugins_url( 'block-editor/index.css', __FILE__ ),
		array(),
		$script_assets['version']
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
	wp_enqueue_script( 'bp-activity-block-editor-emojis' );

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

	if ( isset( $_GET['aid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_add_inline_style(
			'common',
			'#wpbody-content {
				background-color: #f0f0f1;
			}'
		);
	}

	/**
	 * Add a setting to inform whether the Activity Block Editor
	 * is used from the Activity Admin screen or not.
	 */
	$settings['editor']['isDialog']        = defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	$settings['editor']['hasActivityWall'] = ! defined( 'IFRAME_REQUEST' );

	wp_add_inline_script(
		'bp-activity-block-editor',
		'window.bpActivityBlockEditor = ' . wp_json_encode( $settings ) . ';'
	);

	// Editor default styles.
	wp_enqueue_style( 'bp-activity-block-editor' );
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
		if ( ! isset( $category['slug'] ) ) {
			continue;
		}

		if ( 'embed' === $category['slug'] ) {
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
 * Returns the list of allowed block types to use in the Activity block editor.
 *
 * @since 1.0.0
 *
 * @param bool|string[]           $allowed_block_types  Array of block type slugs, or boolean to enable/disable all.
 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
 * @return bool|string[]          Array of block type slugs, or boolean to enable/disable all.
 */
function bp_activity_allowed_block_types( $allowed_block_types, $block_editor_context ) {
	if ( isset( $block_editor_context->name ) && 'bp/edit-activity' === $block_editor_context->name ) {
		$activity_block_types = array( 'core/paragraph', 'core/embed' );
		$block_registry       = WP_Block_Type_Registry::get_instance();

		// Allow all Block types having the 'activity' `buddypress_contexts`.
		foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
			if ( empty( $block_type->buddypress_contexts ) || ! in_array( 'activity', $block_type->buddypress_contexts, true ) ) {
				continue;
			}

			$activity_block_types[] = $block_name;
		}

		/**
		 * Filter here to enable custom BP Activity block types.
		 *
		 * @since 1.0.0
		 *
		 * @param $allowed_block_types  Array of block type slugs.
		 */
		$allowed_block_types = apply_filters( 'bp_activity_allowed_block_types', $activity_block_types );
	}

	return $allowed_block_types;
}
add_filter( 'allowed_block_types_all', 'bp_activity_allowed_block_types', 10, 2 );

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

/**
 * Checks whether the Activity Block Editor is supported by the theme.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_is_supported() {
	$support = false;
	$feature = bp_get_theme_compat_feature( 'activity-block-editor' );

	if ( bp_use_theme_compat_with_current_theme() && $feature ) {
		if ( bp_is_group() ) {
			$support = in_array( 'group', $feature->single_items, true );
		} elseif ( bp_is_user() ) {
			$support = in_array( 'member', $feature->single_items, true );
		} else {
			$support = true;
		}

		// Standalone themes needs to add support to the activity editor.
	} else {
		$support = current_theme_supports( 'buddypress', array( 'activity' => 'block-editor' ) );
	}

	return $support;
}

/**
 * Registers the Activity Block Editor for the front-end context.
 *
 * @since 1.0.0
 */
function bp_activity_front_register_block_editor() {
	// Only load the Block Editor for logged in users.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Starts with Activity directory.
	if ( ! bp_is_activity_directory() && ! bp_is_group_activity() && ! bp_is_user_activity() ) {
		return;
	}

	if ( bp_activity_block_editor_is_supported() ) {
		bp_activity_register_block_editor();

		add_action( 'bp_enqueue_community_scripts', 'bp_activity_block_editor_enqueue_assets' );

		/**
		 * This hook is used to register blocks for the BuddyPress Activity Block Editor.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bp_activity_enqueue_block_editor_assets' );
	}
}
add_action( 'bp_setup_canonical_stack', 'bp_activity_front_register_block_editor', 40 );

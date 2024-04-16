<?php
/**
 * BuddyPress Nouveau Template Pack functions.
 *
 * @todo This part needs to be added to BuddyPress Core.
 *
 * @package bp-activity-block-editor\bp-templates
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Make BP Nouveau supports the Activity Block Editor.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_set_feature() {
	$feature = bp_get_theme_compat_feature( 'activity-block-editor' );
	$pack_id = bp_get_theme_compat_id();

	if ( ! $feature && 'nouveau' === $pack_id ) {
		bp_set_theme_compat_feature(
			$pack_id,
			array(
				'name'     => 'activity-block-editor',
				'settings' => array(
					'single_items' => array( 'member', 'group' ),
				),
			)
		);

		// The Activity Block Editor already includes natively an `@member` autocompleter.
		add_filter( 'bp_activity_maybe_load_mentions_scripts', '__return_false' );
	}
}
add_action( 'bp_after_setup_theme', 'bp_activity_block_editor_set_feature', 12 );

/**
 * Checks whether the Block Editor is being used.
 *
 * @since 1.0.0
 *
 * @return boolean True if the Block Editor is being used. False otherwise.
 */
function bp_activity_use_block_editor() {
	$feature = bp_get_theme_compat_feature( 'activity-block-editor' );

	if ( ! $feature ) {
		return false;
	}

	return bp_is_activity_directory() || ( bp_is_group_activity() && in_array( 'group', $feature->single_items, true ) ) || ( bp_is_user_activity() && in_array( 'member', $feature->single_items, true ) );
}

/**
 * Unregister the BP Nouveau Activity post form.
 *
 * NB: this is safer than dequeing it as some 3rd party plugins may add a
 * dependency to it.
 *
 * @since 1.0.0
 *
 * @param array $scripts The BP Nouveau scripts to register.
 * @return array The BP Nouveau scripts to register.
 */
function bp_activity_block_editor_unregister_activity_post_form( $scripts = array() ) {
	if ( bp_activity_use_block_editor() ) {
		unset( $scripts['bp-nouveau-activity-post-form'] );
	}

	return $scripts;
}
add_filter( 'bp_nouveau_register_scripts', 'bp_activity_block_editor_unregister_activity_post_form', 100, 1 );

/**
 * Enqueues the Activity Block Editor.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_dequeue_activity_post_form() {
	if (  ! bp_activity_use_block_editor() ) {
		return;
	}

	wp_enqueue_style( 'bp-activity-block-editor-front' );
	remove_action( 'wp_footer', 'bp_nouveau_activity_print_post_form_templates' );

	?>
	<div id="bp-activity-block-editor"></div>
	<div id="bp-activity-block-editor-notices"></div>
	<?php
}
add_action( 'bp_after_activity_post_form', 'bp_activity_block_editor_dequeue_activity_post_form', 1 );

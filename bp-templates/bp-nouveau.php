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
	}
}
add_action( 'bp_after_setup_theme', 'bp_activity_block_editor_set_feature', 12 );

/**
 * Dequeues the Nouveau Activity Post Form script.
 *
 * @since 1.0.0
 */
function bp_activity_block_editor_dequeue_activity_post_form() {
	$feature = bp_get_theme_compat_feature( 'activity-block-editor' );

	if ( ! $feature ) {
		return;
	}

	if ( bp_is_activity_directory() || ( bp_is_group_activity() && in_array( 'group', $feature->single_items, true ) ) || ( bp_is_user_activity() && in_array( 'member', $feature->single_items, true ) ) ) {
		wp_dequeue_script( 'bp-nouveau-activity-post-form' );
		wp_enqueue_style( 'bp-activity-block-editor-front' );
		remove_action( 'wp_footer', 'bp_nouveau_activity_print_post_form_templates' );

		?>
		<div id="bp-activity-block-editor"></div>
		<div id="bp-activity-block-editor-notices"></div>
		<?php
	}
}
add_action( 'bp_after_activity_post_form', 'bp_activity_block_editor_dequeue_activity_post_form', 1 );

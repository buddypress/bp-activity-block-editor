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

function filter_activity_entry_buttons( $buttons, $activity_id ) {

	if ( bp_activity_type_supports( bp_get_activity_type(), 'likes' ) ) {

		if ( bp_activity_is_liked() ) {
			$like_props = array(
				'action'  => 'remove',
				'id'      => 'alike-dislike-' . $activity_id,
				'class'   => 'alike-remove',
				'tooltip' => _x( 'Dislike', 'button', 'buddypress' ),
				'text'    => _x( 'Liked', 'link', 'buddypress' ),
			);
		} else {
			$like_props = array(
				'action'  => 'add',
				'id'      => 'alike-like-' . $activity_id,
				'class'   => 'alike-add',
				'tooltip' => _x( 'Like', 'button', 'buddypress' ),
				'text'    => _x( 'Like', 'link', 'buddypress' ),
			);
		}

		$buttons['activity_like'] =  array(
			'id'                => 'activity_like',
			'position'          => 20,
			'component'         => 'activity',
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'must_be_logged_in' => true,
			'button_element'    => $button_element,
			'button_attr'       => array(
				'id'              => $like_props['id'],
				'class'           => sprintf( 'button %s bp-primary-action bp-tooltip', $like_props['class'] ),
				'data-bp-tooltip' => $like_props['tooltip'],
				'aria-expanded'   => 'false',
			),
			'link_text'  => sprintf(
				'<span class="bp-screen-reader-text">%1$s</span> <span class="like-count">%2$s</span>',
				$like_props['text'],
				esc_html( bp_activity_get_like_count() )
			),
		);

		// If button element set add href link to data-attr
		if ( 'button' === $button_element ) {
			$buttons['activity_like']['button_attr']['data-bp-url'] = bp_get_activity_like_link( $like_props['action'] );
		} else {
			$buttons['activity_like']['button_attr']['href'] = bp_get_activity_like_link( $like_props['action'] );
			$buttons['activity_like']['button_attr']['role'] = 'button';
		}
	}

	return $buttons;
}
add_filter( 'bp_nouveau_get_activity_entry_buttons', __NAMESPACE__ . '\filter_activity_entry_buttons', 10, 2 );

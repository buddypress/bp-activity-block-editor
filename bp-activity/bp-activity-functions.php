<?php
/**
 * BuddyPress Activity functions.
 *
 * @package bp-activity
 * @since 1.0.0
 */

namespace BP\Activity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Types ******************************************************************/

/**
 * Registers an activity type.
 *
 * @since 1.0.2
 *
 * @param string $type The name key of the activity type.
 * @param array  $args {
 *     Array or string of arguments for registering an activity type.
 *
 *     @type string[] $components      An array of supported BP components for the activity type.
 *                                     Default ['activity'].
 *     @type string   $role            Whether the activity will display content, log or a reaction (eg: activity comment).
 *                                     Default 'content'.
 *     @type string   $description     A short descriptive summary of what the post type is.
 *                                     Default empty.
 *     @type string[] $labels          A keyed array of labels for this activity type. If not set, activity
 *                                     labels are using the name key of the activity type. Available labels
 *                                     are: 'front_filter' & 'admin_filter'.
 *     @type string   $format_callback Callback for formatting the action string.
 *                                     Default ''.
 *     @type string[] $streams         A list of supported stream contexts for the activity type.
 *                                     List may include 'activity', 'member', 'member_groups', 'group'.
 *                                     Default ['activity'].
 *     @type integer  $position        The order of the activity type in front-end dropdowns.
 *                                     Default 0.
 *     @type array    $supports        The list of supported features for the activity type.
 *                                     Default [].
 * }
 * @return BP_Activity_Type|WP_Error The registered activity type object on success,
 *                                   WP_Error object on failure.
 */
function bp_register_activity_type( $type, $args = array() ) {
	$activity_types = buddypress()->activity->types;

	// Sanitize the name key of the activity type.
	$type = sanitize_key( $type );

	if ( empty( $type ) || strlen( $type ) > 20 ) {
		_doing_it_wrong( __FUNCTION__, __( 'BP Activity Type names must be between 1 and 20 characters in length.', 'buddypress' ), '14.0.0' );
		return new WP_Error( 'activity_type_length_invalid', __( 'BP Activity Type names must be between 1 and 20 characters in length.', 'buddypress' ) );
	}

	if ( isset( $activity_types[ $type ] ) ) {
		return new WP_Error( 'activity_type_already_registered', __( 'BP Activity Types are unique. The type you want to register already exists.', 'buddypress' ) );
	}

	$type_object                           = new BP_Activity_Type( $type, $args );
	buddypress()->activity->types[ $type ] = $type_object;

	return $type_object;
}

/**
 * Unregisters an activity type.
 *
 * @since 1.0.2
 *
 * @param  string $type The name key of the activity type.
 * @return bool|WP_Error True on success. A `WP_Error` object on failure.
 */
function bp_unregister_activity_type( $type ) {
	$type_object = bp_get_activity_type_object( $type );

	if ( is_wp_error( $type_object ) ) {
		return $type_object;
	}

	unset( buddypress()->activity->types[ $type_object->name ] );

	return true;
}

/**
 * Retrieves the Activity type object for a specific type.
 *
 * @since 14.0.0
 *
 * @param string $type The name key of the activity type.
 * @return WP_Error|BP_Activity_Type The Activity type object.
 */
function bp_get_activity_type_object( $type ) {
	$activity_types = buddypress()->activity->types;

	// Sanitize activity type name.
	$type = sanitize_key( $type );

	if ( ! isset( $activity_types[ $type ] ) ) {
		return new WP_Error( 'invalid_activity_type', __( 'Invalid activity type.', 'buddypress' ) );
	}

	return $activity_types[ $type ];
}

/**
 * Retrieves the list of Activity types having a specific role.
 *
 * @since 14.0.0
 *
 * @param string $role The requested role for activity types to retrieve.
 * @return array The list of Activity type name keys having the requested role.
 */
function bp_get_activity_types_for_role( $role ) {
	$types = wp_filter_object_list( buddypress()->activity->types, array( 'role' => $role ), 'and', 'name' );
	return array_values( $types );
}

/** Interactions **************************************************************/

/**
 * Interact with an activity.
 *
 * @since 14.0.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int        $user_id           Optional. The ID of the user reacting.
 *                                         Defaults to the ID of the logged-in user.
 *     @type object|int $activity          Required. The parent activity object or its ID.
 *                                         Defaults to 0.
 *     @type string     $reaction_type     Optional. The reaction activity type.
 *                                         Defaults to 'activity_like'.
 *     @type string     $primary_link      Optional. The primary link for the reaction activity type.
 *                                         Defaults to ''.
 *     @type bool       $skip_notification Optional. false to send a reaction notification, true otherwise.
 *                                         Defaults to true.
 * }
 * @return WP_Error|integer The ID of the reaction on success, otherwise false.
 */
function bp_activity_add_interaction( $args = '' ) {
	$r = bp_parse_args(
		$args,
		array(
			'user_id'           => bp_loggedin_user_id(),
			'activity'          => 0,
			'reaction_type'     => 'activity_like',
			'primary_link'      => '',
			'skip_notification' => true,
		)
	);

	// Get the Activity reaction object.
	$reaction_object = bp_get_activity_type_object( $r['reaction_type'] );
	if ( is_wp_error( $reaction_object ) || 'reaction' !== $reaction_object->role ) {
		return new WP_Error(
			'activity_reaction_unregistered',
			__( 'This activity reaction is not registered.', 'buddypress' )
		);
	}

	// Bail if missing necessary data.
	if ( empty( $r['user_id'] ) || empty( $r['activity'] ) ) {
		return new WP_Error(
			'activity_reaction_missing_data',
			sprintf(
				__( 'There was an error %s. Please try again.', 'buddypress' ),
				esc_html( $reaction_object->labels->doing_action )
			)
		);
	}

	// Init the activity.
	$activity = null;

	// Try to use the provided parent activity.
	if ( is_object( $r['activity'] ) ) {
		$activity = $r['activity'];

		// Get the parent activity.
	} else {
		$activity_id = (int) $r['activity'];
		$activity    = new BP_Activity_Activity( $activity_id );
	}

	// Bail if the parent activity does not exist.
	if ( empty( $activity->date_recorded ) ) {
		return new WP_Error(
			'activity_reaction_missing_activity',
			sprintf(
				__( 'The activity you want to %s no longer exists.', 'buddypress' ),
				esc_html( $reaction_object->labels->singular_name )
			)
		);
	}

	if ( ! bp_activity_type_supports( $activity->type, $reaction_object->feature_name ) ) {
		return new WP_Error(
			'activity_reaction_not_supported',
			sprintf(
				__( 'The activity you want to %s does not support this feature.', 'buddypress' ),
				esc_html( $reaction_object->labels->singular_name )
			)
		);
	}

	// Check to see if the parent activity is hidden, and if so, hide this reaction publicly.
	$is_hidden = 0;
	if ( (int) $activity->hide_sitewide === 1 ) {
		$is_hidden = 1;
	}

	// Sanitize User ID.
	$user_id = (int) $r['user_id'];

	// Insert the activity reaction.
	$reaction_id = bp_activity_add(
		array(
			'component'         => $activity->component,
			'type'              => $reaction_object->name,
			'user_id'           => $user_id,
			'item_id'           => $activity->id,
			'primary_link'      => $r['primary_link'],
			'hide_sitewide'     => $is_hidden,
			'error_type'        => 'wp_error',
		)
	);

	// Clear the activity reactions cache.
	wp_cache_delete( $activity->id, 'bp_activity_reactions' );

	// Clear the user reactions cache.
	wp_cache_delete( $user_id, 'bp_activity_user_reactions' );

	return $reaction_id;
}

/**
 * Remove an activity interaction.
 *
 * @since 1.0.2
 *
 * @param integer $reaction_id   The Activity ID of the reaction.
 * @param string  $reaction_type The Activity reaction key name.
 * @return boolean|WP_Error True on success, a WP Error object otherwise.
 */
function bp_activity_remove_interaction( $reaction_id, $reaction_type = 'activity_like' ) {

	// Get the Activity reaction object.
	$reaction_object = bp_get_activity_type_object( $reaction_type );
	if ( is_wp_error( $reaction_object ) || 'reaction' !== $reaction_object->role ) {
		return new WP_Error(
			'activity_reaction_unregistered',
			__( 'This activity reaction is not registered.', 'buddypress' )
		);
	}

	// Bail if missing necessary data.
	if ( empty( $reaction_id ) ) {
		return new WP_Error(
			'activity_reaction_missing_data',
			sprintf(
				__( 'There was an error %s. Please try again.', 'buddypress' ),
				esc_html( $reaction_object->labels->undoing_action )
			)
		);
	}

	// Validate the reaction.
	$reaction = new BP_Activity_Activity( $reaction_id );
	if ( empty( $reaction->id ) || $reaction_type !== $reaction->type ) {
		return new WP_Error(
			'activity_reaction_missing',
			sprintf(
				__( 'This %s no longer exists.', 'buddypress' ),
				esc_html( $reaction_object->labels->singular_name )
			)
		);
	}

	$removed = bp_activity_delete(
		array(
			'id' => $reaction->id,
		)
	);

	if ( $removed ) {
		// Clear the activity reactions cache.
		wp_cache_delete( $reaction->item_id, 'bp_activity_reactions' );

		// Clear the user reactions cache.
		wp_cache_delete( $reaction->user_id, 'bp_activity_user_reactions' );

	} else {
		return new WP_Error(
			'activity_removing_reaction_failed',
			sprintf(
				__( '%s the activity failed. Please try again.', 'buddypress' ),
				esc_html( strtoupper( $reaction_object->labels->undoing_action ) )
			)
		);
	}

	return true;
}

/**
 * Gets the activity IDs a user interacted with.
 *
 * @since 14.0.0
 *
 * @param integer $user_id       Required. The user ID.
 *                               Defaults to the current user ID.
 * @param string  $reaction_type Required. The activity type key name of the reaction.
 *                               Defaults to `activity_like`.
 * @return WP_Error|array The activity IDs a user reacted to.
 */
function bp_activity_get_user_interactions( $user_id = 0, $reaction_type = 'activity_like' ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( ! $reaction_type ) {
		return array();
	}

	// Get the Activity reaction object.
	$reaction_object = bp_get_activity_type_object( $reaction_type );
	if ( is_wp_error( $reaction_object ) || 'reaction' !== $reaction_object->role ) {
		return new WP_Error(
			'activity_reaction_unregistered',
			__( 'The provided reaction type is not registered as such.', 'buddypress' )
		);
	}

	return BP_Activity_Activity::get_user_reactions( $user_id, $reaction_type );
}

function bp_activity_register_activity_types() {
	$bp = buddypress();

	bp_register_activity_type(
		'activity_update',
		array(
			'components'      => array( 'activity', 'groups' ),
			'role'            => 'content',
			'description'     => __( 'Activity updates let members publicly share messages, opinions, ideas: well any text or rich content with other members.', 'buddypress' ),
			'labels'          => array(
				'front_filter' => __( 'Updates', 'buddypress' ),
				'admin_filter' => __( 'Posted a status update', 'buddypress' ),
			),
			'format_callback' => 'bp_activity_format_activity_action_activity_update',
			'streams'         => array( 'activity', 'member', 'member_groups', 'group' ),
			'supports'        => array( 'comments', 'likes' ),
		)
	);

	bp_register_activity_type(
		'activity_comment',
		array(
			'components'      => array( 'activity', 'groups' ),
			'feature_name'    => 'comments',
			'role'            => 'reaction',
			'description'     => __( 'Activity comments let members reply to an activity update or another activity comment', 'buddypress' ),
			'labels'          => array(
				'front_filter' => __( 'Activity Comments', 'buddypress' ),
				'admin_filter' => __( 'Replied to a status update', 'buddypress' ),
			),
			'format_callback' => 'bp_activity_format_activity_action_activity_comment',
			'streams'         => array( 'activity', 'member', 'member_groups', 'group' ),
			'supports'        => array( 'comments' ),
		)
	);

	bp_register_activity_type(
		'activity_like',
		array(
			'components'      => array( 'activity', 'groups' ),
			'role'            => 'reaction',
			'feature_name'    => 'likes',
			'description'     => __( 'Activity likes let members like activity updates or comments', 'buddypress' ),
			'labels'          => array(
				'singular_name'  => __( 'like', 'buddypress' ),
				'plural_name'    => __( 'likes', 'buddypress' ),
				'front_filter'   => __( 'Activity Likes', 'buddypress' ),
				'admin_filter'   => __( 'Liked a status update', 'buddypress' ),
				'do_action'      => __( 'like', 'buddypress' ),
				'doing_action'   => __( 'liking', 'buddypress' ),
				'did_action'     => __( 'liked', 'buddypress' ),
				'undo_action'    => __( 'dislike', 'buddypress' ),
				'undoing_action' => __( 'disliking', 'buddypress' ),
				'undid_action'   => __( 'disliked', 'buddypress' ),
			),
			'format_callback' => 'bp_activity_format_activity_action_activity_like',
			'streams'         => array( 'activity', 'member', 'member_groups', 'group' ),
		)
	);
}
add_action( 'bp_register_activity_actions', __NAMESPACE__ . '\bp_activity_register_activity_types' );

/**
 * Generate an activity action string for an activity item.
 *
 * @since 2.0.0
 *
 * @param BP_Activity_Activity $activity Activity data object.
 * @return string|bool Returns false if no callback is found, otherwise returns
 *                     the formatted action string.
 */
function bp_activity_generate_action_string( $activity ) {
	// Check for valid input.
	if ( empty( $activity->component ) || empty( $activity->type ) ) {
		return false;
	}

	// Init format callback.
	$format_callback = '';

	// Validate the activity type.
	$type = bp_get_activity_type_object( $activity->type );
	if ( is_wp_error( $type ) || ! $type->format_callback ) {
		// Check for registered legacy format callback.
		$actions = bp_activity_get_actions();

		if ( ! empty( $actions->{$activity->component}->{$activity->type}['format_callback'] ) ) {
			$format_callback = $actions->{$activity->component}->{$activity->type}['format_callback'];
		}
	} else {
		$format_callback = $type->format_callback;

		/*
		 * @todo Some type maybe used by other components.
		 * `$format_callback` should be an array keyed by component IDs.
		 */
		if ( 'groups' === $activity->component ) {
			$format_callback = 'bp_groups_format_activity_action_group_activity_update';
		}
	}

	if ( ! $format_callback ) {
		return false;
	}

	// We apply the format_callback as a filter.
	add_filter( 'bp_activity_generate_action_string', $actions->{$activity->component}->{$activity->type}['format_callback'], 10, 2 );
	add_filter( 'bp_activity_generate_action_string', $format_callback, 10, 2 );

	/**
	 * Filters the string for the activity action being returned.
	 *
	 * @since 2.0.0
	 *
	 * @param BP_Activity_Activity $action   Action string being requested.
	 * @param string               $action   Action string being requested.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	$action = apply_filters( 'bp_activity_generate_action_string', $activity->action, $activity );

	// Remove the filter for future activity items.
	remove_filter( 'bp_activity_generate_action_string', $actions->{$activity->component}->{$activity->type}['format_callback'], 10 );
	remove_filter( 'bp_activity_generate_action_string', $format_callback, 10 );

	return $action;
}

/**
 * Format 'activity_update' activity actions.
 *
 * @since 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string $action
 * @return string
 */
function bp_activity_format_activity_action_activity_update( $action, $activity ) {
	$action = sprintf(
		/* translators: %s: the activity author user link */
		esc_html__( '%s posted an update', 'buddypress' ),
		bp_core_get_userlink( $activity->user_id )
	);
	/**
	 * Filters the formatted activity action update string.
	 *
	 * @since 1.2.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_new_update_action', $action, $activity );
}

/**
 * Format 'activity_comment' activity actions.
 *
 * @since 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_activity_format_activity_action_activity_comment( $action, $activity ) {
	$action = sprintf(
		/* translators: %s: the activity author user link */
		esc_html__( '%s posted a new activity comment', 'buddypress' ),
		bp_core_get_userlink( $activity->user_id )
	);

	/**
	 * Filters the formatted activity action comment string.
	 *
	 * @since 1.2.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_comment_action', $action, $activity );
}

/**
 * Format 'activity_like' activity actions.
 *
 * @since 14.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_activity_format_activity_action_activity_like( $action, $activity ) {
	$action = sprintf(
		/* translators: %s: the activity author user link */
		esc_html__( '%s liked an activity', 'buddypress' ),
		bp_core_get_userlink( $activity->user_id )
	);

	/**
	 * Filters the "like" formatted activity action string.
	 *
	 * @since 14.0.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_like_action', $action, $activity );
}

/**
 * Add an activity item.
 *
 * @since 1.1.0
 * @since 2.6.0 Added 'error_type' parameter to $args.
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an activity ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type string   $action            Optional. The activity action/description, typically
 *                                       something like "Joe posted an update". Values passed to this param
 *                                       will be stored in the database and used as a fallback for when the
 *                                       activity item's format_callback cannot be found (eg, when the
 *                                       component is disabled). As long as you have registered a
 *                                       format_callback for your $type, it is unnecessary to include this
 *                                       argument - BP will generate it automatically.
 *                                       See {@link bp_activity_set_action()}.
 *     @type string   $content           Optional. The content of the activity item.
 *     @type string   $component         The unique name of the component associated with
 *                                       the activity item - 'groups', 'profile', etc.
 *     @type string   $type              The specific activity type, used for directory
 *                                       filtering. 'new_blog_post', 'activity_update', etc.
 *     @type string   $primary_link      Optional. The URL for this item, as used in
 *                                       RSS feeds. Defaults to the URL for this activity
 *                                       item's permalink page.
 *     @type int|bool $user_id           Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 *     @type int      $item_id           Optional. The ID of the associated item.
 *     @type int      $secondary_item_id Optional. The ID of a secondary associated item.
 *     @type string   $date_recorded     Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 *     @type bool     $hide_sitewide     Should the item be hidden on sitewide streams?
 *                                       Default: false.
 *     @type bool     $is_spam           Should the item be marked as spam? Default: false.
 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the activity on success. False on error.
 */
function bp_activity_add( $args = '' ) {
	$r = bp_parse_args(
		$args,
		array(
			'id'                => false,                  // Pass an existing activity ID to update an existing entry.
			'action'            => '',                     // The activity action - e.g. "Jon Doe posted an update".
			'content'           => '',                     // Optional: The content of the activity item e.g. "BuddyPress is awesome guys!".
			'component'         => false,                  // The name/ID of the component e.g. groups, profile, mycomponent.
			'type'              => false,                  // The activity type e.g. activity_update, profile_updated.
			'primary_link'      => '',                     // Optional: The primary URL for this item in RSS feeds (defaults to activity permalink).
			'user_id'           => bp_loggedin_user_id(),  // Optional: The user to record the activity for, can be false if this activity is not for a user.
			'item_id'           => false,                  // Optional: The ID of the specific item being recorded, e.g. a blog_id.
			'secondary_item_id' => false,                  // Optional: A second ID used to further filter e.g. a comment_id.
			'recorded_time'     => bp_core_current_time(), // The GMT time that this activity was recorded.
			'hide_sitewide'     => false,                  // Should this be hidden on the sitewide activity stream?
			'is_spam'           => false,                  // Is this activity item to be marked as spam?
			'error_type'        => 'bool',
		),
		'activity_add'
	);

	// Make sure we are backwards compatible.
	if ( empty( $r['component'] ) && ! empty( $r['component_name'] ) ) {
		$r['component'] = $r['component_name'];
	}

	if ( empty( $r['type'] ) && ! empty( $r['component_action'] ) ) {
		$r['type'] = $r['component_action'];
	}

	// Setup activity to be added.
	$activity                    = new BP_Activity( $r['id'] );
	$activity->user_id           = $r['user_id'];
	$activity->component         = $r['component'];
	$activity->type              = $r['type'];
	$activity->primary_link      = $r['primary_link'];
	$activity->content           = $r['content'];
	$activity->item_id           = $r['item_id'];
	$activity->secondary_item_id = $r['secondary_item_id'];
	$activity->date_recorded     = $r['recorded_time'];
	$activity->hide_sitewide     = $r['hide_sitewide'];
	$activity->is_spam           = $r['is_spam'];
	$activity->error_type        = $r['error_type'];

	// Sets the activity action.
	$activity->action = '';

	if ( ! empty( $r['action'] ) ) {
		$activity->action = $r['action'];
	} else {
		$activity->action = bp_activity_generate_action_string( $activity );
	}

	// Setting the `mptt_left` property to 2 makes it possible to run the same query to get all reactions (including comments).
	if ( 'activity_comment' !== $activity->type && in_array( $activity->type, bp_get_activity_types_for_role( 'reaction' ), true ) ) {
		$activity->mptt_left  = 2;
	}

	$save = $activity->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	if ( ! empty( $activity->item_id ) ) {
		if ( 'activity_comment' === $activity->type ) {
			// If this is an activity comment, clear the comment cache for the parent activity ID.
			wp_cache_delete( $activity->item_id, 'bp_activity_comments' );

			// Also, rebuild the tree.
			BP_Activity_Activity::rebuild_activity_comment_tree( $activity->item_id );

		} elseif ( in_array( $activity->type, bp_get_activity_types_for_role( 'reaction' ), true ) ) {
			wp_cache_delete( $activity->item_id, 'bp_activity_reactions' );
		}
	}

	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	/**
	 * Fires at the end of the execution of adding a new activity item, before returning the new activity item ID.
	 *
	 * @since 1.1.0
	 * @since 4.0.0 Added the `$activity_id` parameter.
	 *
	 * @param array $r           Array of parsed arguments for the activity item being added.
	 * @param int   $activity_id The id of the activity item being added.
	 */
	do_action( 'bp_activity_add', $r, $activity->id );

	return $activity->id;
}

<?php
/**
 * BP Activity Block Editor Emojis REST Controller.
 *
 * @package \bp-activity\classes\class-bp-activity-component
 *
 * @since 1.0.0
 */

namespace BP\Activity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_Activity extends \BP_Activity_Activity {
	/**
	 * Save the activity item to the database.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {
		global $wpdb;

		$this->id                = apply_filters_ref_array( 'bp_activity_id_before_save',                array( $this->id,                &$this ) );
		$this->item_id           = apply_filters_ref_array( 'bp_activity_item_id_before_save',           array( $this->item_id,           &$this ) );
		$this->secondary_item_id = apply_filters_ref_array( 'bp_activity_secondary_item_id_before_save', array( $this->secondary_item_id, &$this ) );
		$this->user_id           = apply_filters_ref_array( 'bp_activity_user_id_before_save',           array( $this->user_id,           &$this ) );
		$this->primary_link      = apply_filters_ref_array( 'bp_activity_primary_link_before_save',      array( $this->primary_link,      &$this ) );
		$this->component         = apply_filters_ref_array( 'bp_activity_component_before_save',         array( $this->component,         &$this ) );
		$this->type              = apply_filters_ref_array( 'bp_activity_type_before_save',              array( $this->type,              &$this ) );
		$this->action            = apply_filters_ref_array( 'bp_activity_action_before_save',            array( $this->action,            &$this ) );
		$this->content           = apply_filters_ref_array( 'bp_activity_content_before_save',           array( $this->content,           &$this ) );
		$this->date_recorded     = apply_filters_ref_array( 'bp_activity_date_recorded_before_save',     array( $this->date_recorded,     &$this ) );
		$this->hide_sitewide     = apply_filters_ref_array( 'bp_activity_hide_sitewide_before_save',     array( $this->hide_sitewide,     &$this ) );
		$this->mptt_left         = apply_filters_ref_array( 'bp_activity_mptt_left_before_save',         array( $this->mptt_left,         &$this ) );
		$this->mptt_right        = apply_filters_ref_array( 'bp_activity_mptt_right_before_save',        array( $this->mptt_right,        &$this ) );
		$this->is_spam           = apply_filters_ref_array( 'bp_activity_is_spam_before_save',           array( $this->is_spam,           &$this ) );

		/**
		 * Fires before the current activity item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Activity_Activity $activity Current instance of the activity item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_activity_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->component ) || empty( $this->type ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->component ) ) {
					$this->errors->add( 'bp_activity_missing_component', __( 'You need to define a component parameter to insert activity.', 'buddypress' ) );
				} else {
					$this->errors->add( 'bp_activity_missing_type', __( 'You need to define a type parameter to insert activity.', 'buddypress' ) );
				}
				return $this->errors;
			}
		}

		/**
		 * Use this filter to make the content of your activity required.
		 *
		 * @since 6.0.0
		 *
		 * @param bool   $value True if the content of the activity type is required.
		 *                      False otherwise.
		 * @param string $type  The type of the activity we are about to insert.
		 */
		$type_requires_content = (bool) apply_filters( 'bp_activity_type_requires_content', $this->type === 'activity_update', $this->type );
		if ( $type_requires_content && ! $this->content ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				$this->errors->add( 'bp_activity_missing_content', __( 'Please enter some content to post.', 'buddypress' ) );
				return $this->errors;
			}
		}

		if ( empty( $this->primary_link ) ) {
			$this->primary_link = bp_loggedin_user_url();
		}

		$data = array(
			'user_id'           => $this->user_id,
			'component'         => $this->component,
			'type'              => $this->type,
			'action'            => $this->action,
			'content'           => $this->content,
			'primary_link'      => $this->primary_link,
			'date_recorded'     => $this->date_recorded,
			'item_id'           => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'hide_sitewide'     => $this->hide_sitewide,
			'mptt_left'         => is_null( $this->mptt_left ) ? 0 : $this->mptt_left,
			'mptt_right'        => is_null( $this->mptt_right ) ? 0 : $this->mptt_right,
			'is_spam'           => $this->is_spam,
		);

		$data_format    = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d' );
		$activity_table = buddypress()->activity->table_name;

		// If we have an existing ID, update the activity item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->update(
				$activity_table,
				$data,
				array(
					'id' => $this->id,
				),
				$data_format,
				array( '%d' )
			);
		} else {
			$q = $wpdb->insert(
				$activity_table,
				$data,
				$data_format
			);
		}

		if ( false === $q ) {
			return false;
		}

		// If this is a new activity item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
			// If an existing activity item, prevent any changes to the content generating new @mention notifications.
		} else {
			add_filter( 'bp_activity_at_name_do_notifications', '__return_false' );
		}
		/**
		 * Fires after an activity item has been saved to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Activity_Activity $activity Current instance of activity item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_activity_after_save', array( &$this ) );
		return true;
	}

	/** Static Methods ***************************************************/
	/**
	 * Get activity items, as specified by parameters.
	 *
	 * @since 1.2.0
	 * @since 2.4.0 Introduced the `$fields` parameter.
	 * @since 2.9.0 Introduced the `$order_by` parameter.
	 * @since 10.0.0 Introduced the `$count_total_only` parameter.
	 * @since 11.0.0 Introduced the `$user_id__in` and `$user_id__not_in` parameters.
	 *
	 * @see BP_Activity_Activity::get_filter_sql() for a description of the
	 *      'filter' parameter.
	 * @see WP_Meta_Query::queries for a description of the 'meta_query'
	 *      parameter format.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 25.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Activity fields to return. Pass 'ids' to get only the activity IDs.
	 *                                           'all' returns full activity objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of activity IDs to exclude. Default: false.
	 *     @type array        $in                Array of ids to limit query by (IN). Default: false.
	 *     @type array        $meta_query        Array of meta_query conditions. See WP_Meta_Query::queries.
	 *     @type array        $date_query        Array of date_query conditions. See first parameter of
	 *                                           WP_Date_Query::__construct().
	 *     @type array        $filter_query      Array of advanced query conditions. See BP_Activity_Query::__construct().
	 *     @type string|array $scope             Pre-determined set of activity arguments.
	 *     @type array        $filter            See BP_Activity_Activity::get_filter_sql().
	 *     @type array        $user_id__in       An array of user ids to include. Activity posted by users matching one of these
	 *                                           user ids will be included in results. Default empty array.
	 *     @type array        $user_id__not_in   An array of user ids to exclude. Activity posted by users matching one of these
	 *                                           user ids will not be included in results. Default empty array.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type bool         $display_comments  Whether to include activity comments. Default: false.
	 *     @type bool         $show_hidden       Whether to show items marked hide_sitewide. Default: false.
	 *     @type string       $spam              Spam status. Default: 'ham_only'.
	 *     @type bool         $update_meta_cache Whether to pre-fetch metadata for queried activity items. Default: true.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total activity items
	 *                                           for the query. Default: false.
	 *     @type bool         $count_total_only  If true, only the DB query to count the total activity items is run.
	 *                                           Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located activities
	 *               - 'activities' is an array of the located activities
	 */
	public static function get( $args = array() ) {
		global $wpdb;
		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
			_deprecated_argument(
				__METHOD__,
				'1.6',
				sprintf(
					/* translators: 1: the name of the method. 2: the name of the file. */
					esc_html__( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ),
					__METHOD__,
					__FILE__
				)
			);
			$old_args_keys = array(
				0 => 'max',
				1 => 'page',
				2 => 'per_page',
				3 => 'sort',
				4 => 'search_terms',
				5 => 'filter',
				6 => 'display_comments',
				7 => 'show_hidden',
				8 => 'exclude',
				9 => 'in',
				10 => 'spam'
			);
			$args = bp_core_parse_args_array( $old_args_keys, $function_args );
		}

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'              => 1,               // The current page.
				'per_page'          => 25,              // Activity items per page.
				'max'               => false,           // Max number of items to return.
				'fields'            => 'all',           // Fields to include.
				'sort'              => 'DESC',          // ASC or DESC.
				'order_by'          => 'date_recorded', // Column to order by.
				'exclude'           => false,           // Array of ids to exclude.
				'in'                => false,           // Array of ids to limit query by (IN).
				'meta_query'        => false,           // Filter by activitymeta.
				'date_query'        => false,           // Filter by date.
				'filter_query'      => false,           // Advanced filtering - see BP_Activity_Query.
				'user_id__in'       => array(),         // Array of user ids to include.
				'user_id__not_in'   => array(),         // Array of user ids to excluce.
				'filter'            => false,           // See self::get_filter_sql().
				'scope'             => false,           // Preset activity arguments.
				'search_terms'      => false,           // Terms to search by.
				'display_comments'  => false,           // Whether to include activity comments.
				'show_hidden'       => false,           // Show items marked hide_sitewide.
				'spam'              => 'ham_only',      // Spam status.
				'update_meta_cache' => true,            // Whether or not to update meta cache.
				'count_total'       => false,           // Whether or not to use count_total.
				'count_total_only'  => false,           // Whether to only get the total count.
			)
		);

		// Select conditions.
		$select_sql = "SELECT DISTINCT a.id";
		$from_sql   = " FROM {$bp->activity->table_name} a";
		$join_sql   = '';

		// Where conditions.
		$where_conditions = array();

		// Excluded types.
		$excluded_types = array();

		// Scope takes precedence.
		if ( ! empty( $r['scope'] ) ) {
			$scope_query = self::get_scope_query_sql( $r['scope'], $r );

			// Add our SQL conditions if matches were found.
			if ( ! empty( $scope_query['sql'] ) ) {
				$where_conditions['scope_query_sql'] = $scope_query['sql'];
			}

			// Override some arguments if needed.
			if ( ! empty( $scope_query['override'] ) ) {
				$r = array_replace_recursive( $r, $scope_query['override'] );
			}

			// Advanced filtering.
		} elseif ( ! empty( $r['filter_query'] ) ) {
			$filter_query = new BP_Activity_Query( $r['filter_query'] );
			$sql          = $filter_query->get_sql();

			if ( ! empty( $sql ) ) {
				$where_conditions['filter_query_sql'] = $sql;
			}
		}

		// Regular filtering.
		if ( $r['filter'] && $filter_sql = BP_Activity_Activity::get_filter_sql( $r['filter'] ) ) {
			$where_conditions['filter_sql'] = $filter_sql;
		}

		// User IDs filtering.
		$user_ids_clause  = array();
		$user_ids_filters = array_filter(
			array_intersect_key(
				$r,
				array(
					'user_id__in'     => true,
					'user_id__not_in' => true,
				)
			)
		);

		foreach ( $user_ids_filters as $user_ids_filter_key => $user_ids_filter ) {
			$user_ids_operator = 'IN';
			if ( 'user_id__not_in' === $user_ids_filter_key ) {
				$user_ids_operator = 'NOT IN';
			}

			if ( $user_ids_clause ) {
				$user_ids_clause[] = array(
					'column'  => 'user_id',
					'compare' => $user_ids_operator,
					'value'   => (array) $user_ids_filter,
				);

			} else {
				$user_ids_clause = array(
					'relation' => 'AND',
					array(
						'column'  => 'user_id',
						'compare' => $user_ids_operator,
						'value'   => (array) $user_ids_filter,
					),
				);
			}
		}

		if ( $user_ids_clause ) {
			$user_ids_query = new BP_Activity_Query( $user_ids_clause );
			$user_ids_sql   = $user_ids_query->get_sql();

			if ( ! empty( $user_ids_sql ) ) {
				$where_conditions['user_ids_query_sql'] = $user_ids_sql;
			}
		}

		// Spam.
		if ( 'ham_only' == $r['spam'] ) {
			$where_conditions['spam_sql'] = 'a.is_spam = 0';
		} elseif ( 'spam_only' == $r['spam'] ) {
			$where_conditions['spam_sql'] = 'a.is_spam = 1';
		}

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'a.content LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @since 3.0.0
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 */
			if ( apply_filters( 'bp_activity_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR a.user_id = %d', $user_id );
				}
			}
		}

		// Sanitize 'order'.
		$sort = $r['sort'];
		if ( 'DESC' !== $sort ) {
			$sort = bp_esc_sql_order( $sort );
		}

		switch( $r['order_by'] ) {
			case 'id' :
			case 'user_id' :
			case 'component' :
			case 'type' :
			case 'action' :
			case 'content' :
			case 'primary_link' :
			case 'item_id' :
			case 'secondary_item_id' :
			case 'date_recorded' :
			case 'hide_sitewide' :
			case 'mptt_left' :
			case 'mptt_right' :
			case 'is_spam' :
				break;
			default :
				$r['order_by'] = 'date_recorded';
				break;
		}

		$order_by = 'a.' . $r['order_by'];

		// Hide Hidden Items?
		if ( ! $r['show_hidden'] ) {
			$where_conditions['hidden_sql'] = "a.hide_sitewide = 0";
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "a.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "a.id IN ({$in})";
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );
		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		// Process date_query into SQL.
		$date_query_sql = self::get_date_query_sql( $r['date_query'] );
		if ( ! empty( $date_query_sql ) ) {
			$where_conditions['date'] = $date_query_sql;
		}

		/*
		 * @todo $r['display_comments'] should be deprecated in favor of $r['display_reactions']
		 */
		$display_reactions = $r['display_comments'];

		// Alter the query based on whether we want to show activity item
		// comments in the stream like normal comments or threaded below
		// the activity.
		if ( false === $display_reactions || 'threaded' === $display_reactions ) {
			$excluded_types = bp_get_activity_types_for_role( 'reaction' );
		}

		// Exclude 'last_activity' items unless the 'action' filter has
		// been explicitly set.
		if ( empty( $r['filter']['object'] ) ) {
			$excluded_types[] = 'last_activity';
		}

		// Build the excluded type sql part.
		if ( ! empty( $excluded_types ) ) {
			$not_in = "'" . implode( "', '", esc_sql( $excluded_types ) ) . "'";
			$where_conditions['excluded_types'] = "a.type NOT IN ({$not_in})";
		}

		/**
		 * Filters the MySQL WHERE conditions for the Activity items get method.
		 *
		 * @since 1.9.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_activity_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main activity query.
		 *
		 * @since 2.5.0
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_activity_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page']     );
		$per_page = absint( $r['per_page'] );
		$retval = array(
			'activities'     => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Init the activity list.
		$activities     = array();
		$only_get_count = (bool) $r['count_total_only'];

		/**
		 * Filters if BuddyPress should use legacy query structure over current structure for version 2.0+.
		 *
		 * It is not recommended to use the legacy structure, but allowed to if needed.
		 *
		 * @since 2.0.0
		 *
		 * @param bool                 $value Whether to use legacy structure or not.
		 * @param BP_Activity_Activity $value Current method being called.
		 * @param array                $r     Parsed arguments passed into method.
		 */
		if ( ! $only_get_count && apply_filters( 'bp_use_legacy_activity_query', false, __METHOD__, $r ) ) {
			// Legacy queries joined against the user table.
			$select_sql = "SELECT DISTINCT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name";
			$from_sql   = " FROM {$bp->activity->table_name} a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID";

			if ( ! empty( $page ) && ! empty( $per_page ) ) {
				$pag_sql = $wpdb->prepare( "LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page );
				/** This filter is documented in bp-activity/bp-activity-classes.php */
				$activity_sql = apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}, a.id {$sort} {$pag_sql}", $select_sql, $from_sql, $where_sql, $sort, $pag_sql );
			} else {
				$pag_sql = '';
				/**
				 * Filters the legacy MySQL query statement so plugins can alter before results are fetched.
				 *
				 * @since 1.5.0
				 *
				 * @param string $value      Concatenated MySQL statement pieces to be query results with for legacy query.
				 * @param string $select_sql Final SELECT MySQL statement portion for legacy query.
				 * @param string $from_sql   Final FROM MySQL statement portion for legacy query.
				 * @param string $where_sql  Final WHERE MySQL statement portion for legacy query.
				 * @param string $sort       Final sort direction for legacy query.
				 */
				$activity_sql = apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}, a.id {$sort}", $select_sql, $from_sql, $where_sql, $sort, $pag_sql );
			}

			/*
			 * Queries that include 'last_activity' are cached separately,
			 * since they are generally much less long-lived.
			 */
			if ( preg_match( '/a\.type NOT IN \([^\)]*\'last_activity\'[^\)]*\)/', $activity_sql ) ) {
				$cache_group = 'bp_activity';
			} else {
				$cache_group = 'bp_activity_with_last_activity';
			}

			$activities = $wpdb->get_results( $activity_sql );

			// Integer casting for legacy activity query.
			foreach ( (array) $activities as $i => $ac ) {
				$activities[ $i ]->id                = (int) $ac->id;
				$activities[ $i ]->item_id           = (int) $ac->item_id;
				$activities[ $i ]->secondary_item_id = (int) $ac->secondary_item_id;
				$activities[ $i ]->user_id           = (int) $ac->user_id;
				$activities[ $i ]->hide_sitewide     = (int) $ac->hide_sitewide;
				$activities[ $i ]->mptt_left         = (int) $ac->mptt_left;
				$activities[ $i ]->mptt_right        = (int) $ac->mptt_right;
				$activities[ $i ]->is_spam           = (int) $ac->is_spam;
			}

		} elseif ( ! $only_get_count ) {
			// Query first for activity IDs.
			$activity_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, a.id {$sort}";

			if ( ! empty( $per_page ) && ! empty( $page ) ) {
				// We query for $per_page + 1 items in order to
				// populate the has_more_items flag.
				$activity_ids_sql .= $wpdb->prepare( " LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
			}

			/**
			 * Filters the paged activities MySQL statement.
			 *
			 * @since 2.0.0
			 *
			 * @param string $activity_ids_sql MySQL statement used to query for Activity IDs.
			 * @param array  $r                Array of arguments passed into method.
			 */
			$activity_ids_sql = apply_filters( 'bp_activity_paged_activities_sql', $activity_ids_sql, $r );

			/*
			 * Queries that include 'last_activity' are cached separately,
			 * since they are generally much less long-lived.
			 */
			if ( preg_match( '/a\.type NOT IN \([^\)]*\'last_activity\'[^\)]*\)/', $activity_ids_sql ) ) {
				$cache_group = 'bp_activity';
			} else {
				$cache_group = 'bp_activity_with_last_activity';
			}

			$cached = bp_core_get_incremented_cache( $activity_ids_sql, $cache_group );
			if ( false === $cached ) {
				$activity_ids = $wpdb->get_col( $activity_ids_sql );
				bp_core_set_incremented_cache( $activity_ids_sql, $cache_group, $activity_ids );
			} else {
				$activity_ids = $cached;
			}

			$retval['has_more_items'] = ! empty( $per_page ) && count( $activity_ids ) > $per_page;

			// If we've fetched more than the $per_page value, we
			// can discard the extra now.
			if ( ! empty( $per_page ) && count( $activity_ids ) === $per_page + 1 ) {
				array_pop( $activity_ids );
			}

			if ( 'ids' === $r['fields'] ) {
				$activities = array_map( 'intval', $activity_ids );
			} else {
				$activities = self::get_activity_data( $activity_ids );
			}
		}

		if ( $activities && 'ids' !== $r['fields'] ) {
			// Get the fullnames of users so we don't have to query in the loop.
			$activities = self::append_user_fullnames( $activities );

			// Get activity meta.
			$activity_ids = array();

			foreach ( (array) $activities as $activity ) {
				$activity_ids[] = $activity->id;
			}

			if ( ! empty( $activity_ids ) && $r['update_meta_cache'] ) {
				bp_activity_update_meta_cache( $activity_ids );
			}

			if ( $activities && $display_reactions ) {
				/*
				 * This should append all possible reactions.
				 */
				$activities = BP_Activity_Activity::append_reactions( $activities, $r['spam'] );
			}

			// Pre-fetch data associated with activity users and other objects.
			BP_Activity_Activity::prefetch_object_data( $activities );

			// Generate action strings.
			$activities = BP_Activity_Activity::generate_action_strings( $activities );
		}

		$retval['activities'] = $activities;

		// Only query the count total if requested.
		if ( ! empty( $r['count_total'] ) || $only_get_count ) {
			/**
			 * Filters the total activities MySQL statement.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value     MySQL statement used to query for total activities.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_activities_sql = apply_filters( 'bp_activity_total_activities_sql', "SELECT count(DISTINCT a.id) FROM {$bp->activity->table_name} a {$join_sql} {$where_sql}", $where_sql, $sort );

			/*
			 * Queries that include 'last_activity' are cached separately,
			 * since they are generally much less long-lived.
			 */
			if ( preg_match( '/a\.type NOT IN \([^\)]*\'last_activity\'[^\)]*\)/', $total_activities_sql ) ) {
				$cache_group = 'bp_activity';
			} else {
				$cache_group = 'bp_activity_with_last_activity';
			}

			$cached = bp_core_get_incremented_cache( $total_activities_sql, $cache_group );
			if ( false === $cached ) {
				$total_activities = $wpdb->get_var( $total_activities_sql );
				bp_core_set_incremented_cache( $total_activities_sql, $cache_group, $total_activities );
			} else {
				$total_activities = $cached;
			}

			// If $max is set, only return up to the max results.
			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_activities > (int) $r['max'] ) {
					$total_activities = $r['max'];
				}
			}

			$retval['total'] = $total_activities;
		}

		return $retval;
	}

	/**
	 * Build an associative array of comments and calculate comments depth.
	 *
	 * @since 14.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 * @param array $descendants The list of activity comments. Required.
	 * @return array The list of activity comments.
	 */
	public static function build_assoc_array_and_calcultate_depth( $descendants ) {
		$comments = array();
		$ref      = array();

		// Loop descendants and build an assoc array.
		foreach ( (array) $descendants as $d ) {
			$d->children = array();

			// If we have a reference on the parent.
			if ( isset( $ref[ $d->secondary_item_id ] ) ) {
				$ref[ $d->secondary_item_id ]->children[ $d->id ] = $d;
				$ref[ $d->id ] =& $ref[ $d->secondary_item_id ]->children[ $d->id ];

				// If we don't have a reference on the parent, put in the root level.
			} else {
				$comments[ $d->id ] = $d;
				$ref[ $d->id ] =& $comments[ $d->id ];
			}
		}

		// Calculate depth for each item.
		foreach ( $ref as &$r ) {
			$depth = 1;
			$parent_id = $r->secondary_item_id;

			while ( $parent_id !== $r->item_id ) {
				$depth++;

				// When display_comments=stream, the parent comment may not be part of the
				// returned results, so we manually fetch it.
				if ( empty( $ref[ $parent_id ] ) ) {
					$direct_parent = new BP_Activity_Activity( $parent_id );
					if ( isset( $direct_parent->secondary_item_id ) ) {
						// If the direct parent is not an activity update, that means we've reached
						// the parent activity item (eg. new_blog_post).
						if ( 'activity_update' !== $direct_parent->type ) {
							$parent_id = $r->item_id;

						} else {
							$parent_id = $direct_parent->secondary_item_id;
						}

					} else {
						// Something went wrong.  Short-circuit the depth calculation.
						$parent_id = $r->item_id;
					}
				} else {
					$parent_id = $ref[ $parent_id ]->secondary_item_id;
				}
			}
			$r->depth = $depth;
		}

		return $comments;
	}

	/**
	 * Fetch reactions for the activity. Reactions are including comments.
	 *
	 * @since 14.0.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type integer $activity_id         Activity ID to fetch comments for.
	 *     @type integer $mptt_left           Left-most node boundary.
	 *     @type integer $mptt_right          Right-most node boundary.
	 *     @type string  $spam                Optional. 'ham_only' (default), 'spam_only' or 'all'.
	 *     @type integer $top_level_parent_id Optional. The id of the root-level parent activity item.
	 * }
	 * @return array An associative array containing Activity comments and Activity reactions.
	 */
	public static function get_activity_reactions( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'activity_id'         => 0,
				'mptt_left'           => 0,
				'mptt_right'          => 0,
				'spam'                => 'ham_only',
				'top_level_parent_id' => 0,
			)
		);

		$activity_id     = (int) $r['activity_id'];
		$comments_cache  = wp_cache_get( $activity_id, 'bp_activity_comments' );
		$reactions_cache = wp_cache_get( $activity_id, 'bp_activity_reactions' );
		$retval          =  array(
			'comments'  => array(),
			'reactions' => array(),
		);

		// We store the string 'none' to cache the fact that the
		// activity item has no comments.
		if ( 'none' === $comments_cache && 'none' === $reactions_cache ) {
			return $retval;
		}

		if ( empty( $GLOBALS['wpdb'] ) ) {
			return array();
		}

		$wpdb           = $GLOBALS['wpdb'];
		$reaction_types = bp_get_activity_types_for_role( 'reaction' );

		if ( ! empty( $comments_cache ) && 'none' !== $comments_cache ) {
			$retval['comments'] = (array) $comments_cache;

			if ( 'none' === $reactions_cache ) {
				$retval['reactions'] = array();
				$reaction_types      = array();
			} else {
				$reaction_types = array_diff( $reaction_types, array( 'activity_comment' ) );
			}
		}

		if ( ! $reaction_types ) {
			return $retval;
		}

		if ( ! empty( $reactions_cache ) && 'none' !== $reactions_cache ) {
			$retval['reactions'] = (array) $reactions_cache;

			if ( 'none' === $comments_cache ) {
				$retval['comments'] = array();
				$reaction_types     = array();
			} else {
				$reaction_types = array( 'activity_comment' );
			}
		}

		if ( ! $reaction_types ) {
			return $retval;
		}

		if ( empty( $r['top_level_parent_id'] ) ) {
			$r['top_level_parent_id'] = $activity_id;
		}

		// Don't retrieve activity comments marked as spam.
		if ( 'ham_only' == $r['spam'] ) {
			$spam_sql = 'AND a.is_spam = 0';
		} elseif ( 'spam_only' == $r['spam'] ) {
			$spam_sql = 'AND a.is_spam = 1';
		} else {
			$spam_sql = '';
		}

		/*
		 * This manipulation is necessary to be sure to get reactions
		 * whether the activity was commented or not.
		 */
		if ( 3 > $r['mptt_right'] ) {
			$r['mptt_right'] = 3;
		}

		// Only include reaction types.
		$in  = "'" . implode( "', '", esc_sql( $reaction_types ) ) . "'";
		$bp  = buddypress();
		$sql = $wpdb->prepare(
			"SELECT id FROM {$bp->activity->table_name} a WHERE a.type IN ({$in}) {$spam_sql} AND a.item_id = %d AND a.mptt_left > %d AND a.mptt_left < %d ORDER BY a.date_recorded ASC",
			$r['top_level_parent_id'],
			$r['mptt_left'],
			$r['mptt_right']
		);

		$reaction_ids = $wpdb->get_col( $sql );
		$reactions    = self::get_activity_data( $reaction_ids );
		$reactions    = self::append_user_fullnames( $reactions );
		$reactions    = self::generate_action_strings( $reactions );

		if ( ! $retval['comments'] ) {
			$comments = wp_list_filter( $reactions, array( 'type' => 'activity_comment' ) );

			if ( ! $comments ) {
				$comments_cache_value = 'none';
			} else {
				$comments             = self::build_assoc_array_and_calcultate_depth( $comments );
				$comments_cache_value = $comments;
			}

			wp_cache_set( $activity_id, $comments_cache_value, 'bp_activity_comments' );

			$retval['comments'] = $comments;
		}

		if ( ! $retval['reactions'] ) {
			$reactions = wp_list_filter( $reactions, array( 'type' => 'activity_comment' ), 'NOT' );

			if ( ! $reactions ) {
				$reactions_cache_value = 'none';
			} else {
				$reactions_cache_value = $reactions;
			}

			wp_cache_set( $activity_id, $reactions_cache_value, 'bp_activity_reactions' );

			$retval['reactions'] = $reactions;
		}

		// Return comments & reactions.
		return $retval;
	}

	/**
	 * Append activity reactions to their associated activity items.
	 *
	 * @since 14.0.0
	 *
	 * @param array  $activities Activities to fetch reactions for.
	 * @param string $spam       Optional. 'ham_only' (default), 'spam_only' or 'all'.
	 * @return array The updated activities with nested reactions.
	 */
	public static function append_reactions( $activities, $spam = 'ham_only' ) {
		$activity_reactions = array();

		// Now fetch the activity reactions and parse them into the correct position in the activities array.
		foreach ( (array) $activities as $activity ) {
			$top_level_parent_id = 0;

			if ( in_array( $activity->type, bp_get_activity_types_for_role( 'reaction' ), true ) ) {
				$top_level_parent_id = $activity->item_id;
			}

			$args = array(
				'activity_id'         => $activity->id,
				'mptt_left'           => $activity->mptt_left,
				'mptt_right'          => $activity->mptt_right,
				'spam'                => $spam,
				'top_level_parent_id' => $top_level_parent_id,
			);

			$function_args = array_values( $args );

			/**
			 * Filters if BuddyPress should use the legacy activity query.
			 *
			 * @since 2.0.0
			 *
			 * @param bool                 $value     Whether or not to use the legacy query.
			 * @param BP_Activity_Activity $value     Magic method referring to currently called method.
			 * @param array                $func_args Array of the method's argument list.
			 */
			if ( 'activity_comment' === $activity->type && apply_filters( 'bp_use_legacy_activity_query', false, 'BP_Activity_Activity::get_activity_comments', $function_args ) ) {
				list( $activity_id, $left, $right, $spam, $top_level_parent_id ) = $function_args;
				$activity_reactions[ $activity->id ] = BP_Activity_Activity::get_activity_comments( $activity_id, $left, $right, $spam, $top_level_parent_id );

			} else {
				$activity_reactions[ $activity->id ] = BP_Activity_Activity::get_activity_reactions( $args );
			}
		}

		// Merge the reactions with the activity items.
		foreach ( (array) $activities as $key => $activity ) {
			if ( isset( $activity_reactions[$activity->id] ) ) {
				$activities[ $key ]->children  = $activity_reactions[ $activity->id ]['comments'];
				$activities[ $key ]->reactions = $activity_reactions[ $activity->id ]['reactions'];
			}
		}

		return $activities;
	}

	/**
	 * Returns the activity IDs a user reacted to.
	 *
	 * @since 14.0.0
	 *
	 * @param integer $user_id       Required. The user ID.
	 *                               Defaults to the current user ID.
	 * @param string  $reaction_type Required. The activity type key name of the reaction.
	 *                               Defaults to `activity_like`.
	 * @return array The activity IDs a user reacted to.
	 */
	public static function get_user_reactions( $user_id, $reaction_type ) {

		if ( ! in_array( $reaction_type, bp_get_activity_types_for_role( 'reaction' ), true ) ) {
			return array();
		}

		// Get the user's reactions cache.
		$user_reactions_cache = wp_cache_get( $user_id, 'bp_activity_user_reactions' );

		if ( 'none' === $user_reactions_cache ) {
			return array();
		} elseif ( ! empty( $user_reactions_cache ) ) {
			return (array) $user_reactions_cache;
		}

		if ( empty( $GLOBALS['wpdb'] ) ) {
			return array();
		}

		$wpdb           = $GLOBALS['wpdb'];
		$activity_table = buddypress()->activity->table_name;
		$sql            = $wpdb->prepare( "SELECT item_id FROM {$activity_table} WHERE user_id = %d AND type = %s", $user_id, $reaction_type );
		$activities     = $wpdb->get_col( $sql );

		$user_reactions_cache_value = $activities;
		if ( ! $activities ) {
			$user_reactions_cache_value = 'none';
		}

		// Set the user's reactions cache.
		wp_cache_set( $user_id, $user_reactions_cache_value, 'bp_activity_user_reactions' );

		return $activities;
	}
}

<?php
/**
 * BP Activity Block Editor Emojis REST Controller.
 *
 * @package \bp-activity\classes\class-bp-activity-block-editor-emojis-rest-controller
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Activity Block Editor Emojis REST Controller Class.
 *
 * @since 1.0.0
 */
class BP_Activity_Block_Editor_Emojis_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->activity->id . '-emojis';
	}

	/**
	 * Registers the routes for the BP Emojis items of the controller.
	 *
	 * @since 1.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to BP Emojis.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = true;

		/**
		 * Filter the BP Emojis `get_items` permissions check.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_activity_block_editor_emojis_rest_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve BP Emojis.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response List of BP Attachments Media response data.
	 */
	public function get_items( $request ) {
		$search     = $request->get_param( 'search' );
		$context    = $request->get_param( 'context' );
		$page       = $request->get_param( 'page' );
		$per_page   = $request->get_param( 'per_page' );
		$pagination = array(
			'X-BP-Activity-Block-Editor-Emojis-Total'      => 0,
			'X-BP-Activity-Block-Editor-Emojis-TotalPages' => 0,
		);

		$page     = ! $page ? 1 : (int) $page;
		$per_page = ! $per_page ? 1 : (int) $per_page;

		$collection = bp_activity_get_emojis(
			array(
				'search'   => $search,
				'per_page' => $per_page,
				'page'     => $page,
			)
		);

		$emojis       = $collection['emojis'];
		$total_emojis = (int) $collection['total_emojis'];
		$max_pages    = ceil( $total_emojis / $per_page );

		if ( $page > $max_pages && $total_emojis > 0 ) {
			return new WP_Error(
				'bp_rest_activity_block_editor_emojis_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.', 'bp-activity-block-editor' ),
				array( 'status' => 400 )
			);
		}

		// Set pagination.
		$pagination['X-BP-Attachments-Media-Libraries-Total']      = $total_emojis;
		$pagination['X-BP-Attachments-Media-Libraries-TotalPages'] = $max_pages;

		$retval = array();
		foreach ( $emojis as $emoji ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $emoji, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		if ( 2 === count( array_filter( $pagination ) ) ) {
			foreach ( $pagination as $key_pagination => $value_pagination ) {
				$response->header( $key_pagination, $value_pagination );
			}
		}

		return $response;
	}

	/**
	 * Creates a custom BP Emoji.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, WP_Error object on failure.
	 */
	public function create_item( $request ) {
		return new WP_Error(
			'invalid-method',
			/* translators: %s: Method name. */
			sprintf( __( "Method '%s' not implemented yet.", 'bp-activity-block-editor' ), __METHOD__ ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Updates a custom BP Emoji.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, WP_Error object on failure.
	 */
	public function update_item( $request ) {
		return new WP_Error(
			'invalid-method',
			/* translators: %s: Method name. */
			sprintf( __( "Method '%s' not implemented yet.", 'bp-activity-block-editor' ), __METHOD__ ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Deletes a custom BP Emoji.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		return new WP_Error(
			'invalid-method',
			/* translators: %s: Method name. */
			sprintf( __( "Method '%s' not implemented yet.", 'bp-activity-block-editor' ), __METHOD__ ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Prepares BP Emojis data for return as an object.
	 *
	 * @since 1.0.0
	 *
	 * @param object          $emoji  BP Emojis object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $emoji, $request ) {
		$data    = get_object_vars( $emoji );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the query params for the BP Emojis collection.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$bp                            = buddypress();
		$params                        = WP_REST_Controller::get_collection_params();
		$params['context']['default']  = 'view';
		$params['per_page']['default'] = 10;

		/**
		 * Filters the collection query params.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_activity_block_editor_emojis_rest_rest_collection_params', $params );
	}

	/**
	 * Retrieves the BP Emoji's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( ! isset( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp-activity-block-editor-emojis',
				'type'       => 'object',
				// Base properties for every BP Emojis.
				'properties' => array(
					'id'       => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'A unique numeric ID for the emoji.', 'bp-activity-block-editor' ),
						'readonly'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'default'           => 0,
					),
					'emoji_id' => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'A unique alphanumeric ID for the emoji.', 'bp-activity-block-editor' ),
						'readonly'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'default'           => '',
					),
					'name'     => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'The name of the emoji.', 'bp-activity-block-editor' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'default'           => '',
					),
					'char'     => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'The description of the medium.', 'bp-activity-block-editor' ),
						'type'              => 'string',
						'sanitize_callback' => 'rest_sanitize_request_arg',
						'validate_callback' => 'rest_validate_request_arg',
						'default'           => '',
					),
					'src'      => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'URL of the image to use as an emoji.', 'bp-activity-block-editor' ),
						'type'              => 'string',
						'format'            => 'uri',
						'sanitize_callback' => 'rest_sanitize_request_arg',
						'validate_callback' => 'rest_validate_request_arg',
						'default'           => '',
					),
					'category' => array(
						'context'           => array( 'view', 'edit', 'embed' ),
						'description'       => __( 'Name of emojis category', 'bp-activity-block-editor' ),
						'type'              => 'string',
						'sanitize_callback' => 'rest_sanitize_request_arg',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array(
							__( 'activities', 'bp-activity-block-editor' ),
							__( 'animals-nature', 'bp-activity-block-editor' ),
							__( 'component', 'bp-activity-block-editor' ),
							__( 'flags', 'bp-activity-block-editor' ),
							__( 'food-drink', 'bp-activity-block-editor' ),
							__( 'objects', 'bp-activity-block-editor' ),
							__( 'people-body', 'bp-activity-block-editor' ),
							__( 'smileys-emotion', 'bp-activity-block-editor' ),
							__( 'symbols', 'bp-activity-block-editor' ),
							__( 'travel-places', 'bp-activity-block-editor' ),
						),
						'default'           => __( 'symbols', 'bp-activity-block-editor' ),
					),
				),
			);
		}

		return $this->add_additional_fields_schema( $this->schema );
	}
}

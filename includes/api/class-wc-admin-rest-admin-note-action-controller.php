<?php
/**
 * REST API Admin Note Action controller
 *
 * Handles requests to the admin note action endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Admin Note Action controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class WC_Admin_REST_Admin_Note_Action_Controller extends WC_Admin_REST_Admin_Notes_Controller {

	/**
	 * Register the routes for admin notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<note_id>[\d-]+)/action/(?P<action_id>[\d-]+)',
			array(
				'args'   => array(
					'note_id'   => array(
						'description' => __( 'Unique ID for the Note.', 'woocommerce-admin' ),
						'type'        => 'integer',
					),
					'action_id' => array(
						'description' => __( 'Unique ID for the Note Action.', 'woocommerce-admin' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'trigger_note_action' ),
					// @todo - double check these permissions for taking note actions.
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Trigger a note action.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function trigger_note_action( $request ) {
		$note = WC_Admin_Notes::get_note( $request->get_param( 'note_id' ) );

		if ( ! $note ) {
			return new WP_Error(
				'woocommerce_admin_notes_invalid_id',
				__( 'Sorry, there is no resouce with that ID.', 'woocommerce-admin' ),
				array( 'status' => 404 )
			);
		}

		// Find note action by ID.
		$action_id        = $request->get_param( 'action_id' );
		$actions          = $note->get_actions( 'edit' );
		$triggered_action = false;

		foreach ( $actions as $action ) {
			if ( $action->id === $action_id ) {
				$triggered_action = $action;
			}
		}

		if ( ! $triggered_action ) {
			return new WP_Error(
				'woocommerce_admin_note_action_invalid_id',
				__( 'Sorry, there is no resouce with that ID.', 'woocommerce-admin' ),
				array( 'status' => 404 )
			);
		}

		/**
		 * Fires when an admin note action is taken.
		 *
		 * @param string $name   The triggered action name.
		 * @param object $action The triggered action.
		 */
		do_action( 'woocommerce_admin_note_action', $triggered_action->name, $triggered_action );

		/**
		 * Fires when an admin note action is taken.
		 *
		 * For more specific targeting of note actions.
		 */
		do_action( 'woocommerce_admin_note_action_' . $triggered_action->name );

		// Update the note with the status for this action.
		$note->set_status( $triggered_action->status );
		$note->save();

		$data = $note->get_data();
		$data = $this->prepare_item_for_response( $data, $request );
		$data = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $data );
	}
}

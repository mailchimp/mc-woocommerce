<?php
/**
 * REST API WC MailChimp settings
 *
 * Handles requests to the /payment_gateways endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#TODO:
# * permission check


/**
 * @package WooCommerce/API
 */
class WC_REST_MC_Store_Settings_Controller extends WC_REST_Payment_Gateways_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'mailchimp';

	/**
	 * Prepare a payment gateway for response.
	 *
	 * @param  WC_Payment_Gateway $gateway    Payment gateway object.
	 * @param  WP_REST_Request    $request    Request object.
	 * @return WP_REST_Response   $response   Response data.
	 */

	/**
	 * Register the route for /payment_gateways and /payment_gateways/<id>
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_settings_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to view payment gateways.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		// if ( ! wc_rest_check_manager_permissions( 'payment_gateways', 'read' ) ) {
		// 	return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		// }
		return true;
	}

	/**
	 * Get payment gateways.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_settings( $request ) {
		$response = array(
			'name'               => 'Dumb store name',
			'email'              => 'dumb@store.email',
		);
		return rest_ensure_response( $response );
	}

	/**
	 * Get the payment gateway schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'store_settings',
			'type'       => 'object',
			'properties' => array(
				'name' => array(
					'description' => __( 'Store name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email' => array(
					'description' => __( 'Store email.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

}

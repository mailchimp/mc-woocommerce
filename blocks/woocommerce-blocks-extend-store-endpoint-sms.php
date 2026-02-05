<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

/**
 * Class Mailchimp_Woocommerce_SMS_Blocks_Extend_Store_Endpoint
 * 
 * Extends the WooCommerce Store API checkout endpoint with SMS consent fields.
 */
class Mailchimp_Woocommerce_SMS_Blocks_Extend_Store_Endpoint {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendRestApi
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'mailchimp-sms';

	/**
	 * Bootstraps the class and hooks required data.
	 */
	public static function init() {
		self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get( Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the SMS data into the checkout endpoint.
	 */
	public static function extend_store() {
		if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
			self::$extend->register_endpoint_data(
				[
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'schema_callback' => array( 'Mailchimp_Woocommerce_SMS_Blocks_Extend_Store_Endpoint', 'extend_checkout_schema' ),
					'schema_type'     => ARRAY_A,
				]
			);
		}
	}

	/**
	 * Register SMS consent schema into the Checkout endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_checkout_schema() {
		return array(
			'smsOptin' => array(
				'description' => __( 'Subscribe to SMS marketing opt-in.', 'mailchimp-for-woocommerce' ),
				'type'        => array( 'boolean', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => function( $value ) {
						if ( ! is_null( $value ) && ! is_bool( $value ) ) {
							return new WP_Error( 'api-error', 'value of type ' . gettype( $value ) . ' was posted to the SMS optin callback' );
						}
						return true;
					},
					'sanitize_callback' => function ( $value ) {
						if ( is_bool( $value ) ) {
							return $value;
						}
						return false;
					},
				),
			),
			'smsPhone' => array(
				'description' => __( 'SMS phone number for marketing consent.', 'mailchimp-for-woocommerce' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => function( $value ) {
						if ( ! is_null( $value ) && ! is_string( $value ) ) {
							return new WP_Error( 'api-error', 'SMS phone must be a string' );
						}
						// Basic phone validation - allow + and digits
						if ( ! empty( $value ) ) {
							$cleaned = preg_replace( '/[\s\-\(\)]/', '', $value );
							if ( ! preg_match( '/^\+?[1-9]\d{6,14}$/', $cleaned ) ) {
								return new WP_Error( 'api-error', 'Invalid phone number format' );
							}
						}
						return true;
					},
					'sanitize_callback' => function ( $value ) {
						if ( is_string( $value ) ) {
							// Sanitize phone - keep only + and digits
							return preg_replace( '/[^\+\d]/', '', $value );
						}
						return '';
					},
				),
			),
		);
	}
}

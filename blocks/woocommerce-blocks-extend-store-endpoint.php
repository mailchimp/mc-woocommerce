<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

class Mailchimp_Woocommerce_Newsletter_Blocks_Extend_Store_Endpoint {
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
	const IDENTIFIER = 'mailchimp-newsletter';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 */
	public static function init() {
		self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get( Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {

		if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
			self::$extend->register_endpoint_data(
				[
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'schema_callback' => array( 'Mailchimp_Woocommerce_Newsletter_Blocks_Extend_Store_Endpoint', 'extend_checkout_schema' ),
					'schema_type'     => ARRAY_A,
				]
			);
		}
	}

	/**
	 * Register shipping workshop schema into the Checkout endpoint.
	 *
	 * @return array Registered schema.
	 *
	 */
	public static function extend_checkout_schema() {

		return array(
			'optin' => array(
				'description' => __( 'Subscribe to marketing opt-in.', 'mailchimp-newsletter' ),
				'type'        => array( 'boolean', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => function( $value ) {
						if ( ! is_null( $value ) && ! is_bool( $value ) ) {
							return new WP_Error( 'api-error', 'value of type ' . gettype( $value ) . ' was posted to the newsletter optin callback' );
						}
						return true;
					},
					'sanitize_callback' => function ( $value ) {
						if ( is_bool( $value ) ) {
							return $value;
						}

						// Return a boolean when "null" is passed,
						// which is the only non-boolean value allowed.
						return false;
					},
				),
			),
			'gdprFields' => array(
				'description' => __( 'GDPR marketing opt-in.', 'mailchimp-newsletter' ),
				'type'        => 'object',
				'context'     => array(),
				'arg_options' => array(
					'validate_callback' => function( $value ) {
						return true;
					},
				),
			),
            'smsPhone' => array(
                'description' => __( 'SMS phone number for marketing consent.', 'mailchimp-for-woocommerce' ),
                'type'        => array( 'string', 'null' ),
                'context'     => array( 'view', 'edit' ),
                'arg_options' => array(
                    'validate_callback' => function( $value ) {
                        mailchimp_log('blocks', 'validate_callback for smsPhone in newsletter', ['value' => $value]);
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
                        mailchimp_log('blocks', 'sanitize_callback for smsPhone in newsletter', ['value' => $value]);
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
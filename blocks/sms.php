<?php

/**
 * SMS Consent Block registration for WooCommerce Blocks checkout
 */

add_action( 'woocommerce_blocks_loaded', function() {

    if (!function_exists('mailchimp_is_configured') || !mailchimp_is_configured()) {
        return;
    }

    // Check if SMS is enabled
    $options = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce');
    if (empty($options['mailchimp_sms_enabled'])) {
        return;
    }

	if (class_exists( '\Automattic\WooCommerce\Blocks\Package' ) &&
        class_exists('\Automattic\WooCommerce\StoreApi\StoreApi') &&
	    interface_exists('\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface')) {

        // Hook into order processing to capture SMS consent
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( 'Mailchimp_Woocommerce_SMS_Blocks_Integration', 'order_processed' ), 10, 2 );
        add_action( 'woocommerce_store_api_checkout_order_processed', array( 'Mailchimp_Woocommerce_SMS_Blocks_Integration', 'order_customer_processed' ) );

		require_once dirname( __FILE__ ) . '/woocommerce-blocks-integration-sms.php';
		require_once dirname( __FILE__ ) . '/woocommerce-blocks-extend-store-endpoint-sms.php';

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new Mailchimp_Woocommerce_SMS_Blocks_Integration() );
			}
		);

		Mailchimp_Woocommerce_SMS_Blocks_Extend_Store_Endpoint::init();
	}
} );

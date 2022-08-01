<?php

define( 'MAILCHIMP_WOOCOMMERCE_NEWSLETTER_VERSION', '1.0.0' );

if (class_exists( '\Automattic\WooCommerce\Blocks\Package' ) &&
    interface_exists('\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface')) {
	require dirname( __FILE__ ) . '/woocommerce-blocks-integration.php';
	add_action(
		'woocommerce_blocks_checkout_block_registration',
		function( $integration_registry ) {
			$integration_registry->register( new Mailchimp_Woocommerce_Newsletter_Blocks_Integration() );
		},
		10,
		1
	);
}

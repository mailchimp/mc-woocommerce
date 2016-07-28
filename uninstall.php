<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_Woocommerce
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('mailchimp-woocommerce');
delete_option('mailchimp-woocommerce-errors.store_info');
delete_option('mailchimp-woocommerce-sync.orders.completed_at');
delete_option('mailchimp-woocommerce-sync.orders.current_page');
delete_option('mailchimp-woocommerce-sync.products.completed_at');
delete_option('mailchimp-woocommerce-sync.products.current_page');
delete_option('mailchimp-woocommerce-sync.syncing');
delete_option('mailchimp-woocommerce-sync.started_at');
delete_option('mailchimp-woocommerce-sync.completed_at');
delete_option('mailchimp-woocommerce-validation.api.ping');
delete_option('mailchimp-woocommerce-cached-api-lists');
delete_option('mailchimp-woocommerce-cached-api-ping-check');

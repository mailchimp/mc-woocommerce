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
 * @package    MailChimp_WooCommerce
 */

// If uninstall not called from WordPress, then exit.
if (!defined( 'WP_UNINSTALL_PLUGIN')) {
	exit;
}

if (!isset($mailchimp_woocommerce_spl_autoloader) || $mailchimp_woocommerce_spl_autoloader === false) {
    include_once "bootstrap.php";
}

try {
    if (($options = get_option('mailchimp-woocommerce', false)) && is_array($options)) {
        if (isset($options['mailchimp_api_key'])) {
            $store_id = get_option('mailchimp-woocommerce-store_id', false);
            if (!empty($store_id)) {
                $api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);
                $result = $api->deleteStore($store_id) ? 'has been deleted' : 'did not delete';
                error_log("store id {$store_id} {$result} MailChimp");
            }
        }
    }
} catch (\Exception $e) {
    error_log($e->getMessage().' on '.$e->getLine().' in '.$e->getFile());
}

mailchimp_clean_database();

<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://mailchimp.com
 * @since             1.0.0
 * @package           MailChimp_WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Mailchimp for WooCommerce
 * Plugin URI:        https://mailchimp.com/connect-your-store/
 * Description:       Connects WooCommerce to Mailchimp to sync your store data, send targeted campaigns to your customers, and sell more stuff. 
 * Version:           5.0
 * Author:            Mailchimp
 * Author URI:        https://mailchimp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * Tested up to: 6.7
 * WC requires at least: 8.2
 * WC tested up to: 9.5
 */

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
    die;
}

if (!isset($mailchimp_woocommerce_spl_autoloader) || $mailchimp_woocommerce_spl_autoloader === false) {
    // bootstrapper
    include_once __DIR__ . "/bootstrap.php";
}

register_activation_hook( __FILE__, 'activate_mailchimp_woocommerce');

// plugins loaded callback
add_action('plugins_loaded', 'mailchimp_on_all_plugins_loaded', 12);

// make this a one liner for testing and code separation
include_once __DIR__ . '/blocks/newsletter.php';

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/// if the user chas enabled the high performance bolt on
if (defined('MAILCHIMP_HIGH_PERFORMANCE') && MAILCHIMP_HIGH_PERFORMANCE) {
    include_once __DIR__.'/includes/function-include-action-scheduler.php';
}

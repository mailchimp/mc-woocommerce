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
 * Description:       Mailchimp - WooCommerce plugin
 * Version:           2.1.10
 * Author:            Mailchimp
 * Author URI:        https://mailchimp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp-woocommerce
 * Domain Path:       /languages
 * Requires at least: 4.4
 * Tested up to: 4.9.6
 */

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
    die;
}

if (!isset($mailchimp_woocommerce_spl_autoloader) || $mailchimp_woocommerce_spl_autoloader === false) {
    include_once "bootstrap.php";
}

register_activation_hook( __FILE__, 'activate_mailchimp_woocommerce');
add_action('plugins_loaded', 'mailchimp_on_all_plugins_loaded', 12);

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_address = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
    $_SERVER['REMOTE_ADDR'] = $forwarded_address[0];
}


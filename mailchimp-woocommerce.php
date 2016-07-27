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
 * @package           MailChimp_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       MailChimp WooCommerce
 * Plugin URI:        https://mailchimp.com
 * Description:       MailChimp - WooCommerce plugin
 * Version:           1.0.4
 * Author:            Ryan Hungate
 * Author URI:        https://mailchimp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @return object
 */
function mailchimp_environment_variables() {
	return (object) array(
		'repo' => 'staging',
		'environment' => 'staging',
		'version' => '1.0.4',
	);
}

/**
 * @return string
 */
function mailchimp_get_store_id() {
	return md5(get_option('siteurl'));
}

/**
 * @param array $data
 * @return mixed
 */
function mailchimp_array_remove_empty($data) {
	if (empty($data) || !is_array($data)) {
		return array();
	}
	foreach ($data as $key => $value) {
		if ($value === null || $value === '') {
			unset($data[$key]);
		}
	}
	return $data;
}

/**
 * @return array
 */
function mailchimp_get_timezone_list() {
	$zones_array = array();
	$timestamp = time();
	$current = date_default_timezone_get();

	foreach(timezone_identifiers_list() as $key => $zone) {
		date_default_timezone_set($zone);
		$zones_array[$key]['zone'] = $zone;
		$zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
	}

	date_default_timezone_set($current);

	return $zones_array;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailchimp-woocommerce-activator.php
 */
function activate_mailchimp_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-woocommerce-activator.php';

	MailChimp_Woocommerce_Activator::activate();
}

/**
 * Create the queue tables
 */
function install_mailchimp_queue()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-woocommerce-activator.php';
	MailChimp_Woocommerce_Activator::create_queue_tables();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailchimp-woocommerce-deactivator.php
 */
function deactivate_mailchimp_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-woocommerce-deactivator.php';
	MailChimp_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mailchimp_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_mailchimp_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mailchimp_woocommerce() {
	$env = mailchimp_environment_variables();
	$plugin = new MailChimp_Woocommerce($env->environment, $env->version);
	$plugin->run();
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$forwarded_address = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = $forwarded_address[0];
}

/** Add all the MailChimp hooks. */
run_mailchimp_woocommerce();


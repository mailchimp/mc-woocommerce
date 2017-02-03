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
 * Plugin Name:       MailChimp for WooCommerce
 * Plugin URI:        https://mailchimp.com/connect-your-store/
 * Description:       MailChimp - WooCommerce plugin
 * Version:           1.0.9
 * Author:            MailChimp
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
	global $wp_version;

	return (object) array(
		'repo' => 'master',
		'environment' => 'production',
		'version' => '1.0.9',
		'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
	);
}

/**
 * @return bool|int
 */
function mailchimp_get_list_id() {
	if (($options = get_option('mailchimp-woocommerce', false)) && is_array($options)) {
		if (isset($options['mailchimp_list'])) {
			return $options['mailchimp_list'];
		}
	}
	return false;
}

/**
 * @return string
 */
function mailchimp_get_store_id() {
	return md5(get_option('siteurl'));
}

/**
 * @return bool|MailChimp_WooCommerce_MailChimpApi
 */
function mailchimp_get_api() {
	if (($options = get_option('mailchimp-woocommerce', false)) && is_array($options)) {
		if (isset($options['mailchimp_api_key'])) {
			return new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);
		}
	}
	return false;
}

/**
 * @param $key
 * @param null $default
 * @return null
 */
function mailchimp_get_option($key, $default = null) {
	$options = get_option('mailchimp-woocommerce');
	if (!is_array($options)) {
		return $default;
	}
	if (!array_key_exists($key, $options)) {
		return $default;
	}
	return $options[$key];
}

/**
 * @param $key
 * @param $default
 * @return mixed|void
 */
function mailchimp_get_data($key, $default) {
	return get_option('mailchimp-woocommerce-'.$key, $default);
}

/**
 * @param $date
 * @return DateTime
 */
function mailchimp_date_utc($date) {
	$timezone = wc_timezone_string();
	//$timezone = mailchimp_get_option('store_timezone', 'America/New_York');
	if (is_numeric($date)) {
		$stamp = $date;
		$date = new \DateTime('now', new DateTimeZone($timezone));
		$date->setTimestamp($stamp);
	} else {
		$date = new \DateTime($date, new DateTimeZone($timezone));
	}

	$date->setTimezone(new DateTimeZone('UTC'));
	return $date;
}

/**
 * @param $date
 * @return DateTime
 */
function mailchimp_date_local($date) {
    $timezone = mailchimp_get_option('store_timezone', 'America/New_York');
	if (is_numeric($date)) {
		$stamp = $date;
		$date = new \DateTime('now', new DateTimeZone('UTC'));
		$date->setTimestamp($stamp);
	} else {
		$date = new \DateTime($date, new DateTimeZone('UTC'));
	}

    $date->setTimezone(new DateTimeZone($timezone));
    return $date;
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
function activate_mailchimp_woocommerce()
{
	// if we don't have woocommerce we need to display a horrible error message before the plugin is installed.
	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		// Deactivate the plugin
		deactivate_plugins(__FILE__);
		$error_message = __('The MailChimp For WooCommerce plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'woocommerce');
		wp_die($error_message);
	}

	// ok we can activate this thing.
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

/**
 * See if we need to run any updates.
 */
function run_mailchimp_plugin_updater() {
	if (!class_exists('PucFactory')) {
		require plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';
	}

	/** @var \PucGitHubChecker_3_1 $checker */
	$updater = PucFactory::getLatestClassVersion('PucGitHubChecker');

	if (class_exists($updater)) {
		$env = mailchimp_environment_variables();
		$checker = new $updater('https://github.com/mailchimp/mc-woocommerce/', __FILE__, $env->repo, 1);
		$checker->handleManualCheck();
	}
}

/**
 * @param $action
 * @param $message
 * @param array $data
 * @return array|WP_Error
 */
function mailchimp_log($action, $message, $data = array())
{
	$options = MailChimp_Woocommerce::getLoggingConfig();

	if (!$options->enable_logging || !$options->account_id || !$options->username) {
		return false;
	}

	$data = array(
		'account_id' => $options->account_id,
		'username' => $options->username,
		'store_domain' => site_url(),
		'platform' => 'woocommerce',
		'action' => $action,
		'message' => $message,
		'data' => $data,
	);

	return wp_remote_post($options->endpoint, array(
		'headers' => array(
			'Accept: application/json',
			'Content-Type: application/json'
		),
		'body' => json_encode($data),
	));
}

/**
 * Determine if a given string contains a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 */
function mailchimp_string_contains($haystack, $needles)
{
	foreach ((array) $needles as $needle) {
		if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
			return true;
		}
	}

	return false;
}


/**
 * @return int
 */
function mailchimp_get_product_count() {
	$posts = mailchimp_count_posts('product');
	$total = 0;
	foreach ($posts as $status => $count) {
		$total += $count;
	}
	return $total;
}

/**
 * @return int
 */
function mailchimp_get_order_count() {
	$posts = mailchimp_count_posts('shop_order');
	unset($posts['auto-draft']);
	$total = 0;
	foreach ($posts as $status => $count) {
		$total += $count;
	}
	return $total;
}

/**
 * @param $type
 * @return array|null|object
 */
function mailchimp_count_posts($type) {
	global $wpdb;
	$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status";
	$posts = $wpdb->get_results( $wpdb->prepare($query, $type));
	$response = array();
	foreach ($posts as $post) {
		$response[$post->post_status] = $post->num_posts;
	}
	return $response;
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

function mailchimp_woocommerce_add_meta_tags() {
    echo '<meta name="referrer" content="always"/>';
}

add_action('wp_head', 'mailchimp_woocommerce_add_meta_tags');

/** Add the plugin updater function ONLY when they are logged in as admin. */
add_action('admin_init', 'run_mailchimp_plugin_updater');

/** Add all the MailChimp hooks. */
run_mailchimp_woocommerce();



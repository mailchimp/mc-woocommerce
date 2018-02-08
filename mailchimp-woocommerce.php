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
 * Version:           2.1.4
 * Author:            MailChimp
 * Author URI:        https://mailchimp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp-woocommerce
 * Domain Path:       /languages
 * Requires at least: 4.4
 * Tested up to: 4.9.2
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

    $o = get_option('mailchimp-woocommerce', false);

    return (object) array(
        'repo' => 'master',
        'environment' => 'production',
        'version' => '2.1.4',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => class_exists('WC') ? WC()->version : null,
        'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'none',
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
    $store_id = mailchimp_get_data('store_id', false);
    if (empty($store_id)) {
        mailchimp_set_data('store_id', $store_id = uniqid(), 'yes');
    }
    return $store_id;
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
 * @param null $default
 * @return mixed
 */
function mailchimp_get_data($key, $default = null) {
    return get_option('mailchimp-woocommerce-'.$key, $default);
}

/**
 * @param $key
 * @param $value
 * @param string $autoload
 * @return bool
 */
function mailchimp_set_data($key, $value, $autoload = 'yes') {
    return update_option('mailchimp-woocommerce-'.$key, $value, $autoload);
}

/**
 * @param $date
 * @return DateTime
 */
function mailchimp_date_utc($date) {
    $timezone = wc_timezone_string();
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
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
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
 * @return bool
 */
function mailchimp_check_woocommerce_plugin_status()
{
    if (defined("RUNNING_CUSTOM_WOOCOMMERCE") && RUNNING_CUSTOM_WOOCOMMERCE === true) return true;
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins')));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailchimp-woocommerce-activator.php
 */
function activate_mailchimp_woocommerce() {
    // if we don't have woocommerce we need to display a horrible error message before the plugin is installed.
    if (!mailchimp_check_woocommerce_plugin_status()) {
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
function install_mailchimp_queue() {
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
 * @param $action
 * @param $message
 * @param null $data
 */
function mailchimp_debug($action, $message, $data = null) {
    if (mailchimp_environment_variables()->logging === 'debug') {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->debug("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $action
 * @param $message
 * @param array $data
 * @return array|WP_Error
 */
function mailchimp_log($action, $message, $data = array()) {
    if (mailchimp_environment_variables()->logging !== 'none') {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->notice("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $action
 * @param $message
 * @param array $data
 * @return array|WP_Error
 */
function mailchimp_error($action, $message, $data = array()) {
    if (mailchimp_environment_variables()->logging !== 'none') {
        if ($message instanceof \Exception) $message = mailchimp_error_trace($message);
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->error("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param Exception $e
 * @param string $wrap
 * @return string
 */
function mailchimp_error_trace(\Exception $e, $wrap = "") {
    $error = "{$e->getMessage()} on {$e->getLine()} in {$e->getFile()}";
    if (empty($wrap)) return $error;
    return "{$wrap} :: {$error}";
}

/**
 * Determine if a given string contains a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 */
function mailchimp_string_contains($haystack, $needles) {
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
    unset($posts['auto-draft'], $posts['trash']);
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
    unset($posts['auto-draft'], $posts['trash']);
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

/**
 * @return bool
 */
function mailchimp_update_connected_site_script() {
    // pull the store ID
    $store_id = mailchimp_get_store_id();

    // if the api is configured
    if ($store_id && ($api = mailchimp_get_api())) {

        // if we have a store
        if (($store = $api->getStore($store_id))) {

            // handle the coupon sync if we don't have a flag that says otherwise.
            $job = new MailChimp_WooCommerce_Process_Coupons();
            if ($job->getData('sync.coupons.completed_at', false) === false) {
                wp_queue($job);
            }

            // see if we have a connected site script url/fragment
            $url = $store->getConnectedSiteScriptUrl();
            $fragment = $store->getConnectedSiteScriptFragment();

            // if it's not empty we need to set the values
            if ($url && $fragment) {

                // update the options for script_url and script_fragment
                update_option('mailchimp-woocommerce-script_url', $url);
                update_option('mailchimp-woocommerce-script_fragment', $fragment);

                // check to see if the site is connected
                if (!$api->checkConnectedSite($store_id)) {

                    // if it's not, connect it now.
                    $api->connectSite($store_id);
                }

                return true;
            }
        }
    }
    return false;
}

/**
 * @return string|false
 */
function mailchimp_get_connected_site_script_url() {
    return get_option('mailchimp-woocommerce-script_url', false);
}

/**
 * @return string|false
 */
function mailchimp_get_connected_site_script_fragment() {
    return get_option('mailchimp-woocommerce-script_fragment', false);
}

/**
 * @return bool
 */
function mailchimp_running_in_console() {
    return (bool) (defined( 'DISABLE_WP_HTTP_WORKER' ) && true === DISABLE_WP_HTTP_WORKER);
}

/**
 * @return bool
 */
function mailchimp_http_worker_is_running() {
    return (bool) get_site_transient('http_worker_lock');
}

/**
 * @return array|WP_Error
 */
function mailchimp_call_http_worker_manually() {
    $action = 'http_worker';
    $query_args = apply_filters('http_worker_query_args', array(
        'action' => $action,
        'nonce'  => wp_create_nonce($action),
    ));
    $query_url = apply_filters('http_worker_query_url', admin_url('admin-ajax.php'));
    $post_args = apply_filters('http_worker_post_args', array(
        'timeout'   => 0.01,
        'blocking'  => false,
        'cookies'   => $_COOKIE,
        'sslverify' => apply_filters('https_local_ssl_verify', false),
    ));
    $url = add_query_arg($query_args, $query_url);
    return wp_remote_post(esc_url_raw($url), $post_args);
}

register_activation_hook( __FILE__, 'activate_mailchimp_woocommerce' );

// cancelling out the deactivation hook code for now.
//register_deactivation_hook( __FILE__, 'deactivate_mailchimp_woocommerce' );

/**
 *
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

function mailchimp_on_all_plugins_loaded() {
    if (mailchimp_check_woocommerce_plugin_status()) {

        /**
         * The core plugin class that is used to define internationalization,
         * admin-specific hooks, and public-facing site hooks.
         */
        require plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-woocommerce.php';

        add_action('wp_head', 'mailchimp_woocommerce_add_meta_tags');
        /** Add all the MailChimp hooks. */
        run_mailchimp_woocommerce();
    }
}

add_action( 'plugins_loaded', 'mailchimp_on_all_plugins_loaded' );

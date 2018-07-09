<?php

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
    die;
}

$mailchimp_woocommerce_spl_autoloader = true;

spl_autoload_register(function($class) {
    $classes = array(
        // includes root
        'MailChimp_Service' => 'includes/class-mailchimp-woocommerce-service.php',
        'MailChimp_WooCommerce_Options' => 'includes/class-mailchimp-woocommerce-options.php',
        'MailChimp_Newsletter' => 'includes/class-mailchimp-woocommerce-newsletter.php',
        'MailChimp_WooCommerce_Loader' => 'includes/class-mailchimp-woocommerce-loader.php',
        'MailChimp_WooCommerce_i18n' => 'includes/class-mailchimp-woocommerce-i18n.php',
        'MailChimp_WooCommerce_Deactivator' => 'includes/class-mailchimp-woocommerce-deactivator.php',
        'MailChimp_WooCommerce_Activator' => 'includes/class-mailchimp-woocommerce-activator.php',
        'MailChimp_WooCommerce' => 'includes/class-mailchimp-woocommerce.php',
        'MailChimp_WooCommerce_Privacy' => 'includes/class-mailchimp-woocommerce-privacy.php',

        // includes/api/assets
        'MailChimp_WooCommerce_Address' => 'includes/api/assets/class-mailchimp-address.php',
        'MailChimp_WooCommerce_Cart' => 'includes/api/assets/class-mailchimp-cart.php',
        'MailChimp_WooCommerce_Customer' => 'includes/api/assets/class-mailchimp-customer.php',
        'MailChimp_WooCommerce_LineItem' => 'includes/api/assets/class-mailchimp-line-item.php',
        'MailChimp_WooCommerce_Order' => 'includes/api/assets/class-mailchimp-order.php',
        'MailChimp_WooCommerce_Product' => 'includes/api/assets/class-mailchimp-product.php',
        'MailChimp_WooCommerce_ProductVariation' => 'includes/api/assets/class-mailchimp-product-variation.php',
        'MailChimp_WooCommerce_PromoCode' => 'includes/api/assets/class-mailchimp-promo-code.php',
        'MailChimp_WooCommerce_PromoRule' => 'includes/api/assets/class-mailchimp-promo-rule.php',
        'MailChimp_WooCommerce_Store' => 'includes/api/assets/class-mailchimp-store.php',

        // includes/api/errors
        'MailChimp_WooCommerce_Error' => 'includes/api/errors/class-mailchimp-error.php',
        'MailChimp_WooCommerce_ServerError' => 'includes/api/errors/class-mailchimp-server-error.php',

        // includes/api/helpers
        'MailChimp_WooCommerce_CurrencyCodes' => 'includes/api/helpers/class-mailchimp-woocommerce-api-currency-codes.php',
        'MailChimp_Api_Locales' => 'includes/api/helpers/class-mailchimp-woocommerce-api-locales.php',

        // includes/api
        'MailChimp_WooCommerce_MailChimpApi' => 'includes/api/class-mailchimp-api.php',
        'MailChimp_WooCommerce_Api' => 'includes/api/class-mailchimp-woocommerce-api.php',
        'MailChimp_WooCommerce_CreateListSubmission' => 'includes/api/class-mailchimp-woocommerce-create-list-submission.php',
        'MailChimp_WooCommerce_Transform_Coupons' => 'includes/api/class-mailchimp-woocommerce-transform-coupons.php',
        'MailChimp_WooCommerce_Transform_Orders' => 'includes/api/class-mailchimp-woocommerce-transform-orders-wc3.php',
        'MailChimp_WooCommerce_Transform_Products' => 'includes/api/class-mailchimp-woocommerce-transform-products.php',

        // includes/processes
        'MailChimp_WooCommerce_Abstract_Sync' => 'includes/processes/class-mailchimp-woocommerce-abstract-sync.php',
        'MailChimp_WooCommerce_Cart_Update' => 'includes/processes/class-mailchimp-woocommerce-cart-update.php',
        'MailChimp_WooCommerce_Process_Coupons' => 'includes/processes/class-mailchimp-woocommerce-process-coupons.php',
        'MailChimp_WooCommerce_Process_Orders' => 'includes/processes/class-mailchimp-woocommerce-process-orders.php',
        'MailChimp_WooCommerce_Process_Products' => 'includes/processes/class-mailchimp-woocommerce-process-products.php',
        'MailChimp_WooCommerce_SingleCoupon' => 'includes/processes/class-mailchimp-woocommerce-single-coupon.php',
        'MailChimp_WooCommerce_Single_Order' => 'includes/processes/class-mailchimp-woocommerce-single-order.php',
        'MailChimp_WooCommerce_Single_Product' => 'includes/processes/class-mailchimp-woocommerce-single-product.php',
        'MailChimp_WooCommerce_User_Submit' => 'includes/processes/class-mailchimp-woocommerce-user-submit.php',

        'MailChimp_WooCommerce_Public' => 'public/class-mailchimp-woocommerce-public.php',
        'MailChimp_WooCommerce_Admin' => 'admin/class-mailchimp-woocommerce-admin.php',

        'WP_Job' => 'includes/vendor/queue/classes/wp-job.php',
        'WP_Queue' => 'includes/vendor/queue/classes/wp-queue.php',
        'WP_Http_Worker' => 'includes/vendor/queue/classes/worker/wp-http-worker.php',
        'WP_Worker' => 'includes/vendor/queue/classes/worker/wp-worker.php',
        'Queue_Command' => 'includes/vendor/queue/classes/cli/queue-command.php',
    );

    // if the file exists, require it
    $path = plugin_dir_path( __FILE__ );
    if (array_key_exists($class, $classes) && file_exists($path.$classes[$class])) {
        require $path.$classes[$class];
    }
});

/**
 * @return object
 */
function mailchimp_environment_variables() {
    global $wp_version;

    $o = get_option('mailchimp-woocommerce', false);

    return (object) array(
        'repo' => 'master',
        'environment' => 'production',
        'version' => '2.1.9',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => class_exists('WC') ? WC()->version : null,
        'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'standard',
    );
}

// Add WP CLI commands
if (defined( 'WP_CLI' ) && WP_CLI) {
    try {
        /**
         * Service push to MailChimp
         *
         * <type>
         * : product_sync order_sync order product
         */
        function mailchimp_cli_push_command( $args, $assoc_args ) {
            if (is_array($args) && isset($args[0])) {
                switch($args[0]) {

                    case 'product_sync':
                        wp_queue(new MailChimp_WooCommerce_Process_Products());
                        WP_CLI::success("queued up the product sync!");
                        break;

                    case 'order_sync':
                        wp_queue(new MailChimp_WooCommerce_Process_Orders());
                        WP_CLI::success("queued up the order sync!");
                        break;

                    case 'order':
                        if (!isset($args[1])) {
                            wp_die('You must specify an order id as the 2nd parameter.');
                        }
                        wp_queue(new MailChimp_WooCommerce_Single_Order($args[1]));
                        WP_CLI::success("queued up the order {$args[1]}!");
                        break;

                    case 'product':
                        if (!isset($args[1])) {
                            wp_die('You must specify a product id as the 2nd parameter.');
                        }
                        wp_queue(new MailChimp_WooCommerce_Single_Product($args[1]));
                        WP_CLI::success("queued up the product {$args[1]}!");
                        break;
                }
            }
        };
        WP_CLI::add_command( 'mailchimp_push', 'mailchimp_cli_push_command');
        WP_CLI::add_command( 'queue', 'Queue_Command' );
    } catch (\Exception $e) {}
}

if (!function_exists( 'wp_queue')) {
    /**
     * WP queue.
     *
     * @param WP_Job $job
     * @param int    $delay
     */
    function wp_queue( WP_Job $job, $delay = 0 ) {
        global $wp_queue;
        if (empty($wp_queue)) {
            $wp_queue = new WP_Queue();
        }
        $wp_queue->push( $job, $delay );
        do_action( 'wp_queue_job_pushed', $job );
    }
}


/**
 * @return bool
 */
function mailchimp_should_init_queue() {
    return mailchimp_detect_admin_ajax() && mailchimp_is_configured() && !mailchimp_running_in_console() && !mailchimp_http_worker_is_running();
}

/**
 * @return bool
 */
function mailchimp_is_configured() {
    return (bool) (mailchimp_get_api_key() && mailchimp_get_list_id());
}

/**
 * @return bool|int
 */
function mailchimp_get_api_key() {
    return mailchimp_get_option('mailchimp_api_key', false);
}

/**
 * @return bool|int
 */
function mailchimp_get_list_id() {
    return mailchimp_get_option('mailchimp_list', false);
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
    if (($key = mailchimp_get_api_key())) {
        return new MailChimp_WooCommerce_MailChimpApi($key);
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
    // if you are using a custom folder name other than woocommerce just define the constant to TRUE
    if (defined("RUNNING_CUSTOM_WOOCOMMERCE") && RUNNING_CUSTOM_WOOCOMMERCE === true) {
        return true;
    }
    // it the plugin is active, we're good.
    if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins')))) {
        return true;
    }
    // lets check for network activation woo installs now too.
    if (function_exists('is_plugin_active_for_network')) {
        return is_plugin_active_for_network( 'woocommerce/woocommerce.php');
    }
    return false;
}

/**
 * Get all the registered image sizes along with their dimensions
 *
 * @global array $_wp_additional_image_sizes
 *
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 *
 * @return array $image_sizes The image sizes
 */
function mailchimp_woocommerce_get_all_image_sizes() {
    global $_wp_additional_image_sizes;
    $image_sizes = array();
    $default_image_sizes = get_intermediate_image_sizes();
    foreach ($default_image_sizes as $size) {
        $image_sizes[$size]['width'] = intval( get_option("{$size}_size_w"));
        $image_sizes[$size]['height'] = intval( get_option("{$size}_size_h"));
        $image_sizes[$size]['crop'] = get_option("{$size}_crop") ? get_option("{$size}_crop") : false;
    }
    if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)) {
        $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
    }
    return $image_sizes;
}

/**
 * @return array
 */
function mailchimp_woocommerce_get_all_image_sizes_list() {
    $response = array();
    foreach (mailchimp_woocommerce_get_all_image_sizes() as $key => $data) {
        $label = ucwords(str_replace('_', ' ', $key));
        $response[$key] = "{$label} ({$data['width']} x {$data['height']})";
    }
    return $response;
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
    MailChimp_WooCommerce_Activator::activate();
}

/**
 * Create the queue tables
 */
function install_mailchimp_queue() {
    MailChimp_WooCommerce_Activator::create_queue_tables();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailchimp-woocommerce-deactivator.php
 */
function deactivate_mailchimp_woocommerce() {
    MailChimp_WooCommerce_Deactivator::deactivate();
}

/**
 * @param $action
 * @param $message
 * @param null $data
 */
function mailchimp_debug($action, $message, $data = null) {
    if (mailchimp_environment_variables()->logging === 'debug' && function_exists('wc_get_logger')) {
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
    if (mailchimp_environment_variables()->logging !== 'none' && function_exists('wc_get_logger')) {
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
    if (mailchimp_environment_variables()->logging !== 'none' && function_exists('wc_get_logger')) {
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
 * @return bool
 */
function mailchimp_detect_admin_ajax() {
    if (defined('DOING_CRON') && DOING_CRON) return true;
    if (!is_admin()) return false;
    if (!defined('DOING_AJAX')) return false;
    return DOING_AJAX;
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
 * @param $email
 * @return bool
 */
function mailchimp_email_is_privacy_protected($email) {
    return $email === 'deleted@site.invalid';
}

/**
 * @param $email
 * @return bool
 */
function mailchimp_email_is_amazon($email) {
    return mailchimp_string_contains($email, '@marketplace.amazon.com');
}

/**
 * @param $str
 * @return string
 */
function mailchimp_hash_trim_lower($str) {
    return md5(trim(strtolower($str)));
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

function mailchimp_flush_queue_tables() {
    try {
        /** @var \ */
        global $wpdb;
        $wpdb->query($wpdb->prepare("TRUNCATE `{$wpdb->prefix}queue`", array()));
        $wpdb->query($wpdb->prepare("TRUNCATE `{$wpdb->prefix}failed_jobs`", array()));
        $wpdb->query($wpdb->prepare("TRUNCATE `{$wpdb->prefix}mailchimp_carts`", array()));
    } catch (\Exception $e) {}
}

function mailchimp_flush_sync_pointers() {
    // clean up the initial sync pointers
    foreach (array('orders', 'products', 'coupons') as $resource_type) {
        delete_option("mailchimp-woocommerce-sync.{$resource_type}.started_at");
        delete_option("mailchimp-woocommerce-sync.{$resource_type}.completed_at");
        delete_option("mailchimp-woocommerce-sync.{$resource_type}.current_page");
    }
}

/**
 * To be used when running clean up for uninstalls or re-installs.
 */
function mailchimp_clean_database() {
    mailchimp_flush_queue_tables();

    // clean up the initial sync pointers
    mailchimp_flush_sync_pointers();

    delete_option('mailchimp-woocommerce');
    delete_option('mailchimp-woocommerce-store_id');
    delete_option('mailchimp-woocommerce-sync.syncing');
    delete_option('mailchimp-woocommerce-sync.started_at');
    delete_option('mailchimp-woocommerce-sync.completed_at');
    delete_option('mailchimp-woocommerce-validation.api.ping');
    delete_option('mailchimp-woocommerce-cached-api-lists');
    delete_option('mailchimp-woocommerce-cached-api-ping-check');
    delete_option('mailchimp-woocommerce-errors.store_info');
}

function run_mailchimp_woocommerce() {
    $env = mailchimp_environment_variables();
    $plugin = new MailChimp_WooCommerce($env->environment, $env->version);
    $plugin->run();
}

function mailchimp_woocommerce_add_meta_tags() {
    echo '<meta name="referrer" content="always"/>';
}

function mailchimp_on_all_plugins_loaded() {
    if (mailchimp_check_woocommerce_plugin_status()) {
        add_action('wp_head', 'mailchimp_woocommerce_add_meta_tags');
        run_mailchimp_woocommerce();
    }
}

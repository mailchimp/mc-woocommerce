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
        'Mailchimp_Woocommerce_Deactivation_Survey' => 'includes/class-mailchimp-woocommerce-deactivation-survey.php',
        'MailChimp_WooCommerce_Queue' => 'includes/class-mailchimp-woocommerce-queue.php',
        'MailChimp_WooCommerce_Rest_Api' => 'includes/class-mailchimp-woocommerce-rest-api.php',
        
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
        'MailChimp_WooCommerce_RateLimitError' => 'includes/api/errors/class-mailchimp-rate-limit-error.php',
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
        'MailChimp_WooCommerce_Process_Coupons_Initial_Sync' => 'includes/processes/class-mailchimp-woocommerce-process-coupons-initial-sync.php',
        'MailChimp_WooCommerce_Process_Orders' => 'includes/processes/class-mailchimp-woocommerce-process-orders.php',
        'MailChimp_WooCommerce_Process_Products' => 'includes/processes/class-mailchimp-woocommerce-process-products.php',
        'MailChimp_WooCommerce_SingleCoupon' => 'includes/processes/class-mailchimp-woocommerce-single-coupon.php',
        'MailChimp_WooCommerce_Single_Order' => 'includes/processes/class-mailchimp-woocommerce-single-order.php',
        'MailChimp_WooCommerce_Single_Product' => 'includes/processes/class-mailchimp-woocommerce-single-product.php',
        'MailChimp_WooCommerce_User_Submit' => 'includes/processes/class-mailchimp-woocommerce-user-submit.php',
        'MailChimp_WooCommerce_Rest_Queue' => 'includes/processes/class-mailchimp-woocommerce-rest-queue.php',

        'MailChimp_WooCommerce_Public' => 'public/class-mailchimp-woocommerce-public.php',
        'MailChimp_WooCommerce_Admin' => 'admin/class-mailchimp-woocommerce-admin.php',

        'WP_Job' => 'includes/vendor/queue/classes/wp-job.php',
        'WP_Queue' => 'includes/vendor/queue/classes/wp-queue.php',
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
        'environment' => 'staging', // staging or production
        'version' => '2.1.17',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => function_exists('WC') ? WC()->version : null,
        'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'debug',
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
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Products());
                        WP_CLI::success("queued up the product sync!");
                        break;

                    case 'order_sync':
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Orders());
                        WP_CLI::success("queued up the order sync!");
                        break;

                    case 'order':
                        if (!isset($args[1])) {
                            wp_die('You must specify an order id as the 2nd parameter.');
                        }
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Order($args[1]));
                        WP_CLI::success("queued up the order {$args[1]}!");
                        break;

                    case 'product':
                        if (!isset($args[1])) {
                            wp_die('You must specify a product id as the 2nd parameter.');
                        }
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($args[1]));
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
 * @param WP_Job $job
 * @param int $delay
 * @param bool $force_now
 */
function mailchimp_handle_or_queue(WP_Job $job, $delay = 0, $force_now = false)
{   
    if ($job instanceof \MailChimp_WooCommerce_Single_Order && isset($job->order_id)) {
        // if this is a order process already queued - just skip this
        if (get_site_transient("mailchimp_order_being_processed_{$job->order_id}") == true) {
            mailchimp_debug('order_sync.abort', "transient true for order {$job->order_id}. Skipping queue item addition.");
            return;
        }
        // tell the system the order is already queued for processing in this saving process - and we don't need to process it again.
        set_site_transient( "mailchimp_order_being_processed_{$job->order_id}", true, 30);
        mailchimp_debug('order_sync.transient', "transient set for order {$job->order_id}");
    }

    wp_queue($job, $delay);

    // force now is used during the sync.
    if ($force_now === true || mailchimp_should_init_rest_queue()) {
        mailchimp_call_rest_api_queue_manually();
    }
}

/**
 * @param bool $job_check
 * @return bool
 */
function mailchimp_should_init_rest_queue($job_check = false) {
    if (mailchimp_running_in_console()) return false;
    if (mailchimp_queue_is_disabled()) return false;
    if (!mailchimp_is_configured()) return false;
    if (mailchimp_http_worker_is_running()) return false;
    return !$job_check ? true : MailChimp_WooCommerce_Queue::instance()->available_jobs() > 0;
}

/**
 * @param int $max
 * @return bool|DateTime
 */
function mailchimp_get_http_lock_expiration($max = 300) {
    try {
        if (($lock_time = (string) get_site_transient('http_worker_lock')) && !empty($lock_time)) {
            $parts = str_getcsv($lock_time, ' ');
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $lock_duration = apply_filters('http_worker_lock_time', 60);
                if (empty($lock_duration) || !is_numeric($lock_duration) || ($lock_duration >= $max)) {
                    $lock_duration = $max;
                }
                // craft a new date time object
                $date = new \DateTime();
                // set the timestamp with the lock duration
                $date->setTimestamp(((int) $parts[1] + $lock_duration));
                return $date;
            }
        }
    } catch (\Exception $e) {}
    return false;
}

/**
 * @return bool
 */
function mailchimp_should_reset_http_lock() {
    return ($lock = mailchimp_get_http_lock_expiration()) && $lock->getTimestamp() < time();
}

/**
 * @return bool
 */
function mailchimp_reset_http_lock() {
    return delete_site_transient( 'http_worker_lock' );
}

/**
 * @param bool $force
 * @return bool
 */
function mailchimp_list_has_double_optin($force = false) {
    if (!mailchimp_is_configured()) {
        return false;
    }

    $key = 'mailchimp_double_optin';

    $double_optin = get_site_transient($key);

    if (!$force && ($double_optin === 'yes' || $double_optin === 'no')) {
        return $double_optin === 'yes';
    }

    try {
        $data = mailchimp_get_api()->getList(mailchimp_get_list_id());
        $double_optin = array_key_exists('double_optin', $data) ? ($data['double_optin'] ? 'yes' : 'no') : 'no';
        set_site_transient($key, $double_optin, 600);
        return $double_optin === 'yes';
    } catch (\Exception $e) {
        set_site_transient($key, 'no', 600);
    }

    return $double_optin === 'yes';
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
 * @return array
 */
function mailchimp_get_user_tags_to_update() {
    $tags = mailchimp_get_option('mailchimp_user_tags');

    if (empty($tags)) {
        return false;
    }

    $tags = explode(',', $tags);

    foreach ($tags as $tag) {
        $formatted_tags[] = array("name" => $tag, "status" => 'active');
    }

    // apply filter to user custom tags addition/removal
    $formatted_tags = apply_filters('mailchimp_user_tags', $formatted_tags);
    
    return $formatted_tags;
}

/**
 * @return bool|MailChimp_WooCommerce_MailChimpApi
 */
function mailchimp_get_api() {

    if (($api = MailChimp_WooCommerce_MailChimpApi::getInstance())) {
        return $api;
    }

    if (($key = mailchimp_get_api_key())) {
        return MailChimp_WooCommerce_MailChimpApi::constructInstance($key);
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
    if (!is_multisite()) return false;
    $plugins = get_site_option( 'active_sitewide_plugins');
    return isset($plugins['woocommerce/woocommerce.php']);
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
        $label = __($label);
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
    $error = "Error Code {$e->getCode()} :: {$e->getMessage()} on {$e->getLine()} in {$e->getFile()}";
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
                mailchimp_handle_or_queue($job);
            }
            return mailchimpi_refresh_connected_site_script($store);
        }
    }
    return false;
}

/**
 * @return bool|DateTime
 */
function mailchimp_get_updated_connected_site_since_as_date_string() {
    $updated_at = get_option('mailchimp-woocommerce-script_updated_at', false);
    if (empty($updated_at)) return '';
    try {
        $date = new \DateTime();
        $date->setTimestamp($updated_at);
        return $date->format('D, M j, Y g:i A');
    } catch (\Exception $e) {
        return '';
    }
}

/**
 * @return int
 */
function mailchimp_get_updated_connected_site_since() {
    $updated_at = get_option('mailchimp-woocommerce-script_updated_at', false);
    return empty($updated_at) ? 1000000 : (time() - $updated_at);
}

/**
 * @param int $seconds
 * @return bool
 */
function mailchimp_should_update_connected_site_script($seconds = 600) {
    return mailchimp_get_updated_connected_site_since() >= $seconds;
}

/**
 *
 */
function mailchimp_update_connected_site_script_from_cdn() {
    if (mailchimp_is_configured() && mailchimp_should_update_connected_site_script() && ($store_id = mailchimp_get_store_id())) {
        try {
            // pull the store, refresh the connected site url
            mailchimpi_refresh_connected_site_script(mailchimp_get_api()->getStore($store_id));
        } catch (\Exception $e) {
            mailchimp_error("admin.update_connected_site_script", $e->getMessage());
        }
    }
}

/**
 * @param MailChimp_WooCommerce_Store $store
 * @return bool
 */
function mailchimpi_refresh_connected_site_script(MailChimp_WooCommerce_Store $store) {

    $api = mailchimp_get_api();

    $url = $store->getConnectedSiteScriptUrl();
    $fragment = $store->getConnectedSiteScriptFragment();

    // if it's not empty we need to set the values
    if ($url && $fragment) {

        // update the options for script_url and script_fragment
        update_option('mailchimp-woocommerce-script_url', $url);
        update_option('mailchimp-woocommerce-script_fragment', $fragment);
        update_option('mailchimp-woocommerce-script_updated_at', time());

        // check to see if the site is connected
        if (!$api->checkConnectedSite($store->getId())) {

            // if it's not, connect it now.
            $api->connectSite($store->getId());
        }

        return true;
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
function mailchimp_queue_is_disabled() {
    return (bool) (defined( 'MAILCHIMP_DISABLE_QUEUE' ) && true === MAILCHIMP_DISABLE_QUEUE);
}

/**
 * @return bool
 */
function mailchimp_http_worker_is_running() {
    if (mailchimp_should_reset_http_lock()) {
        mailchimp_reset_http_lock();
        mailchimp_log('http_worker_lock', "HTTP worker lock needed to be deleted to initiate the queue.");
        return false;
    }
    return (bool) get_site_transient('http_worker_lock');
}

/**
 * @param $email
 * @return bool
 */
function mailchimp_email_is_allowed($email) {
    if (!is_email($email) || mailchimp_email_is_amazon($email) || mailchimp_email_is_privacy_protected($email)) {
        return false;
    }
    return true;
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
    return mailchimp_string_contains($email, '@marketplace.amazon.');
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
function mailchimp_call_rest_api_queue_manually() {
    return MailChimp_WooCommerce_Rest_Api::work();
}

/**
 * @return array|WP_Error
 */
function mailchimp_call_rest_api_test() {
    return MailChimp_WooCommerce_Rest_Api::test();
}

/**
 * @return bool
 */
function mailchimp_should_use_local_curl_for_rest_api() {
    return defined('MAILCHIMP_USE_CURL') && MAILCHIMP_USE_CURL;
}

/**
 * @return int
 */
function mailchimp_get_local_curl_http_version() {
    return defined('MAILCHIMP_USE_LOCAL_CURL_VERSION') ? MAILCHIMP_USE_LOCAL_CURL_VERSION : CURL_HTTP_VERSION_1_1;
}

/**
 * @return bool|string
 */
function mailchimp_get_curlopt_interface_ip() {
    return defined('MAILCHIMP_USE_OUTBOUND_IP') ? MAILCHIMP_USE_OUTBOUND_IP : false;
}

/**
 * @return bool|string domain name
 */
function mailchimp_get_local_rest_domain_or_ip() {
    if (defined('MAILCHIMP_REST_IP')) {
        return MAILCHIMP_REST_IP;
    } else if (defined('MAILCHIMP_REST_LOCALHOST')) {
        return 'localhost';
    } else {
        return false;
    }
}

/**
 * @return string url
 */
function mailchimp_apply_local_rest_api_override($url, $alternate_host) {
    $parsed_url = parse_url($url);
    $p             = array();
    $p['scheme']   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : ''; 
    $p['host']     = $alternate_host;         
    $p['port']     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : ''; 
    $p['user']     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : ''; 
    $p['pass']     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass']  : ''; 
    $p['pass']     = ( $p['user'] || $p['pass'] ) ? $p['pass']."@" : ''; 
    $p['path']     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : ''; 
    $p['query']    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : ''; 
    $p['fragment'] = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';
    
    return $url = $p['scheme'].$p['user'].$p['pass'].$p['host'].$p['port'].$p['path'].$p['query'].$p['fragment'];
}


/**
 * @return bool|string
 */
function mailchimp_woocommerce_check_if_http_worker_fails() {

    // if the user has defined that they are going to use the queue from the console, we can just return false here.
    // this means they've agreed to run the queue from a CLI version instead.
    if (mailchimp_running_in_console()) {
        return false;
    }

    // if the function doesn't exist we can't do anything.
    if (!mailchimp_should_use_local_curl_for_rest_api() && !function_exists('wp_remote_post')) {
        mailchimp_set_data('test.can.remote_post', false);
        mailchimp_set_data('test.can.remote_post.error', 'function "wp_remote_post" does not exist');
        return __('function "wp_remote_post" does not exist', 'mailchimp-woocommerce');
    }

    // apply a blocking call to make sure we get the response back
    $response = mailchimp_call_rest_api_test();

    if (is_wp_error($response)) {
        // nope, we have problems
        mailchimp_set_data('test.can.remote_post', false);
        mailchimp_set_data('test.can.remote_post.error', $response->get_error_message());
        return $response->get_error_message();
    } elseif (is_array($response) && isset($response['http_response']) && ($r = $response['http_response'])) {
        /** @var \WP_HTTP_Requests_Response $r */
        if ((int) $r->get_status() !== 200) {
            $message = __('The REST API seems to be disabled on this wordpress site. Please enable to sync data.', 'mailchimp-woocommerce');
            mailchimp_set_data('test.can.remote_post', false);
            mailchimp_set_data('test.can.remote_post.error', $message);
            mailchimp_error('test.rest_api', '', array(
                'status' => $r->get_status(),
                'body' => $r->get_data(),
            ));
            return $message;
        }
    }

    // yep all good.
    mailchimp_set_data('test.can.remote_post', true);
    mailchimp_set_data('test.can.remote_post.error', false);
    return false;
}

/**
 * @param $url
 * @param array $params
 * @param array $headers
 * @return array|mixed|object|WP_Error|null
 */
function mailchimp_woocommerce_rest_api_get($url, $params = array(), $headers = array()) {
    $alternate_host = mailchimp_get_local_rest_domain_or_ip();
    if ($alternate_host) {
       $url = mailchimp_apply_local_rest_api_override($url, $alternate_host);
    }

    if (mailchimp_should_use_local_curl_for_rest_api()) {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, mailchimp_apply_local_curl_options('GET', $url, $params, $headers));
            return mailchimp_process_local_curl_response($curl);
        } catch (\Exception $e) {
            mailchimp_error("mailchimp_woocommerce_rest_api_get", $e->getMessage());
            return new WP_Error( 'http_request_failed', $e->getMessage());
        }
    }

    $params['headers'] = $headers;

    return wp_remote_get($url, $params);
}

/**
 * @param $method
 * @param $url
 * @param array $params
 * @param array $headers
 * @return array
 */
function mailchimp_apply_local_curl_options($method, $url, $params = array(), $headers = array()) {

    $headers = (array) $headers;

    $curl_options = array(
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_URL => mailchimp_rest_api_url($url, '', $params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => $params['timeout'],
        CURLOPT_HTTP_VERSION => mailchimp_get_local_curl_http_version(),
        CURLINFO_HEADER_OUT => true,
        CURLOPT_HTTPHEADER => array_merge(mailchimp_get_http_local_json_header(), $headers)
    );

    // if we have a dedicated IP address, and have set a configuration for it, we'll use it here.
    if (($interface = mailchimp_get_curlopt_interface_ip())) {
        $curl_options[CURLOPT_INTERFACE] = $interface;
    }

    return $curl_options;
}

/**
 * @param $curl
 * @return array|mixed|object|null
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_process_local_curl_response($curl)
{
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($err) {
        throw new MailChimp_WooCommerce_Error('CURL error :: '.$err, 500);
    }
    $data = json_decode($response, true);
    if (empty($info) || ($info['http_code'] >= 200 && $info['http_code'] <= 400)) {
        if (is_array($data)) {
            mailchimp_rest_check_for_errors($data);
        }
        return $data;
    }
    if ($info['http_code'] >= 400 && $info['http_code'] < 500) {
        throw new MailChimp_WooCommerce_Error($data['title'] .' :: '.$data['detail'], $data['status']);
    } else if ($info['http_code'] >= 500) {
        throw new MailChimp_WooCommerce_ServerError($data['detail'], $data['status']);
    }
    return json_encode(array('info' => $info, 'response' => $response));
}

/**
 * @param array $data
 * @return bool
 * @throws MailChimp_WooCommerce_Error
 */
function mailchimp_rest_check_for_errors(array $data)
{
    // if we have an array of error data push it into a message
    if (isset($data['errors'])) {
        $message = '';
        foreach ($data['errors'] as $error) {
            $message .= '<p>'.$error['field'].': '.$error['message'].'</p>';
        }
        throw new MailChimp_WooCommerce_Error($message, $data['status']);
    }
    // make sure the response is correct from the data in the response array
    if (isset($data['status']) && $data['status'] >= 400) {
        throw new MailChimp_WooCommerce_Error($data['detail'], $data['status']);
    }
    return false;
}

/**
 * @param $url
 * @param string $extra
 * @param null $params
 * @return string
 */
function mailchimp_rest_api_url($url, $extra = '', $params = null)
{
    if (!empty($extra)) {
        $url .= $extra;
    }
    if (!empty($params)) {
        $url .= '?'.(is_array($params) ? http_build_query($params) : $params);
    }
    return $url;
}

/**
 * @return array
 */
function mailchimp_get_http_local_json_header() {
    $env = mailchimp_environment_variables();
    $server_user_agent = "MailChimp for WooCommerce/{$env->version} PHP/{$env->php_version} WordPress/{$env->wp_version} Woo/{$env->wc_version}";
    return array(
        'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ),
        'Accept' => 'application/json',
        'User-Agent'  => $server_user_agent
    );
}

/**
 * @return string
 */
function mailchimp_test_http_worker_ajax() {
    wp_send_json(array('success' => true), 200);
}

/**
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function mailchimp_get_transient($key, $default = null) {
    $transient = get_site_transient("mailchimp-woocommerce.{$key}");
    return empty($transient) ? $default : $transient;
}

/**
 * @param $key
 * @param $value
 * @param int $seconds
 * @return bool
 */
function mailchimp_set_transient($key, $value, $seconds = 60) {
    mailchimp_delete_transient($key);
    return set_site_transient("mailchimp-woocommerce.{$key}", array(
        'value' => $value,
        'expires' => time()+$seconds,
    ), $seconds);
}

/**
 * @param $key
 * @return bool
 */
function mailchimp_delete_transient($key) {
    return delete_site_transient("mailchimp-woocommerce.{$key}");
}

/**
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function mailchimp_get_transient_value($key, $default = null) {
    $transient = mailchimp_get_transient($key, false);
    return (is_array($transient) && array_key_exists('value', $transient)) ? $transient['value'] : $default;
}

/**
 * @param $key
 * @param $value
 * @return bool|null
 */
function mailchimp_check_serialized_transient_changed($key, $value) {
    if (($saved = mailchimp_get_transient_value($key)) && !empty($saved)) {
        return serialize($saved) === serialize($value);
    }
    return null;
}

/**
 * @param $email
 * @return bool|string
 */
function mailchimp_get_transient_email_key($email) {
    $email = md5(trim(strtolower($email)));
    return empty($email) ? false : 'MailChimp_WooCommerce_User_Submit@'.$email;
}

/**
 * @param $email
 * @param $status_meta
 * @param int $seconds
 * @return bool
 */
function mailchimp_tell_system_about_user_submit($email, $status_meta, $seconds = 60) {
   return mailchimp_set_transient(mailchimp_get_transient_email_key($email), $status_meta, $seconds);
}

/**
 * @param $subscribed
 * @return array
 */
function mailchimp_get_subscriber_status_options($subscribed) {
    $requires = mailchimp_list_has_double_optin();

    // if it's true - we set this value to NULL so that we do a 'pending' association on the member.
    $status_if_new = $requires ? null : $subscribed;
    $status_if_update = $requires ? 'pending' : $subscribed;

    // set an array of status meta that we will use for comparison below to the transient data
    return array(
        'created' => $status_if_new,
        'updated' => $status_if_update
    );
}

function mailchimp_check_if_on_sync_tab() {
    if ((isset($_GET['page']) && $_GET['page'] === 'mailchimp-woocommerce')) {
        $options = get_option('mailchimp-woocommerce', array());
        if (isset($_GET['tab'])) {
            if ($_GET['tab'] === 'sync') {
                return true;
            }
            return false;
        }
        else if (isset($options['active_tab']) && $options['active_tab'] === 'sync') {
			return true;
		}
    }
    return false;
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

/**
 * @param array $data
 * @param int $status
 * @return WP_REST_Response
 */
function mailchimp_rest_response($data, $status = 200) {
    if (!is_array($data)) $data = array();
    $response = new WP_REST_Response($data);
    $response->set_status($status);
    return $response;
}

/**
 * @return bool
 */
function mailchimp_has_started_syncing() {
    $sync_started_at = get_option('mailchimp-woocommerce-sync.started_at');
    $sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');
    return ($sync_completed_at < $sync_started_at);
}

/**
 * @return bool
 */
function mailchimp_is_done_syncing() {
    $sync_started_at = get_option('mailchimp-woocommerce-sync.started_at');
    $sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');
    return ($sync_completed_at >= $sync_started_at);
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


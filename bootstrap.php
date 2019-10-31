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
        'MailChimp_WooCommerce_Rest_Api' => 'includes/class-mailchimp-woocommerce-rest-api.php',
        'Mailchimp_Wocoomerce_CLI' => 'includes/class-mailchimp-woocommerce-cli.php',
        
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
        'Mailchimp_Woocommerce_Job' => 'includes/processes/class-mailchimp-woocommerce-job.php',
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
        
        'MailChimp_WooCommerce_Public' => 'public/class-mailchimp-woocommerce-public.php',
        'MailChimp_WooCommerce_Admin' => 'admin/class-mailchimp-woocommerce-admin.php',
        
        // Queue system Action Scheduler
        'ActionScheduler' => 'includes/vendor/action-scheduler/action-scheduler.php',
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
        'environment' => 'production', // staging or production
        'version' => '2.3',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => function_exists('WC') ? WC()->version : null,
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
        WP_CLI::add_command( 'queue', 'Mailchimp_Wocoomerce_CLI' );
    } catch (\Exception $e) {}
}

/**
 * Push a job onto the Action Scheduler queue.
 *
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 *
 * @return true
 */
function mailchimp_as_push( Mailchimp_Woocommerce_Job $job, $delay = 0 ) {			
    global $wpdb;
    $job_id = isset($job->id) ? $job->id : get_class($job);
    
    $args = array(
        'job' => maybe_serialize($job),
        'obj_id' => $job_id,
        'created_at'   => gmdate( 'Y-m-d H:i:s', time() )
    );
    
    $existing_actions = as_get_scheduled_actions(array(
        'hook' => get_class($job), 
        'status' => ActionScheduler_Store::STATUS_PENDING,  
        'args' => array(
            'obj_id' => isset($job->id) ? $job->id : null), 
            'group' => 'mc-woocommerce'
        )
    );
    
    if (!empty($existing_actions)) {
        as_unschedule_action(get_class($job), array('obj_id' => $job->id), 'mc-woocommerce');
    }
    else {
        $inserted = $wpdb->insert($wpdb->prefix."mailchimp_jobs", $args);
        if (!$inserted) {
            try {
                if (mailchimp_string_contains($wpdb->last_error, 'Table')) {
                    mailchimp_debug('DB Issue: `mailchimp_job` table was not found!', 'Creating Tables');
                    install_mailchimp_queue();
                    $inserted = $wpdb->insert($wpdb->prefix."mailchimp_jobs", $args);
                    if (!$inserted) {
                        mailchimp_debug('Queue Job '.get_class($job), $wpdb->last_error);
                    }
                }
            } catch (\Exception $e) {
                mailchimp_error_trace($e, 'trying to create queue tables');
            }
        }
    }

    // TODO: deal with errors
    $action = as_schedule_single_action( strtotime( '+'.$delay.' seconds' ), get_class($job), array('obj_id' => $job_id), "mc-woocommerce");
    
    $message = ($job_id != get_class($job)) ? ' :: obj_id '.$job_id : '';
    if (!empty($existing_actions)) {
        mailchimp_debug('action_scheduler.reschedule_job', get_class($job) . ($delay > 0 ? ' restarts in '.$delay. ' seconds' : ' restarts in the next minute' ) . $message);
    } 
    else {
        mailchimp_log('action_scheduler.queue_job', get_class($job) . ($delay > 0 ? ' starts in '.$delay. ' seconds' : ' starts in the next minute' ) .$message);
    }

    return $action;	
}


/**
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 * @param bool $force_now
 */
function mailchimp_handle_or_queue(Mailchimp_Woocommerce_Job $job, $delay = 0)
{   
    if ($job instanceof \MailChimp_WooCommerce_Single_Order && isset($job->id)) {
        // if this is a order process already queued - just skip this
        if (get_site_transient("mailchimp_order_being_processed_{$job->id}") == true) {
            return;
        }
        // tell the system the order is already queued for processing in this saving process - and we don't need to process it again.
        set_site_transient( "mailchimp_order_being_processed_{$job->id}", true, 30);
    }
    
    $as_job_id = mailchimp_as_push($job, $delay);
    
    if (!is_int($as_job_id)) {
        mailchimp_log('action_scheduler.queue_fail', get_class($job) .' FAILED :: as_job_id: '.$as_job_id);
    }
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
    $api = mailchimp_get_api();
    if (mailchimp_is_configured()) {
        // let's retrieve the store for this domain, through the API
        $store = $api->getStore($store_id, false);
        // if there's no store, try to fetch from mc a store related to the current domain
        if (!$store) {
            $stores = $api->stores();
            //iterate thru stores, find correct store ID and save it to db
            foreach ($stores as $mc_store) {
                if ($mc_store->getDomain() === get_option('siteurl')) {
                    update_option('mailchimp-woocommerce-store_id', $mc_store->getId(), 'yes');
                    $store_id = $mc_store->getId();
                }
            }
        }
    }

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

function mailchimp_flush_database_tables() {
    try {
        /** @var \ */
        global $wpdb;
        
        mailchimp_delete_as_jobs();
        
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_carts`");
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_jobs`");
    } catch (\Exception $e) {}
}

function mailchimp_delete_as_jobs() {

    $existing_as_actions = as_get_scheduled_actions(
        array(
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'group' => 'mc-woocommerce',
            'per_page' => -1,
        )
    );
    
    if (!empty($existing_as_actions)) {
        foreach ($existing_as_actions as $as_action) {
            as_unschedule_action($as_action->get_hook(), $as_action->get_args(), 'mc-woocommerce');    # code...
        }
        return true;
    }
    return false;

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
    // delete custom tables data
    mailchimp_flush_database_tables();

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


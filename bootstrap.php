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
        'MailChimp_WooCommerce_HPOS' => 'includes/class-mailchimp-woocommerce-hpos.php',
        'Mailchimp_Woocommerce_Block_Editor' => 'includes/class-mailchimp-woocommerce-block-editor.php',

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
        'MailChimp_WooCommerce_Process_Orders' => 'includes/processes/class-mailchimp-woocommerce-process-orders.php',
        'MailChimp_WooCommerce_Process_Products' => 'includes/processes/class-mailchimp-woocommerce-process-products.php',
        'MailChimp_WooCommerce_SingleCoupon' => 'includes/processes/class-mailchimp-woocommerce-single-coupon.php',
        'MailChimp_WooCommerce_Single_Order' => 'includes/processes/class-mailchimp-woocommerce-single-order.php',
        'MailChimp_WooCommerce_Single_Product' => 'includes/processes/class-mailchimp-woocommerce-single-product.php',
        'MailChimp_WooCommerce_User_Submit' => 'includes/processes/class-mailchimp-woocommerce-user-submit.php',
        'MailChimp_WooCommerce_Process_Full_Sync_Manager' => 'includes/processes/class-mailchimp-woocommerce-full-sync-manager.php',
        'MailChimp_WooCommerce_Subscriber_Sync' => 'includes/processes/class-mailchimp-woocommerce-subscriber-sync.php',
        'MailChimp_WooCommerce_WebHooks_Sync' => 'includes/processes/class-mailchimp-woocommerce-webhooks-sync.php',
        'MailChimp_WooCommerce_Pull_Last_Campaign' => 'includes/processes/class-mailchimp-woocommerce-pull-last-campaign.php',

        'MailChimp_WooCommerce_Public' => 'public/class-mailchimp-woocommerce-public.php',
        'MailChimp_WooCommerce_Admin' => 'admin/class-mailchimp-woocommerce-admin.php',

        'MailChimp_WooCommerce_Fix_Duplicate_Store' => 'includes/api/class-mailchimp-woocommerce-fix-duplicate-store.php',
        'MailChimp_WooCommerce_Logs' => 'includes/api/class-mailchimp-woocommerce-logs.php',
        'MailChimp_WooCommerce_Tower' => 'includes/api/class-mailchimp-woocommerce-tower.php',
        'MailChimp_WooCommerce_Log_Viewer' => 'includes/api/class-mailchimp-woocommerce-log-viewer.php',
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
        'version' => '3.1',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => function_exists('WC') ? WC()->version : null,
        'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'standard',
    );
}

/**
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 *
 * @return false|int|string
 */
function mailchimp_as_push( Mailchimp_Woocommerce_Job $job, $delay = 0 ) {			
    global $wpdb;
    $current_page = isset($job->current_page) && $job->current_page >= 0 ? $job->current_page : false;
    $job_id = isset($job->id) ? $job->id : ($current_page ? $job->current_page : get_class($job));
    $message = ($job_id != get_class($job)) ? ' :: '. (isset($job->current_page) ? 'page ' : 'obj_id ') . $job_id : '';
    $attempts = $job->get_attempts() > 0 ? ' attempt:' . $job->get_attempts() : '';
    if ($job->get_attempts() <= 5) {

        $args = array(
            'job' => maybe_serialize($job),
            'obj_id' => $job_id,
            'created_at'   => gmdate( 'Y-m-d H:i:s', time() )
        );
        
        $existing_actions =  function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(array(
            'hook' => get_class($job), 
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'args' => array(
                'obj_id' => isset($job->id) ? $job->id : null), 
                'group' => 'mc-woocommerce'
            )
        ) : null;
        
        if (!empty($existing_actions)) {
            try {
                as_unschedule_action(get_class($job), array('obj_id' => $job->id), 'mc-woocommerce');
            } catch (Exception $e) {}
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
                } catch (Exception $e) {
                    mailchimp_error_trace($e, 'trying to create queue tables');
                }
            }
        }
        
        $action_args = array(
            'obj_id' => $job_id,
        );

        if ($current_page !== false) {
            $action_args['page'] = $current_page;
        }

        $action = as_schedule_single_action( strtotime( '+'.$delay.' seconds' ), get_class($job), $action_args, "mc-woocommerce");
      
        if (!empty($existing_actions)) {
            mailchimp_debug('action_scheduler.reschedule_job', get_class($job) . ($delay > 0 ? ' restarts in '.$delay. ' seconds' : ' re-queued' ) . $message . $attempts);
        } 
        else {
            mailchimp_log('action_scheduler.queue_job', get_class($job) . ($delay > 0 ? ' starts in '.$delay. ' seconds' : ' queued' ) . $message . $attempts);
        }
    
        return $action;	
    }
    else {
        $job->set_attempts(0);
        mailchimp_log('action_scheduler.fail_job', get_class($job) . ' cancelled. Too many attempts' . $message . $attempts);
        return false;
    }
}


/**
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 */
function mailchimp_handle_or_queue(Mailchimp_Woocommerce_Job $job, $delay = 0)
{
    if ($job instanceof MailChimp_WooCommerce_Single_Order && isset($job->id) && empty($job->gdpr_fields)) {
        // if this is a order process already queued - just skip this
        if (get_site_transient("mailchimp_order_being_processed_{$job->id}") == true) {
            mailchimp_debug('queue', "Not queuing up order {$job->id} because it's already queued");
            return;
        }
        // tell the system the order is already queued for processing in this saving process - and we don't need to process it again.
        set_site_transient( "mailchimp_order_being_processed_{$job->id}", true, 30);
    }

	// Allow sites to alter whether the order or product is synced.
	// $job should contain at least the ID of the order/product as $job->id.
	if ( $job instanceof \MailChimp_WooCommerce_Single_Order ) {
		if ( apply_filters( 'mailchimp_should_push_order', $job->id ) === false ) {
			mailchimp_debug( 'action_scheduler.queue_job.order', "Order {$job->id} not pushed do to filter." );
			return null;
		}
	} else if ( $job instanceof \MailChimp_WooCommerce_Single_Product ) {
		if ( apply_filters( 'mailchimp_should_push_product', $job->id ) === false ) {
			mailchimp_debug( 'action_scheduler.queue_job.product', "Product {$job->id} not pushed do to filter." );
			return null;
		}
	}

    $as_job_id = mailchimp_as_push($job, $delay);
    
    if (!is_int($as_job_id)) {
        mailchimp_log('action_scheduler.queue_fail', get_class($job) .' FAILED :: as_job_id: '.$as_job_id);
    }
}

/**
 * @param $job_hook
 *
 * @return int
 */
function mailchimp_get_remaining_jobs_count($job_hook) {
    $existing_actions =  function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(
        array(
            'hook' => $job_hook, 
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'group' => 'mc-woocommerce', 
            'per_page' => -1,
        ), 'ids'
    ) : null;
    // mailchimp_log('sync.full_sync_manager.queue', "counting {$job_hook} actions:", array($existing_actions));		
    return count($existing_actions);
}

function mailchimp_submit_subscribed_only() {
    return ! (bool) mailchimp_get_option('mailchimp_ongoing_sync_status', '1');
}

/**
 * @return bool
 */
function mailchimp_carts_disabled() {
    return mailchimp_get_option('mailchimp_cart_tracking', 'all') === 'disabled';
}

/**
 * @return bool
 */
function mailchimp_carts_subscribers_only() {
    return mailchimp_get_option('mailchimp_cart_tracking', 'all') === 'subscribed';
}

/**
 * @param $email
 * @return string|null
 */
function mailchimp_get_subscriber_status($email) {
    try {
        return mailchimp_get_api()->member(mailchimp_get_list_id(), $email)['status'];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * @param false $force
 *
 * @return bool
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_list_has_double_optin($force = false) {
    if (!mailchimp_is_configured()) {
        return false;
    }

    $key = 'double_optin';

    $double_optin = mailchimp_get_transient($key);

    if (!$force && ($double_optin === 'yes' || $double_optin === 'no')) {
        return $double_optin === 'yes';
    }

    try {
        $data = mailchimp_get_api()->getList(mailchimp_get_list_id());
        $double_optin = array_key_exists('double_optin', $data) ? ($data['double_optin'] ? 'yes' : 'no') : 'no';
        mailchimp_set_transient($key, $double_optin, 600);
        return $double_optin === 'yes';
    } catch (Exception $e) {
        mailchimp_error('api.list', __('Error retrieving list for double_optin check', 'mailchimp-for-woocommerce'));
        throw $e;
    }
}


/**
 * @return bool
 */
function mailchimp_is_configured() {
    return (bool) (mailchimp_get_api_key() && mailchimp_get_list_id());
}

/**
 * @return bool
 */
function mailchimp_action_scheduler_exists() {
    return ( did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) && class_exists( 'ActionScheduler', false ) );
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
 * @param $key
 *
 * @return string
 */
function mailchimp_build_webhook_url( $key ) {
    //$key = base64_encode($key);
    return MailChimp_WooCommerce_Rest_Api::url('member-sync') . '?auth=' . $key;
}
/**
 * Generate random string
 * @return string
 */
function mailchimp_create_webhook_token(){
    return md5( trim( strtolower(get_bloginfo('url') . '|' . time() . '|' . mailchimp_get_list_id() . '|' . wp_salt() )  ) );
}
/**
 * @param $url
 */
function mailchimp_set_webhook_url( $url ) {
    update_option('mc-mailchimp_webhook_url', $url);
}
/**
 * Returns webhookurl option
 * @return string
 */
function mailchimp_get_webhook_url() {
    return get_option('mc-mailchimp_webhook_url', false);
}
/**
 * Returns webhook url
 * @return array Common localhost ips
 */
function mailchimp_common_loopback_ips(){
    return array(
	    '127.0.0.1',
	    '0:0:0:0:0:0:0:1',
	    '::1'
    );
}

/**
 * @return mixed|string
 */
function mailchimp_get_store_id() {
    $store_id = mailchimp_get_data('store_id', false);

    // if the store ID is not empty, let's check the last time the store id's have been verified correctly
    if (!empty($store_id)) {
        // see if we have a record of the last verification set for this job.
        $last_verification = mailchimp_get_data('store-id-last-verified');
        // if it's less than 300 seconds, we don't need to beat up on Mailchimp's API to do this so often.
        // just return the store ID that was in memory.
        if ((!empty($last_verification) && is_numeric($last_verification)) && ((time() - $last_verification) < 600)) {
            //mailchimp_log('debug.performance', 'prevented store endpoint api call');
            return $store_id;
        }
    }

    $api = mailchimp_get_api();
    if (mailchimp_is_configured()) {
        //mailchimp_log('debug.performance', 'get_store_id - calling STORE endpoint.');
        // let's retrieve the store for this domain, through the API
        $store = $api->getStoreIfAvailable($store_id);
        // if there's no store, try to fetch from mc a store related to the current domain
        if (!$store) {
            //mailchimp_log('debug.performance', 'get_store_id - no store found - calling STORES endpoint to update site id.');
            $stores = $api->stores();
            if (!empty($stores)) {
                //iterate thru stores, find correct store ID and save it to db
                foreach ($stores as $mc_store) {
                    if ($mc_store->getDomain() === get_option('siteurl')) {
                        update_option('mailchimp-woocommerce-store_id', $mc_store->getId(), 'yes');
                        $store_id = $mc_store->getId();
                    }
                }
            }
        }
    }

    if (empty($store_id)) {
        mailchimp_set_data('store_id', $store_id = uniqid());
    }

    // tell the system the last time we verified this store ID is valid with a timestamp.
    mailchimp_set_data('store-id-last-verified', time());

    return $store_id;
}

/**
 * @param null $email
 * @param null $order
 *
 * @return false|mixed|void
 */
function mailchimp_get_user_tags_to_update($email = null, $order = null) {
    $tags = mailchimp_get_option('mailchimp_user_tags');
    $formatted_tags = array();
    
    if (!empty($tags)) {
        $tags = explode(',', $tags);

        foreach ($tags as $tag) {
            $formatted_tags[] = array("name" => $tag, "status" => 'active');
        }
    }

    // apply filter to user custom tags addition/removal
    $formatted_tags = apply_filters('mailchimp_user_tags', $formatted_tags, $email, $order);

    return empty($formatted_tags) ? false : $formatted_tags;
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
 *
 * @return DateTime
 * @throws Exception
 */
function mailchimp_date_utc($date) {
    $timezone = wc_timezone_string();
    if (is_numeric($date)) {
        $stamp = $date;
        $date = new DateTime('now', new DateTimeZone($timezone));
        $date->setTimestamp($stamp);
    } else {
        $date = new DateTime($date, new DateTimeZone($timezone));
    }

    $date->setTimezone(new DateTimeZone('UTC'));
    return $date;
}

/**
 * @param $date
 *
 * @return DateTime|false
 */
function mailchimp_date_local($date) {
    try {
	    $timezone = str_replace(':', '', mailchimp_get_timezone());

	    if (is_numeric($date)) {
		    $stamp = $date;
		    $date = new DateTime('now', new DateTimeZone('UTC'));
		    $date->setTimestamp($stamp);
	    } else {
		    $date = new DateTime($date, new DateTimeZone('UTC'));
	    }

	    $date->setTimezone(new DateTimeZone($timezone));
	    return $date;
    } catch (Exception $e) {
    	return false;
    }
}

/**
 * @param $data
 *
 * @return array
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
 *  Gets the current tomezone from wordpress settings
 *
 * @param false $humanReadable
 *
 * @return mixed|string|void
 */
function mailchimp_get_timezone($humanReadable = false) {
    // get timezone data from options
    $timezone_string = get_option( 'timezone_string' );
    $offset  = get_option( 'gmt_offset' );
    
    $signal = ($offset <=> 0 ) < 0 ? "-" : "+";
    $offset = sprintf('%1s%02d:%02d', $signal, abs((int) $offset), abs(fmod($offset, 1) * 60));
    
    // shows timezone name + offset in hours and minutes, or only the timezone name. If no timezone string is set, show only offset
    if (!$humanReadable && $timezone_string) {
        $timezone = $timezone_string;
    }
    else if ($humanReadable && $timezone_string) {
        $timezone = "UTC" . $offset .' '. $timezone_string;
    }
    else if ($humanReadable && !$timezone_string) {
         $timezone = "UTC" . $offset;
    }
    else if (!$timezone_string) {
        $timezone = $offset;
    }
    
    return $timezone;
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
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function activate_mailchimp_woocommerce() {

    // if we don't have any of these dependencies,
    // we need to display a horrible error message before the plugin is installed.
    mailchimp_check_curl_is_installed();
    mailchimp_check_woocommerce_is_installed();
    // good to go - activate the plugin.
    MailChimp_WooCommerce_Activator::activate();
}

function mailchimp_check_curl_is_installed() {
    if (!function_exists('curl_exec')) {
        // Deactivate the plugin
        deactivate_plugins(__FILE__);
        $error_message = __('The MailChimp For WooCommerce plugin requires <a href="https://www.php.net/manual/en/book.curl.php/">curl</a> to be enabled!', 'woocommerce');
        wp_die($error_message);
    }
    return true;
}

function mailchimp_check_woocommerce_is_installed() {
    if (!mailchimp_check_woocommerce_plugin_status() && !( defined('WP_CLI') && WP_CLI )) {
    // Deactivate the plugin
        deactivate_plugins(__FILE__);
        $error_message = __('The MailChimp For WooCommerce plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'woocommerce');
        wp_die($error_message);
    }
    return true;
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
 * @return void
 */
function mailchimp_error($action, $message, $data = array()) {
    if (mailchimp_environment_variables()->logging !== 'none' && function_exists('wc_get_logger')) {
        if ($message instanceof Exception) $message = mailchimp_error_trace($message);
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->error("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $e
 * @param string $wrap
 *
 * @return string
 */
function mailchimp_error_trace($e, $wrap = "") {
	if ($e && $e instanceof Exception) {
		$error = "Error Code {$e->getCode()} :: {$e->getMessage()} on {$e->getLine()} in {$e->getFile()}";
	} else {
		$error = "";
	}
    if (empty($wrap)) return $error;
    return "{$wrap} :: {$error}";
}

/**
 *  Determine if a given string contains a given substring.
 *
 * @param $haystack
 * @param $needles
 *
 * @return bool
 */
function mailchimp_string_contains($haystack, $needles) {
    $has_mb = function_exists('mb_strpos');
    foreach ((array) $needles as $needle) {
        $has_needle = $needle != '';
        // make sure the server has "mb_strpos" otherwise this fails. Fallback to "strpos"
        $position = $has_mb ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
        if ($has_needle && $position !== false) {
            return true;
        }
    }
    return false;
}

/**
 * @return int
 */
function mailchimp_get_coupons_count() {
    $posts = mailchimp_count_posts('shop_coupon');
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
    if ($type === 'shop_order') {
        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s";
        $posts = $wpdb->get_results( $wpdb->prepare($query, $type, 'wc-completed'));
    } else if ($type === 'product') {
        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN (%s, %s, %s) group BY post_status";
        $posts = $wpdb->get_results( $wpdb->prepare($query, $type, 'private', 'publish', 'draft'));
    } else {
        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s";
        $posts = $wpdb->get_results( $wpdb->prepare($query, $type, 'publish'));
    }

    $response = array();
    foreach ($posts as $post) {
        $response[$post->post_status] = $post->num_posts;
    }
    return $response;
}

/**
 * @return bool
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_connected_site_script() {
    // pull the store ID
    $store_id = mailchimp_get_store_id();

    // if the api is configured
    if ($store_id && ($api = mailchimp_get_api())) {
        // if we have a store
        if (($store = $api->getStore($store_id))) {
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
        $date = new DateTime();
        $date->setTimestamp($updated_at);
        return $date->format('D, M j, Y g:i A');
    } catch (Exception $e) {
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
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_connected_site_script_from_cdn() {
    if (mailchimp_is_configured() && mailchimp_should_update_connected_site_script() && ($store_id = mailchimp_get_store_id())) {
        try {
            // pull the store, refresh the connected site url
            mailchimpi_refresh_connected_site_script(mailchimp_get_api()->getStore($store_id));
        } catch (Exception $e) {
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
 * @param $email
 * @return mixed
 */
function mailchimp_get_wc_customer($email) {
    global $wpdb;
    return $wpdb->get_row( "SELECT * FROM `{$wpdb->prefix}wc_customer_lookup` WHERE `email` = '{$email}'" );
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
 * @return array|false
 */
function mailchimp_get_subscriber_status_options($subscribed) {
    try {
        $requires = mailchimp_list_has_double_optin();
    } catch (Exception $e) {
        return false;
    }

    // if it's true - we set this value to NULL so that we do a 'pending' association on the member.
    $status_if_new = $requires ? null : $subscribed;
    $status_if_update = $requires ? 'pending' : $subscribed;

    // set an array of status meta that we will use for comparison below to the transient data
    return array(
        'requires_double_optin' => $requires,
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
    } catch (Exception $e) {}
}

function mailchimp_flush_sync_job_tables() {
    try {
        /** @var \ */
        global $wpdb;
        
        mailchimp_delete_as_jobs();
        
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_jobs`");
    } catch (Exception $e) {}
}

function mailchimp_delete_as_jobs() {

    $existing_as_actions = function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(
        array(
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'group' => 'mc-woocommerce',
            'per_page' => -1,
        )
    ) : null;
    
    if (!empty($existing_as_actions)) {
        foreach ($existing_as_actions as $as_action) {
            try {
                as_unschedule_action($as_action->get_hook(), $as_action->get_args(), 'mc-woocommerce');    # code...
            } catch (Exception $e) {}
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
        delete_option("mailchimp-woocommerce-sync.{$resource_type}.started_at");
        delete_option("mailchimp-woocommerce-sync.{$resource_type}.current_page");
    }
}

/**
 * To be used when running clean up for uninstalls or store disconnection.
 */
function mailchimp_clean_database() {
    global $wpdb;
    
    // delete custom tables data
    mailchimp_flush_database_tables();

    // delete plugin options
    $plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'mailchimp%woocommerce%'" );

    foreach( $plugin_options as $option ) {
        delete_option( $option->option_name );
    }
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
    if ($sync_completed_at == false) return false;
    else return ($sync_completed_at >= $sync_started_at);
}

function run_mailchimp_woocommerce() {
    $env = mailchimp_environment_variables();
    $plugin = new MailChimp_WooCommerce($env->environment, $env->version);
    $plugin->run();
    if (isset($_GET['restart_order_sync']) && $_GET['restart_order_sync'] === '1') {
        mailchimp_as_push(new MailChimp_WooCommerce_Process_Orders());
    }
}

function mailchimp_on_all_plugins_loaded() {
    if (mailchimp_check_woocommerce_plugin_status()) {
        run_mailchimp_woocommerce();
    }
}

function mailchimp_get_allowed_capability() {
    $capability = 'manage_options';
    if (current_user_can('manage_woocommerce') && mailchimp_get_option('mailchimp_permission_cap') == 'manage_woocommerce') {
        return 'manage_woocommerce';
    }
    return apply_filters('mailchimp_allowed_capability', $capability);
}

/**
 * @param MailChimp_WooCommerce_Order $order
 * @param null $subscribed
 *
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_member_with_double_opt_in(MailChimp_WooCommerce_Order $order, $subscribed = null)
{
    if (!mailchimp_is_configured()) return;

    $api = mailchimp_get_api();

    // if the customer has a flag to double opt in - we need to push this data over to MailChimp as pending
    // before the order is submitted.
    if ($subscribed) {
        if ($order->getCustomer()->requiresDoubleOptIn()) {
            try {
                $list_id = mailchimp_get_list_id();
                $merge_fields = $order->getCustomer()->getMergeFields();
                $email = $order->getCustomer()->getEmailAddress();

                try {
                    $member = $api->member($list_id, $email);
                    if ($member['status'] === 'transactional') {
                        $api->update($list_id, $email, 'pending', $merge_fields);
                        mailchimp_tell_system_about_user_submit($email, mailchimp_get_subscriber_status_options('pending'));
                        mailchimp_log('double_opt_in', "Updated {$email} Using Double Opt In - previous status was '{$member['status']}'", $merge_fields);
                    }
                } catch (Exception $e) {
                    // if the error code is 404 - need to subscribe them because it means they were not on the list.
                    if ($e->getCode() == 404) {
                        $api->subscribe($list_id, $email, 'pending', $merge_fields);
                        mailchimp_tell_system_about_user_submit($email, mailchimp_get_subscriber_status_options(false));
                        mailchimp_log('double_opt_in', "Subscribed {$email} Using Double Opt In", $merge_fields);
                    } else {
                        mailchimp_error('double_opt_in.update', $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                mailchimp_error('double_opt_in.create', $e->getMessage());
            }
        } else {
            // if we've set the wordpress user correctly on the customer
            if (($wordpress_user = $order->getCustomer()->getWordpressUser())) {
                $user_submit = new MailChimp_WooCommerce_User_Submit($wordpress_user->ID, '1', null);
                $user_submit->handle();
            }
        }
    }
}

/**
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_communication_status() {
    $plugin_admin = MailChimp_WooCommerce_Admin::instance();
    $original_opt = $plugin_admin->getData('comm.opt',0);
    $options = $plugin_admin->getOptions();
    if (is_array($options) && array_key_exists('admin_email', $options)) {
        $plugin_admin->mailchimp_set_communications_status_on_server($original_opt, $options['admin_email']);    
    }
    // communication is ready lets define the webhooks
    $plugin_admin->defineWebhooks();
}

/**
 *
 */
function mailchimp_remove_communication_status() {
    $plugin_admin = MailChimp_WooCommerce_Admin::instance();
    $original_opt = $plugin_admin->getData('comm.opt',0);
    $options = $plugin_admin->getOptions();
    if (is_array($options) && array_key_exists('admin_email', $options)) {
        $remove = true;
        $plugin_admin->mailchimp_set_communications_status_on_server($original_opt, $options['admin_email'], $remove);
    }
}

/**
 * Removes any Woocommece inbox notes this plugin created.
 */
function mailchimp_remove_activity_panel_inbox_notes() {
    if ( ! class_exists( '\Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes' ) ) {
        return;
    }

    // if we can't use woocommerce for some reason - just return null
    if (!function_exists('WC')) {
        return;
    }

    // if we do not have the ability to use notes, just cancel out here.
    if (!method_exists(WC(), 'is_wc_admin_active') || !WC()->is_wc_admin_active()) {
        return;
    }

    try {
	    Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes::delete_notes_with_name( 'mailchimp-for-woocommerce-incomplete-install' );
    } catch (Exception $e) {
        // do nothing.
    }
}

// Print notices outside woocommerce admin bar
function mailchimp_settings_errors() {
    $settings_errors = get_settings_errors();
    $notices_html = '';
    foreach ($settings_errors as $notices) {
        $notices_html .= '<div id="setting-error-'. $notices['code'].'" class="notice notice-'. $notices['type'].' inline is-dismissible"><p>' . $notices['message'] . '</p></div>';
    }
    return $notices_html;
}

/**
 * @param null $user_email
 * @param null $language
 * @param string $caller
 * @param string $status_if_new
 * @param null $order
 * @param null $gdpr_fields
 * @param false $update_status
 *
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_member_data_update($user_email = null, $language = null, $caller = '', $status_if_new = 'transactional', $order = null, $gdpr_fields = null, $update_status = false) {
    mailchimp_debug('debug', "mailchimp_member_data_update", array(
        'user_email' => $user_email,
        'user_language' => $language,
        'caller' => $caller,
        'status_if_new' => $status_if_new,
        'gdpr_fields' => $gdpr_fields,
    ));
    if (!$user_email) return;
    
    $hash = md5(strtolower(trim($user_email)));
    $gdpr_fields_to_save = null;

    if ($caller !== 'cart' || !mailchimp_get_transient($caller . ".member.{$hash}")) {
        $list_id = mailchimp_get_list_id();
        try {
            // try to get the member to update if already synced
            $member = mailchimp_get_api()->member($list_id, $user_email);
            // update member with new data
            // if the member's subscriber status was transactional - and if we're passing in either one of these options below,
            // we can attach the new status to the member.
            if ($member['status'] === 'transactional' && in_array($status_if_new, array('subscribed', 'pending'))) {
                $member['status'] = $status_if_new;
            }
            if (($member['status'] === 'transactional' && in_array($status_if_new, array('subscribed', 'pending'))) || $member['status'] === 'subscribed') {
                if (!empty($gdpr_fields) && is_array($gdpr_fields)) {
                    $gdpr_fields_to_save = [];
                    foreach ($gdpr_fields as $id => $value) {
                        $gdpr_field['marketing_permission_id'] = $id;
                        $gdpr_field['enabled'] = (bool) $value;
                        $gdpr_fields_to_save[] = $gdpr_field;
                    }
                }
            }
            $merge_fields = $order ? apply_filters('mailchimp_get_ecommerce_merge_tags', array(), $order) : array();
            if (!is_array($merge_fields)) $merge_fields = array();
            if ($update_status && in_array($member['status'], array('unsubscribed', 'cleaned'))) {
                $member['status'] = $status_if_new;
            }
            $result = mailchimp_get_api()->update($list_id, $user_email, $member['status'], $merge_fields, null, $language, $gdpr_fields_to_save);
            // set transient to prevent too many calls to update language
            mailchimp_set_transient($caller . ".member.{$hash}", true, 3600);
            mailchimp_log($caller . '.member.updated', "Updated {$user_email} subscriber status to {$result['status']} and language to {$language}");
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                $merge_fields = $order ? apply_filters('mailchimp_get_ecommerce_merge_tags', array(), $order) : array();
                if (!is_array($merge_fields)) $merge_fields = array();
                if (!empty($gdpr_fields) && is_array($gdpr_fields)) {
                    $gdpr_fields_to_save = [];
                    foreach ($gdpr_fields as $id => $value) {
                        $gdpr_field['marketing_permission_id'] = $id;
                        $gdpr_field['enabled'] = (bool) $value;
                        $gdpr_fields_to_save[] = $gdpr_field;
                    }
                }
                // member doesn't exist yet, create as transactional ( or what was passed in the function args )
                mailchimp_get_api()->subscribe($list_id, $user_email, $status_if_new, $merge_fields, array(), $language, $gdpr_fields_to_save);
                // set transient to prevent too many calls to update language
                mailchimp_set_transient($caller . ".member.{$hash}", true, 3600);
                mailchimp_log($caller . '.member.created', "Added {$user_email} as transactional, setting language to [{$language}]");
            } else {
                mailchimp_error($caller . '.member.sync.error', $e->getMessage());
            }
        }
    }
}

/**
 * @param $name
 * @param $value
 * @param $expire
 * @param $path
 * @param string $domain
 * @param bool $secure
 * @param false $httponly
 * @param string $samesite
 */
function mailchimp_set_cookie($name, $value, $expire, $path, $domain = '', $secure = true, $httponly = false, $samesite = 'Strict') {
    if (PHP_VERSION_ID < 70300) {
        @setcookie($name, $value, $expire, $path . '; samesite=' . $samesite, $domain, $secure, $httponly);
        return;
    }
    @setcookie($name, $value, [
        'expires' => $expire,
        'path' => $path,
        'domain' => $domain,
        'samesite' => $samesite,
        'secure' => $secure,
        'httponly' => $httponly,
    ]);
}

/**
 * We will allow people to filter this value - turn it off if they would like.
 * add_filter( 'mailchimp_allowed_to_use_cookie', 'custom_cookie_callback_function', 10, 1 );
 *
 * @param $cookie
 *
 * @return bool
 */
function mailchimp_allowed_to_use_cookie($cookie) {
    $result = apply_filters('mailchimp_allowed_to_use_cookie', $cookie);
    if (is_bool($result)) return $result;
    return $result === $cookie;
}

// the cookie name will be whatever we're trying to set, but the most simple
// return the $cookie_name if you will allow it -
// otherwise it is going to turn this feature off.

/**
 * @return mixed|null
 */
function mailchimp_get_outbound_ip() {
    // if we have a dedicated IP address, and have set a configuration for it, we'll use it here.
    if (defined('MAILCHIMP_USE_OUTBOUND_IP') && !empty(MAILCHIMP_USE_OUTBOUND_IP)) {
        return MAILCHIMP_USE_OUTBOUND_IP;
    }
    return null;
}

/**
 * @return bool
 */
function mailchimp_render_gdpr_fields() {
    if (defined('MAILCHIMP_RENDER_GDPR_FIELDS') && !MAILCHIMP_RENDER_GDPR_FIELDS) {
        return false;
    }
    return true;
}

function mailchimp_expanded_alowed_tags() {
	$my_allowed = wp_kses_allowed_html( 'post' );
	// iframe
	$my_allowed['iframe'] = array(
		'src'             => array(),
		'height'          => array(),
		'width'           => array(),
		'frameborder'     => array(),
		'allowfullscreen' => array(),
	);
	// form fields - input
	$my_allowed['input'] = array(
		'class' => array(),
		'id'    => array(),
		'name'  => array(),
		'value' => array(),
		'type'  => array(),
		'checked' => array(),
	);
	// select
	$my_allowed['select'] = array(
		'class'  => array(),
		'id'     => array(),
		'name'   => array(),
		'value'  => array(),
		'type'   => array(),
	);
	// select options
	$my_allowed['option'] = array(
		'selected' => array(),
	);
	// style
	$my_allowed['style'] = array(
		'types' => array(),
	);

	return $my_allowed;
}

/**
 * @param $user_id
 *
 * @return DateTime|false|null
 */
function mailchimp_get_marketing_status_updated_at($user_id) {
	if (empty($user_id) || !is_numeric($user_id)) {
		return null;
	}
	$value = get_user_meta($user_id, 'mailchimp_woocommerce_marketing_status_updated_at', true);
	return !empty($value) && is_numeric($value) ? mailchimp_date_local($value) : null;
}

// Add WP CLI commands
if (defined( 'WP_CLI' ) && WP_CLI) {
    try {
	    /**
	     * @param $args
	     * @param $assoc_args
	     */
        function mailchimp_cli_push_command( $args, $assoc_args ) {
	        if (!class_exists('WP_CLI')) {
	        	return;
	        }
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
        }
        if (class_exists('WP_CLI')) {
	        WP_CLI::add_command( 'mailchimp_push', 'mailchimp_cli_push_command');
	        WP_CLI::add_command( 'queue', 'Mailchimp_Wocoomerce_CLI' );
        }
    } catch (Exception $e) {}
}
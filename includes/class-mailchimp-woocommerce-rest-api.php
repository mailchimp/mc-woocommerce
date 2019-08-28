<?php

class MailChimp_WooCommerce_Rest_Api
{
    protected static $namespace = 'mailchimp-for-woocommerce/v1';
    protected $http_worker_listen = false;

    /**
     * @param $path
     * @return string
     */
    public static function url($path)
    {
        return esc_url_raw(rest_url(static::$namespace.'/'.ltrim($path, '/')));
    }

    /**
     * @return array|mixed|object|WP_Error|null
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_RateLimitError
     * @throws MailChimp_WooCommerce_ServerError
     */
    public static function test()
    {
        add_filter( 'https_local_ssl_verify', '__return_false', 1 );

        // allow people to change this value just in case, but default to a sensible 10 second timeout.
        $timeout = apply_filters('mailchimp_woocommerce_test_rest_api_timeout', 10);

        // just in case someone didn't return a valid timeout value, go back to the default
        if (!is_numeric($timeout)) {
            $timeout = 10;
        }

        return mailchimp_woocommerce_rest_api_get(
            static::url('ping'),
            array(
                'timeout'   => $timeout,
                'blocking'  => true,
            ),
            mailchimp_get_http_local_json_header()
        );
    }

    /**
     * @param bool $force
     * @return array|mixed|object|WP_Error|null
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_RateLimitError
     * @throws MailChimp_WooCommerce_ServerError
     */
    public static function work($force = false)
    {
        add_filter( 'https_local_ssl_verify', '__return_false', 1 );

        $path = $force ? 'queue/work/force' : 'queue/work';
        // this is the new rest API version
        return mailchimp_woocommerce_rest_api_get(
            static::url($path),
            array(
                'timeout'   => 0.01,
                'blocking'  => false,
            ),
            mailchimp_get_http_local_json_header()
        );
    }

    /**
     * Register all Mailchimp API routes.
     */
    public function register_routes()
    {
        $this->register_ping();
        $this->register_routes_for_queue();
        $this->register_survey_routes();
        $this->register_sync_stats();
    }

    /**
     * Ping
     */
    protected function register_ping()
    {
        register_rest_route(static::$namespace, '/ping', array(
            'methods' => 'GET',
            'callback' => array($this, 'ping'),
        ));
    }

    /**
     * Right now we only have a survey disconnect endpoint.
     */
    protected function register_survey_routes()
    {
        register_rest_route(static::$namespace, "/survey/disconnect", array(
            'methods' => 'POST',
            'callback' => array($this, 'post_disconnect_survey'),
        ));
    }

    /**
     * These are the routes for the queue and testing the functionality of the REST API during setup.
     */
    protected function register_routes_for_queue()
    {
        register_rest_route(static::$namespace, "/queue/work", array(
            'methods' => 'GET',
            'callback' => array($this, 'queue_work'),
        ));

        register_rest_route(static::$namespace, "/queue/work/force", array(
            'methods' => 'GET',
            'callback' => array($this, 'queue_work_force'),
        ));

        register_rest_route(static::$namespace, "/queue/stats", array(
            'methods' => 'GET',
            'callback' => array($this, 'queue_stats'),
        ));

        // if we have available jobs, it will handle async
        if ($this->maybe_fire_manually()) {
            static::work();
        }
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function ping(WP_REST_Request $request)
    {
        return mailchimp_rest_response(array('success' => true));
    }

    /**
     * Ping
     */
    protected function register_sync_stats()
    {
        if (current_user_can('editor') || current_user_can('administrator')) {
            register_rest_route(static::$namespace, '/sync/stats', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_sync_stats'),
            ));
        }
    }

    /**
     * @return WP_REST_Response
     */
    public function queue_stats()
    {
        return mailchimp_rest_response(array(
            'mailchimp_is_configured' => mailchimp_is_configured(),
            'queue_type' => mailchimp_running_in_console() ? 'console' : 'rest',
            'one_at_at_time' => mailchimp_queue_is_disabled(),
            'queue_is_running' => mailchimp_http_worker_is_running(),
            'should_init_queue' => mailchimp_should_init_rest_queue(),
            'jobs_in_queue' => number_format(MailChimp_WooCommerce_Queue::instance()->available_jobs()),
        ));
    }

    /**
     * This is the new HTTP queue handler - which should only fire when the rest API route has been called.
     * Replacing admin-ajax.php
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function queue_work(WP_REST_Request $request)
    {
        // if we're going to dispatch the manual request on this process, just return a "spawning" reason.
        if ($this->http_worker_listen === true) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'spawning'));
        }

        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (mailchimp_running_in_console()) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'cli enabled'));
        }

        // if the worker is already running - just respond with a reason of "running"
        if (mailchimp_http_worker_is_running()) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'running'));
        }

        // using the singleton - handle the jobs if we have things to do - will return a count
        $jobs_processed = MailChimp_WooCommerce_Rest_Queue::instance()->handle();

        // chances are this will never be returned to JS at all just because we're using a 0.01 second timeout
        // but we need to do it just in case.
        return mailchimp_rest_response(array('success' => true, 'processed' => $jobs_processed));
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function queue_work_force(WP_REST_Request $request)
    {
        // if we're going to dispatch the manual request on this process, just return a "spawning" reason.
        if ($this->http_worker_listen === true) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'spawning'));
        }

        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (mailchimp_running_in_console()) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'cli enabled'));
        }

        // reset the lock
        mailchimp_reset_http_lock();

        // using the singleton - handle the jobs if we have things to do - will return a count
        $jobs_processed = MailChimp_WooCommerce_Rest_Queue::instance()->handle();

        // chances are this will never be returned to JS at all just because we're using a 0.01 second timeout
        // but we need to do it just in case.
        return mailchimp_rest_response(array('success' => true, 'processed' => $jobs_processed));
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function post_disconnect_survey(WP_REST_Request $request)
    {
        // need to send a post request to
        $host = mailchimp_environment_variables()->environment === 'staging' ?
            'https://staging.conduit.vextras.com' : 'https://conduit.mailchimpapp.com';

        $route = "{$host}/survey/woocommerce";

        $result = wp_remote_post(esc_url_raw($route), array(
            'timeout'   => 12,
            'blocking'  => true,
            'method'      => 'POST',
            'data_format' => 'body',
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode($request->get_params()),
        ));

        return mailchimp_rest_response($result);
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_sync_stats(WP_REST_Request $request)
    {
        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
            return mailchimp_rest_response(array('success' => false, 'reason' => 'not configured'));
        }

        $store_id = mailchimp_get_store_id();
        $promo_rules_count = mailchimp_count_posts('shop_coupon');
        $product_count = mailchimp_get_product_count();
        $order_count = mailchimp_get_order_count();

        try {
            $promo_rules = $api->getPromoRules($store_id, 1, 1, 1);
            $mailchimp_total_promo_rules = $promo_rules['total_items'];
            if ($mailchimp_total_promo_rules > $promo_rules_count['publish']) $mailchimp_total_promo_rules = $promo_rules_count['publish'];
        } catch (\Exception $e) { $mailchimp_total_promo_rules = 0; }
        try {
            $products = $api->products($store_id, 1, 1);
            $mailchimp_total_products = $products['total_items'];
            if ($mailchimp_total_products > $product_count) $mailchimp_total_products = $product_count;
        } catch (\Exception $e) { $mailchimp_total_products = 0; }
        try {
            $orders = $api->orders($store_id, 1, 1);
            $mailchimp_total_orders = $orders['total_items'];
            if ($mailchimp_total_orders > $order_count) $mailchimp_total_orders = $order_count;
        } catch (\Exception $e) { $mailchimp_total_orders = 0; }

        $date = mailchimp_date_local('now');

        // but we need to do it just in case.
        return mailchimp_rest_response(array(
            'success' => true,
            'promo_rules_in_store' => (int) $promo_rules_count['publish'],
            'promo_rules_in_mailchimp' => $mailchimp_total_promo_rules,
            'products_in_store' => $product_count,
            'products_in_mailchimp' => $mailchimp_total_products,
            'orders_in_store' => $order_count,
            'orders_in_mailchimp' => $mailchimp_total_orders,
            'promo_rules_page' => get_option('mailchimp-woocommerce-sync.coupons.current_page'),
            'products_page' => get_option('mailchimp-woocommerce-sync.products.current_page'),
            'orders_page' => get_option('mailchimp-woocommerce-sync.orders.current_page'),
            'date' => $date->format( __('D, M j, Y g:i A', 'mc-woocommerce')),
            'has_started' => mailchimp_has_started_syncing(),
            'has_finished' => mailchimp_is_done_syncing(),
        ));
    }

    /**
     * @return bool
     */
    protected function maybe_fire_manually()
    {
        $transient = 'http_worker_queue_listen';
        $transient_expiration = 30;

        // if we're not running in the console, and the http_worker is not running
        if (mailchimp_should_init_rest_queue(false)) {
            try {
                // if we do not have a site transient for the queue listener
                if (!get_site_transient($transient)) {
                    // set the site transient to expire in X seconds so this will not happen too many times
                    // but still work for cron scripts on the minute mark.
                    set_site_transient($transient, microtime(), $transient_expiration);

                    // tell the site we're firing off a worker process now.
                    return $this->http_worker_listen = true;
                }
            } catch (\Exception $e) {
                mailchimp_error('maybe_fire_manually', mailchimp_error_trace($e, "maybe_fire_manually"));
            }
        }

        return false;
    }
}
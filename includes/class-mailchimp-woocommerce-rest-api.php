<?php

class MailChimp_WooCommerce_Rest_Api
{
    protected static $namespace = 'mailchimp-for-woocommerce/v1';

    /**
     * @param $path
     * @return string
     */
    public static function url($path)
    {
        return esc_url_raw(rest_url(static::$namespace.'/'.ltrim($path, '/')));
    }

    /**
     * @return mixed
     */
    public static function test()
    {
        return wp_remote_get(static::url('ping'), array(
            'timeout'   => 5,
            'blocking'  => true,
            'cookies'   => $_COOKIE,
            'sslverify' => apply_filters('https_local_ssl_verify', false)
        ));
    }

    /**
     * Call the "work" command manually to initiate the queue.
     *
     * @return mixed
     */
    public static function work()
    {
        // this is the new rest API version
        return wp_remote_get(static::url('queue/work'), array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'cookies'   => $_COOKIE,
            'sslverify' => apply_filters('https_local_ssl_verify', false)
        ));
    }

    /**
     * Register all Mailchimp API routes.
     */
    public function register_routes()
    {
        $this->register_ping();
        $this->register_routes_for_queue();
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
     * These are the routes for the queue and testing the functionality of the REST API during setup.
     */
    protected function register_routes_for_queue()
    {
        register_rest_route(static::$namespace, "/queue/work", array(
            'methods' => 'GET',
            'callback' => array($this, 'queue_work'),
        ));

        register_rest_route(static::$namespace, "/queue/stats", array(
            'methods' => 'GET',
            'callback' => array($this, 'queue_stats'),
        ));

        // this function can only be called after the rest routes have been registered.
        $this->fire_queue_for_fallback();
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
     * @return WP_REST_Response
     */
    public function queue_stats()
    {
        return mailchimp_rest_response(array(
            'mailchimp_is_configured' => mailchimp_is_configured(),
            'queue_type' => mailchimp_running_in_console() ? 'console' : 'rest',
            'one_at_at_time' => mailchimp_queue_is_disabled(),
            'queue_is_running' => mailchimp_http_worker_is_running(),
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
     *
     */
    protected function fire_queue_for_fallback()
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
                    // if we have available jobs, it will handle async
                    static::work();
                }
            } catch (\Exception $e) {
                mailchimp_error_trace($e, "loading dependencies");
            }
        }
    }
}
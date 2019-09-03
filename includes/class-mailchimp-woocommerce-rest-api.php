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
     * Register all Mailchimp API routes.
     */
    public function register_routes()
    {
        $this->register_ping();
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
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function ping(WP_REST_Request $request)
    {
        return $this->mailchimp_rest_response(array('success' => true));
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

        return $this->mailchimp_rest_response($result);
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_sync_stats(WP_REST_Request $request)
    {
        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
            return $this->mailchimp_rest_response(array('success' => false, 'reason' => 'not configured'));
        }

        $store_id = mailchimp_get_store_id();
        $promo_rules_count = mailchimp_count_posts('shop_coupon');
        $product_count = mailchimp_get_product_count();
        $order_count = mailchimp_get_order_count();

        try {
            $promo_rules = $api->getPromoRules($store_id, 1, 1, 1);
            $mailchimp_total_promo_rules = $promo_rules['total_items'];
            if (isset($promo_rules_count['publish']) && $mailchimp_total_promo_rules > $promo_rules_count['publish']) $mailchimp_total_promo_rules = $promo_rules_count['publish'];
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
        return $this->mailchimp_rest_response(array(
            'success' => true,
            'promo_rules_in_store' => isset($promo_rules_count['publish']) ? (int) $promo_rules_count['publish'] : 0,
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
     * @param array $data
     * @param int $status
     * @return WP_REST_Response
     */
    private function mailchimp_rest_response($data, $status = 200) {
        if (!is_array($data)) $data = array();
        $response = new WP_REST_Response($data);
        $response->set_status($status);
        return $response;
    }
}
<?php

class MailChimp_WooCommerce_Rest_Api
{
    protected static $namespace = 'mailchimp-for-woocommerce/v1';

	/**
	 * @param $path
	 *
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
        // ping
        register_rest_route(static::$namespace, '/ping', array(
            'methods' => 'GET',
            'callback' => array($this, 'ping'),
            'permission_callback' => '__return_true',
        ));

        // Right now we only have a survey disconnect endpoint.
        register_rest_route(static::$namespace, "/survey/disconnect", array(
            'methods' => 'POST',
            'callback' => array($this, 'post_disconnect_survey'),
            'permission_callback' => array($this, 'permission_callback'),
        ));

        // Sync Stats
        register_rest_route(static::$namespace, '/sync/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sync_stats'),
            'permission_callback' => array($this, 'permission_callback'),
        ));

        // remove review banner
        register_rest_route(static::$namespace, "/review-banner", array(
            'methods' => 'GET',
            'callback' => array($this, 'dismiss_review_banner'),
            'permission_callback' => array($this, 'permission_callback'),
        ));

        //Member Sync
        register_rest_route(static::$namespace, "/member-sync", array(
            'methods' => 'GET',
            'callback' => array($this, 'member_sync_alive_signal'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(static::$namespace, "/member-sync", array(
            'methods' => 'POST',
            'callback' => array($this, 'member_sync'),
            'permission_callback' => '__return_true',
        ));

        // Tower report
        register_rest_route(static::$namespace, "/tower/report", array(
            'methods' => 'POST',
            'callback' => array($this, 'get_tower_report'),
            'permission_callback' => '__return_true',
        ));

        // tower logs
        register_rest_route(static::$namespace, "/tower/logs", array(
            'methods' => 'POST',
            'callback' => array($this, 'get_tower_logs'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(static::$namespace, "/tower/resource", array(
            'methods' => 'POST',
            'callback' => array($this, 'get_tower_resource'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(static::$namespace, "/tower/action", array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_tower_action'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(static::$namespace, "/tower/sync_stats", array(
            'methods' => 'POST',
            'callback' => array($this, 'get_tower_sync_stats'),
            'permission_callback' => '__return_true',
        ));

    }

    /**
     * @return bool
     */
    public function permission_callback()
    {
        $cap = mailchimp_get_allowed_capability();
        return ($cap === 'manage_woocommerce' || $cap === 'manage_options' );
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function ping(WP_REST_Request $request)
    {
        return $this->mailchimp_rest_response(array('success' => true));
    }

	/**
	 * @param WP_REST_Request $request
	 *
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
	 *
	 * @return WP_REST_Response
	 */
    public function get_sync_stats(WP_REST_Request $request)
    {
        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
            return $this->mailchimp_rest_response(array('success' => false, 'reason' => 'not configured'));
        }

        $store_id = mailchimp_get_store_id();
        
        $complete = array(
            'coupons' => get_option('mailchimp-woocommerce-sync.coupons.completed_at'),
            'products' => get_option('mailchimp-woocommerce-sync.products.completed_at'),
            'orders' => get_option('mailchimp-woocommerce-sync.orders.completed_at')
        );

        $promo_rules_count = mailchimp_get_coupons_count();
        $product_count = mailchimp_get_product_count();
        $order_count = mailchimp_get_order_count();

        $mailchimp_total_promo_rules = $complete['coupons'] ? $promo_rules_count - mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_SingleCoupon') : 0;
        $mailchimp_total_products = $complete['products'] ? $product_count - mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Product') : 0;
        $mailchimp_total_orders = $complete['orders'] ? $order_count - mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Order') : 0;
        // try {
        //     $promo_rules = $api->getPromoRules($store_id, 1, 1, 1);
        //     $mailchimp_total_promo_rules = $promo_rules['total_items'];
        //     if (isset($promo_rules_count['publish']) && $mailchimp_total_promo_rules > $promo_rules_count['publish']) $mailchimp_total_promo_rules = $promo_rules_count['publish'];
        // } catch (Exception $e) { $mailchimp_total_promo_rules = 0; }
        // try {
        //     $products = $api->products($store_id, 1, 1);
        //     $mailchimp_total_products = $products['total_items'];
        //     if ($mailchimp_total_products > $product_count) $mailchimp_total_products = $product_count;
        // } catch (Exception $e) { $mailchimp_total_products = 0; }
        // try {
        //     $orders = $api->orders($store_id, 1, 1);
        //     $mailchimp_total_orders = $orders['total_items'];
        //     if ($mailchimp_total_orders > $order_count) $mailchimp_total_orders = $order_count;
        // } catch (Exception $e) { $mailchimp_total_orders = 0; }

        $date = mailchimp_date_local('now');
        // but we need to do it just in case.
        return $this->mailchimp_rest_response(array(
            'success' => true,
            'promo_rules_in_store' => $promo_rules_count,
            'promo_rules_in_mailchimp' => $mailchimp_total_promo_rules,
            
            'products_in_store' => $product_count,
            'products_in_mailchimp' => $mailchimp_total_products,
            
            'orders_in_store' => $order_count,
            'orders_in_mailchimp' => $mailchimp_total_orders,
            
            // 'promo_rules_page' => get_option('mailchimp-woocommerce-sync.coupons.current_page'),
            // 'products_page' => get_option('mailchimp-woocommerce-sync.products.current_page'),
            // 'orders_page' => get_option('mailchimp-woocommerce-sync.orders.current_page'),
            
            'date' => $date ? $date->format( __('D, M j, Y g:i A', 'mailchimp-for-woocommerce')) : '',
            'has_started' => mailchimp_has_started_syncing() || ($order_count != $mailchimp_total_orders),
            'has_finished' => mailchimp_is_done_syncing() && ($order_count == $mailchimp_total_orders),
	        'last_loop_at' => mailchimp_get_data('sync.last_loop_at'),
        ));
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function dismiss_review_banner(WP_REST_Request $request)
    {
        return $this->mailchimp_rest_response(array('success' => delete_option('mailchimp-woocommerce-sync.initial_sync')));
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function member_sync(WP_REST_Request $request)
    {
        $this->authorize('webhook.token', $request);
        $data = $request->get_params();
        if (!empty($data['type']) && !empty($data['data']['list_id']) && mailchimp_get_list_id() == $data['data']['list_id'] ){
            $job = new MailChimp_WooCommerce_Subscriber_Sync($data);
            $job->handle();
            return $this->mailchimp_rest_response(array('success' => true));
        }
        return $this->mailchimp_rest_response(array('success' => false));
    }

	/**
	 * Returns an alive signal to confirm url exists to mailchimp system
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function member_sync_alive_signal(WP_REST_Request $request)
    {
        $this->authorize('webhook.token', $request);
        return $this->mailchimp_rest_response(array('success' => true));
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function get_tower_report(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);
        return $this->mailchimp_rest_response(
            $this->tower($request->get_query_params())->handle()
        );
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 * @throws Throwable
	 */
    public function handle_tower_action(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);
        $body = $request->get_json_params();

        $action = isset($body['action']) ? $body['action'] : null;
        $data = isset($body['data']) ? $body['data'] : null;
        $response = null;

        if (empty($action)) {
            return $this->mailchimp_rest_response(array(
                'success' => false,
                'message' => 'invalid action'
            ));
        }

        switch ($action) {
            case 'emergency_stop_syncing':
                mailchimp_set_data('emergency_stop', true);
                $response = [
                    'title' => "Successfully stopped the sync.",
                    'description' => "Please note you'll need to have them reconnect.",
                    'type' => 'success',
                ];
                break;
            case 'update_feature':
                $response = [
                    'title' => "Features are not available for WooCommerce",
                    'type' => 'error',
                ];
                break;
            case 'resync_orders':
                MailChimp_WooCommerce_Process_Orders::push();
                $response = [
                    'title' => "Successfully initiated the order resync",
                    'description' => "Please note that it will take a couple minutes to start this process. Check the store logs for details.",
                    'type' => 'success',
                ];
                break;
            case 'resync_products':
                MailChimp_WooCommerce_Process_Products::push();
                $response = [
                    'title' => "Successfully initiated product resync",
                    'description' => "Please note that it will take a couple minutes to start this process. Check the store logs for details.",
                    'type' => 'success',
                ];
                break;
            case 'resync_customers':
                $response = [
                    'title' => "Customer resync",
                    'description' => "WooCommerce does not have customers to sync. Only orders.",
                    'type' => 'error',
                ];
                break;
            case 'resync_promo_codes':
                MailChimp_WooCommerce_Process_Coupons::push();
                $response = [
                    'title' => "Successfully initiated promo code resync",
                    'description' => "Please note that it will take a couple minutes to start this process. Check the store logs for details.",
                    'type' => 'success',
                ];
                break;
            case 'resync_chimpstatic_script':
                $response = [
                    'title' => "Chimpstatic script",
                    'description' => 'Scripts are automatically injected at runtime.',
                    'type' => 'error',
                ];
                break;
            case 'activate_webhooks':
                $api = mailchimp_get_api();
                $list = mailchimp_get_list_id();
                if (get_option('permalink_structure') === '') {
                    $response = [
                        'title' => "Store Webhooks",
                        'description' => "No store webhooks to apply",
                        'type' => 'error',
                    ];
                } else {
                    $previous_url = mailchimp_get_webhook_url();
                    if (mailchimp_get_data('webhook.token') && $previous_url && $api->hasWebhook($list, $previous_url)) {
                        $response = [
                            'title' => "Store Webhooks",
                            'description' => "Store already has webhooks enabled!",
                            'type' => 'success',
                        ];
                    } else {
                        $key = mailchimp_create_webhook_token();
                        $url = mailchimp_build_webhook_url($key);
                        mailchimp_set_data('webhook.token', $key);
                        try {
                            $webhook = $api->webHookSubscribe($list, $url);
                            mailchimp_set_webhook_url($webhook['url']);
                            mailchimp_log('webhooks', "added webhook to audience");
                            $response = [
                                'title' => "Store Webhooks",
                                'description' => "Set up a new webhook at {$webhook['url']}",
                                'type' => 'success',
                            ];
                        } catch (Exception $e) {
                            $response = [
                                'title' => "Store Webhooks",
                                'description' => $e->getMessage(),
                                'type' => 'error',
                            ];
                            mailchimp_set_data('webhook.token', false);
                            mailchimp_set_webhook_url(false);
                            mailchimp_error('webhook', $e->getMessage());
                        }
                    }
                }
                break;
            case 'resync_all':
                $service = new MailChimp_Service();
                $service->removePointers();
                MailChimp_WooCommerce_Admin::instance()->startSync();
                $service->setData('sync.config.resync', true);
                $response = [
                    'title' => "Successfully initiated the store resync",
                    'description' => "Please note that it will take a couple minutes to start this process. Check the store logs for details.",
                    'type' => 'success',
                ];
                break;
            case 'resync_customer':
                $response = [
                    'title' => "Error syncing custome",
                    'description' => "WooCommerce only works with orders.",
                    'type' => 'error',
                ];
                break;
            case 'resync_order':
                $order = new WC_Order($data['id']);
                if (!$order->get_date_created()) {
                    $response = [
                        'title' => "Error syncing order",
                        'description' => "This order id does not exist.",
                        'type' => 'error',
                    ];
                } else {
                    $job = new MailChimp_WooCommerce_Single_Order($order->get_id());
                    $data = $job->handle();
                    $response = [
                        'title' => "Executed order resync",
                        'description' => "Check the store logs for details.",
                        'type' => 'success',
                    ];
                }
                break;
            case 'resync_product':
                $product = new WC_Product($data['id']);
                if (!$product->get_date_created()) {
                    $response = [
                        'title' => "Error syncing product",
                        'description' => "This product id does not exist.",
                        'type' => 'error',
                    ];
                } else {
                    $job = new MailChimp_WooCommerce_Single_Product($product);
                    $data = $job->handle();
                    $response = [
                        'title' => "Executed product resync",
                        'description' => "Check the store logs for details.",
                        'type' => 'success',
                    ];
                }
                break;
            case 'resync_cart':
                $response = [
                    'title' => "Let's talk",
                    'description' => "This isn't supported by our system yet. If you really need this, please say something.",
                    'type' => 'error',
                ];
                break;
            case 'fix_duplicate_store':
                $job = new MailChimp_WooCommerce_Fix_Duplicate_Store(mailchimp_get_store_id(), true, false);
                $job->handle();
                $response = [
                    'title' => "Successfully queued up store deletion.",
                    'description' => "This process may take a couple minutes to complete. Please check back by reloading the page after a minute.",
                    'type' => 'success',
                ];
                break;
            case 'remove_legacy_app':
                $response = [
                    'title' => "Error removing legacy app",
                    'description' => "WooCommerce doesn't have any legacy apps to delete.",
                    'type' => 'error',
                ];
                break;
        }

        return $this->mailchimp_rest_response($response);
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function get_tower_logs(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);
        return $this->mailchimp_rest_response(
            $this->tower($request->get_query_params())->logs()
        );
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function get_tower_resource(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);
        $body = json_decode($request->get_body(), true);

        if (!isset($body['resource']) || !isset($body['resource_id'])) {
            return $this->mailchimp_rest_response(array(
                'resource' => null,
                'resource_error' => 'Resource not found because post request was wrong',
                'mailchimp' => null,
                'mailchimp_error' => 'Resource not found because post request was wrong',
            ));
        }

        $platform = null;
        $mc = null;
        $store_id = mailchimp_get_store_id();
        
        switch ($body['resource']) {
            case 'order':                
                $order = MailChimp_WooCommerce_HPOS::get_order($body['resource_id']);
                /*$order = get_post($body['resource_id']);*/
                $mc = !$order->get_id() ? null : mailchimp_get_api()->getStoreOrder($store_id, $order->get_id());
                if ($order->get_id()) {
                    $transformer = new MailChimp_WooCommerce_Transform_Orders();
                    $platform = $transformer->transform($order)->toArray();
                }
                if ($mc) $mc = $mc->toArray();
                break;
            case 'customer':
                //$body['resource_id'] = urldecode($body['resource_id']);
                $field = is_email($body['resource_id']) ? 'email' : 'id';
                $platform = get_user_by($field, $body['resource_id']);
	            $mc = array('member' => null, 'customer' => null);
                if ($platform) {
	                $date = mailchimp_get_marketing_status_updated_at($platform->ID);
                    $platform->mailchimp_woocommerce_is_subscribed = (bool) get_user_meta($platform->ID, 'mailchimp_woocommerce_is_subscribed', true);
	                $platform->marketing_status_updated_at = $date ? $date->format(__('D, M j, Y g:i A', 'mailchimp-for-woocommerce')) : '';
	                $hashed = mailchimp_hash_trim_lower($platform->user_email);
                } else if ('email' === $field) {
                    $hashed = mailchimp_hash_trim_lower($body['resource_id']);
                    $wc_customer = mailchimp_get_wc_customer($body['resource_id']);
                    if ( $wc_customer !== null ) {
                        $platform = $wc_customer;
                        $orders = wc_get_orders( array(
                            'customer' => $body['resource_id'],
                            'limit' => 1,
                            'orderby' => 'date',
                            'order' => 'DESC',
                        ) );
                        $date = $orders[0]->get_meta('marketing_status_updated_at');
                        $platform->mailchimp_woocommerce_is_subscribed = (bool) $orders[0]->get_meta('mailchimp_woocommerce_is_subscribed');
                        $platform->marketing_status_updated_at = $date ? $date->format(__('D, M j, Y g:i A', 'mailchimp-for-woocommerce')) : '';
                    }
                }
				if (isset($hashed) && $hashed) {
					try {
						$mc['member'] = mailchimp_get_api()->member(mailchimp_get_list_id(), $platform->user_email);
					} catch (Exception $e) {
						$mc['member'] = null;
					}
					if ($customer = mailchimp_get_api()->getCustomer($store_id, $hashed)) {
						$mc['customer'] = $customer->toArray();
					}
				}
                break;
            case 'product':                
                $platform = MailChimp_WooCommerce_HPOS::get_product($body['resource_id']);

                if ($platform) {
                    $transformer = new MailChimp_WooCommerce_Transform_Products();
                    $platform = $transformer->transform($platform)->toArray();
                }
                if ($mc = mailchimp_get_api()->getStoreProduct($store_id, $body['resource_id'])) {
                    $mc = $mc->toArray();
                }
                break;
            case 'cart':
                global $wpdb;
                $uid = mailchimp_hash_trim_lower($body['resource_id']);
                $table = "{$wpdb->prefix}mailchimp_carts";
                $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %s", $uid);
                $platform = $wpdb->get_row($sql);
                if ($mc = mailchimp_get_api()->getCart($store_id, $uid)) {
                    $mc = $mc->toArray();
                }
                break;
            case 'promo_code':
                $platform = new WC_Coupon($body['resource_id']);
	            $mc = mailchimp_get_api()->getPromoRuleWithCodes($store_id, $body['resource_id']);
                break;
        }

        return $this->mailchimp_rest_response(array(
            'resource' => $platform,
            'resource_error' => empty($platform) ? 'Resource not found' : false,
            'mailchimp' => $mc,
            'mailchimp_error' => empty($mc) ? 'Resource not found' : false,
        ));
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function get_tower_sync_stats(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);

        // if the queue is running in the console - we need to say tell the response why it's not going to fire this way.
        if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
            return $this->mailchimp_rest_response(array('success' => false, 'reason' => 'not configured'));
        }

        $store_id = mailchimp_get_store_id();
        $product_count = mailchimp_get_product_count();
        $order_count = mailchimp_get_order_count();

        try {
            $products = $api->products($store_id, 1, 1);
            $mailchimp_total_products = $products['total_items'];
            if ($mailchimp_total_products > $product_count) {
                $mailchimp_total_products = $product_count;
            }
        } catch (Exception $e) { $mailchimp_total_products = 0; }
        try {
            $mailchimp_total_customers = $api->getCustomerCount($store_id);
        } catch (Exception $e) { $mailchimp_total_customers = 0; }
        try {
            $orders = $api->orders($store_id, 1, 1);
            $mailchimp_total_orders = $orders['total_items'];
            if ($mailchimp_total_orders > $order_count) {
                $mailchimp_total_orders = $order_count;
            }
        } catch (Exception $e) { $mailchimp_total_orders = 0; }

        // but we need to do it just in case.
        return $this->mailchimp_rest_response(array(
            'platform' => array(
                'products' => $product_count,
                'customers' => 0,
                'orders' => $order_count,
            ),
            'mailchimp' => array(
                'products' => $mailchimp_total_products,
                'customers' => $mailchimp_total_customers,
                'orders' => $mailchimp_total_orders,
            ),
        ));
    }

	/**
	 * @param null $params
	 *
	 * @return MailChimp_WooCommerce_Tower
	 */
    private function tower($params = null)
    {
        if (!is_array($params)) $params = array();
        $job = new MailChimp_WooCommerce_Tower(mailchimp_get_store_id());
        $job->withLogFile(!empty($params['log_view']) ? $params['log_view'] : null);
        $job->withLogSearch(!empty($params['search']) ? $params['search'] : null);
        return $job;
    }

	/**
	 * @param $data
	 * @param int $status
	 *
	 * @return WP_REST_Response
	 */
    private function mailchimp_rest_response($data, $status = 200)
    {
        if (!is_array($data)) $data = array();
        $response = new WP_REST_Response($data);
        $response->set_status($status);
        return $response;
    }

	/**
	 * @param $key
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
    private function authorize($key, WP_REST_Request $request)
    {
        $allowed_keys = array(
            'tower.token',
            'webhook.token',
        );
        // this is just a safeguard against people trying to do wonky things.
        if (!in_array($key, $allowed_keys, true)) {
            wp_send_json_error(array('message' => 'unauthorized token type'), 403);
        }
        // get the auth token from either a header, or the query string
        $token = $this->getAuthToken($request);
        // pull the saved data
        $saved = mailchimp_get_data($key);

        // if we don't have a token - or we don't have the saved comparison
        // or the token doesn't equal the saved token, throw an error.
        if (empty($token) || empty($saved) || ($token !== $saved && base64_decode($token) !== $saved)) {
            wp_send_json_error(array('message' => 'unauthorized'), 403);
        }
        return true;
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return false|mixed|string
	 */
    private function getAuthToken(WP_REST_Request $request)
    {
        if (($token = $this->getBearerTokenHeader($request))) {
            return $token;
        }
        return $this->getAuthQueryStringParam($request);
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return false|string
	 */
    private function getBearerTokenHeader(WP_REST_Request $request)
    {
        $header = $request->get_header('Authorization');
        $position = strrpos($header, 'Bearer ');
        if ($position !== false) {
            $header = substr($header, $position + 7);
            return strpos($header, ',') !== false ?
                strstr(',', $header, true) :
                $header;
        }
        return false;
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return false|mixed
	 */
    private function getAuthQueryStringParam(WP_REST_Request $request)
    {
        $params = $request->get_query_params();
        return empty($params['auth']) ? false : $params['auth'];
    }
}
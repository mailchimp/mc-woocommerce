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

        // Tower verify connectivity
        register_rest_route(static::$namespace, "/tower/verify", array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_tower_connection'),
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

        register_rest_route(static::$namespace, "/tower/subscriber_stats", array(
            'methods' => 'POST',
            'callback' => array($this, 'get_local_count_by_status'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(static::$namespace, "/tower/toggle_remote_support", array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_remote_support'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(static::$namespace, "/tower/get_store_id", array(
            'methods' => 'GET',
            'callback' => array($this, 'get_store_id'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * @return bool
     */
    public function permission_callback()
    {
        $cap = mailchimp_get_allowed_capability();
        return ($cap === 'manage_woocommerce' || $cap === 'manage_options' || $cap === 'administrator');
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
    public function verify_tower_connection(WP_REST_Request $request)
    {
        $this->authorize('tower.token', $request);
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

        $promo_rules_count = mailchimp_get_coupons_count();
        $product_count = mailchimp_get_product_count();
        $order_count = mailchimp_get_order_count();
        $customer_count = mailchimp_get_customer_lookup_count();

        if (($internal = mailchimp_get_local_sync_counts())) {
            $mailchimp_total_promo_rules = $internal->coupons;
            $mailchimp_total_products = $internal->products;
            $mailchimp_total_orders = $internal->orders;
            $mailchimp_total_customers = $internal->customers;
        } else {
            try {
                $promo_rules = $api->getPromoRules($store_id, 1, 1, 1);
                $mailchimp_total_promo_rules = $promo_rules['total_items'];
                if (isset($promo_rules_count['publish']) && $mailchimp_total_promo_rules > $promo_rules_count['publish']) $mailchimp_total_promo_rules = $promo_rules_count['publish'];
            } catch (Exception $e) { $mailchimp_total_promo_rules = 0; }
            try {
                $mailchimp_total_products = $api->getProductCount($store_id);
            } catch (Exception $e) { $mailchimp_total_products = 0; }
            try {
                $mailchimp_total_orders = $api->getOrderCount($store_id);
            } catch (Exception $e) { $mailchimp_total_orders = 0; }

            try {
                $mailchimp_total_customers = $api->getCustomerCount($store_id);
            } catch (Exception $e) { $mailchimp_total_customers = 0; }
        }

        // fallback to make sure we're not over-counting somewhere.
        if ($mailchimp_total_products > $product_count) $mailchimp_total_products = $product_count;
        if ($mailchimp_total_orders > $order_count) $mailchimp_total_orders = $order_count;
        if ($mailchimp_total_customers > $customer_count) $mailchimp_total_customers = $customer_count;

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
            'customers_in_store' => $customer_count,
            'customers_in_mailchimp' => $mailchimp_total_customers,
            'date' => $date ? $date->format( __('D, M j, Y g:i A', 'mailchimp-for-woocommerce')) : '',
            'has_started' => mailchimp_has_started_syncing(),
            'has_finished' => mailchimp_is_done_syncing(),
	        'last_loop_at' => mailchimp_get_data('sync.last_loop_at'),
            'real' => $internal ?? null,
        ));
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_store_id(WP_REST_Request $request)
    {
        $this->authorizeWooToken($request);
        return $this->mailchimp_rest_response(array(
            'success' => true,
            'store_id' => mailchimp_get_store_id(),
        ));
    }

    public function toggle_remote_support(WP_REST_Request $request)
    {
        $this->authorizeWooToken($request);

        $body = $request->get_json_params();
        $toggle = isset($body['toggle']) ? $body['toggle'] : null;
        if (!is_bool($toggle)) {
            return $this->mailchimp_rest_response(array(
                'success' => false,
                'reason' => 'Toggle not defined. Must be a true/false value.'
            ), 401);
        }
        $tower = new MailChimp_WooCommerce_Tower(mailchimp_get_store_id());
        $result = $tower->toggle($toggle);
        if ( $result && isset($result->success) && $result->success) {
            \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-tower.opt', $toggle, 'yes');
            return $this->mailchimp_rest_response(array(
                'success' => true,
                'store_id' => mailchimp_get_store_id(),
                'message' => 'Enable report support.'
            ));
        }
        return $this->mailchimp_rest_response(array(
            'success' => false,
            'reason' => 'Could not enable remote support. Call the squad.'
        ), 401);
    }

    public function get_local_count_by_status(WP_REST_Request $request)
    {
        $this->authorizeWooToken($request);

        $list_id = mailchimp_get_list_id();
        if (empty($list_id)) {
            return $this->mailchimp_rest_response(array(
                'success' => false,
                'reason' => 'list id not configured'
            ));
        }

        $params = $request->get_params();
        $status = $params['status'] ?? 'subscribed';
        $allowed = array('transactional', 'subscribed', 'unsubscribed', 'pending');

        if (!in_array($status, $allowed, true)) {
            return $this->mailchimp_rest_response(array(
                'success' => false,
                'reason' => 'invalid status option'
            ));
        }

        try {
            switch ($status) {
                case 'transactional':
                    $count = mailchimp_get_api()->getTransactionalCount($list_id);
                    $meta_value = '0';
                    break;
                case 'subscribed':
                    $count = mailchimp_get_api()->getSubscribedCount($list_id);
                    $meta_value = '1';
                    break;
                case 'unsubscribed':
                    $count = mailchimp_get_api()->getUnsubscribedCount($list_id);
                    $meta_value = '0';
                    break;
                case 'pending':
                    $count = mailchimp_get_api()->getPendingCount($list_id);
                    $meta_value = 'pending';
                    break;
                default:
                    $meta_value = null;
                    $count = 0;
            }
        } catch (\Exception $e) {
            return $this->mailchimp_rest_response(array(
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ));
        }

        $args  = array(
            'meta_key' => 'mailchimp_woocommerce_is_subscribed',
            'meta_value' => $meta_value,
            'meta_compare' => '=',
        );

        $users = new WP_User_Query( $args );

        return $this->mailchimp_rest_response(array(
            'success' => true,
            'mailchimp' => $count,
            'platform' => $users->get_total(),
        ));
    }

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
    public function dismiss_review_banner(WP_REST_Request $request)
    {
        return $this->mailchimp_rest_response(array('success' => \Mailchimp_Woocommerce_DB_Helpers::delete_option('mailchimp-woocommerce-sync.initial_sync')));
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
                MailChimp_WooCommerce_Process_Customers::push();
                $response = [
                    'title' => "Customer resync",
                    'description' => "Please note that it will take a couple minutes to start this process. Check the store logs for details.",
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
	        case 'fix_is_syncing_problem':
		        $fixed = MailChimp_WooCommerce_Admin::instance()->fix_is_syncing_problem();
		        $response = $fixed ? [
			        'title' => "Successfully fixed sync flags.",
			        'description' => "Please reload the store stats to see updated meta",
			        'type' => 'success',
		        ] : [
			        'title' => "Sync flags not changed",
			        'description' => "There were no changes made.",
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
                $order = MailChimp_WooCommerce_HPOS::get_order_for_tower($body['resource_id']);
                if ($order && $order->get_order_number()) {
                    $mc = mailchimp_get_api()->getStoreOrder($store_id, $order->get_order_number());
                    $transformer = new MailChimp_WooCommerce_Transform_Orders();
                    $platform = $transformer->transform($order)->toArray();
                }
                if ($mc) $mc = $mc->toArray();
                break;
            case 'customer':
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
            case 'promo-code':
                $id = $body['resource_id'];
                $platform = wc_get_coupon_code_by_id($id);
                if (empty($platform)) {
                    $platform = wc_get_coupon_id_by_code($id);
                    if (empty($platform)) {
                        $id = $platform;
                    }
                }
	            try {
                    $mc = mailchimp_get_api()->getPromoRuleWithCodes($store_id, $id);
                } catch (\Exception $e) {

                }
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
        $tower = new MailChimp_WooCommerce_Tower($store_id);
        // but we need to do it just in case.
        return $this->mailchimp_rest_response($tower->formatEcommerceStats());
    }

	/**
	 * @param $args
	 *
	 * @return int
	 */
	private function get_customer_count( $args = array() ) {

		// default users per page
		$users_per_page = get_option( 'posts_per_page' );

		// Set base query arguments
		$query_args = array(
			'fields'  => 'ID',
			'role'    => 'customer',
			'orderby' => 'registered',
			'number'  => $users_per_page,
		);

		// Custom Role
		if ( ! empty( $args['role'] ) ) {
			$query_args['role'] = $args['role'];

			// Show users on all roles
			if ( 'all' === $query_args['role'] ) {
				unset( $query_args['role'] );
			}
		}

		// Search
		if ( ! empty( $args['q'] ) ) {
			$query_args['search'] = $args['q'];
		}

		// Limit number of users returned
		if ( ! empty( $args['limit'] ) ) {
			if ( -1 == $args['limit'] ) {
				unset( $query_args['number'] );
			} else {
				$query_args['number'] = absint( $args['limit'] );
				$users_per_page       = absint( $args['limit'] );
			}
		} else {
			$args['limit'] = $query_args['number'];
		}

		// Page
		$page = ( isset( $args['page'] ) ) ? absint( $args['page'] ) : 1;

		// Offset
		if ( ! empty( $args['offset'] ) ) {
			$query_args['offset'] = absint( $args['offset'] );
		} else {
			$query_args['offset'] = $users_per_page * ( $page - 1 );
		}

		// Order (ASC or DESC, ASC by default)
		if ( ! empty( $args['order'] ) ) {
			$query_args['order'] = $args['order'];
		}

		// Order by
		if ( ! empty( $args['orderby'] ) ) {
			$query_args['orderby'] = $args['orderby'];

			// Allow sorting by meta value
			if ( ! empty( $args['orderby_meta_key'] ) ) {
				$query_args['meta_key'] = $args['orderby_meta_key'];
			}
		}

		$query = new WP_User_Query( $query_args );

		return $query->get_total();
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
        // make sure the cache doesn't return something old.
        nocache_headers();
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
     * @return true
     */
    private function authorizeWooToken(WP_REST_Request $request)
    {
        global $wpdb;
        // get the auth token from either a header, or the query string
        $token = (string) $this->getAuthToken($request);
        // get the token and pull out both the consumer key and consumer secret split by the :
        $parts = str_getcsv($token, ':');
        // if we don't have 2 items, that's invalid
        if (count($parts) !== 2) {
            wp_send_json_error(array('message' => 'unauthorized'), 403);
        }
        list($key, $secret) = $parts;
        $consumer_key = wc_api_hash(sanitize_text_field($key));
        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE consumer_key = %s AND consumer_secret = %s", array($consumer_key, $secret));
        $api_key = $wpdb->get_row( $sql );
        if (empty($api_key)) {
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
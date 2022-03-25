<?php

class MailChimp_WooCommerce_Tower extends Mailchimp_Woocommerce_Job
{
    protected $store_id;
    protected $with_shop_sales = true;
    protected $with_log_file = null;
    protected $with_log_search = null;

    /**
     * OrderCreatedHook constructor.
     * @param $store_id
     */
    public function __construct($store_id)
    {
        $this->store_id = $store_id;
    }

    public function withoutShopSales()
    {
        $this->with_shop_sales = false;
        return $this;
    }

    public function withShopSales()
    {
        $this->with_shop_sales = true;
        return $this;
    }

    public function withLogFile($file)
    {
        $this->with_log_file = $file;
        return $this;
    }

    public function withLogSearch($search)
    {
        $this->with_log_search = $search;
        return $this;
    }

    /**
     * @return array
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_ServerError
     */
    public function handle()
    {
        return $this->getData();
    }

    /**
     * @return array
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_ServerError
     */
    public function getData()
    {
        $product_count = $customer_count = $order_count = $mc_product_count = $mc_customer_count = $mc_order_count = 0;

        $api = mailchimp_get_api();
        $store_id = mailchimp_get_store_id();
        $authenticated = mailchimp_is_configured();
        $list_id = mailchimp_get_list_id();
        $url = get_option('siteurl');
        $options = (array) get_option('mailchimp-woocommerce');

        try {
            $product_count = mailchimp_get_product_count();
            $customer_count = 0;
            $order_count = mailchimp_get_order_count();
            $plan = $plan_name = 'Woo';
            $store_active = true;
        } catch (\Throwable $e) {
            $store_active = false;
            $plan = null;
        }

        $has_mailchimp_script = false;
        $has_old_integration = false;
        $duplicate_store_problem = false;
        $store_attached = false;
        $syncing_mc = false;
        $list_is_valid = false;
        $account_info = [];
        $shop = null;

        if ($authenticated) {
            $account_info = $api->getProfile();
            if (is_array($account_info)) {
                // don't need these
                unset($account_info['_links']);
            }
            $stores = $api->stores();
            $compare_url = $this->baseDomain($url);
            $list_name = $list_id ? $api->getList($list_id)['name'] : null;

            if (is_array($stores) && !empty($stores)) {
                foreach ($stores as $mc_store) {
                    $store_url = $this->baseDomain($mc_store->getDomain());
                    $public_key_matched = $mc_store->getId() === $store_id;
                    // make sure the current store in context is inside the Mailchimp array of stores.
                    if ($public_key_matched) {
                        $shop = $mc_store;
                        $syncing_mc = $mc_store->isSyncing();
                        $store_attached = true;
                        $list_is_valid = $mc_store->getListId() === $list_id;
                        $has_mailchimp_script = (bool) $mc_store->getConnectedSiteScriptFragment();
                    }
                    if ($store_url === $compare_url) {
                        if (!$public_key_matched && $mc_store->getPlatform() === 'Woocommerce') {
                            $duplicate_store_problem = true;
                        }
                    }
                }
            }

            try {
                if ($store_attached) {
                    $mc_product_count = $api->getProductCount($store_id);
                    $mc_customer_count = $api->getCustomerCount($store_id);
                    $mc_order_count = $api->getOrderCount($store_id);
                }
            } catch (\Throwable $e) {

            }
        }

        $time = new \DateTime('now');

        return [
            'store' => (object) array(
                'public_key' => $store_id,
                'domain' => $url,
                'secure_url' => $url,
                'user' => (object) array(
                    'email' => isset($options['admin_email']) ? $options['admin_email'] : null,
                ),
                'address' => (object) array(
                    'street' => isset($options['store_street']) && $options['store_street'] ? $options['store_street'] : '',
                    'city' => isset($options['store_street']) && $options['store_street'] ? $options['store_street'] : '',
                    'state' => isset($options['store_state']) && $options['store_state'] ? $options['store_state'] : '',
                    'country' => isset($options['store_country']) && $options['store_country'] ? $options['store_country'] : '',
                    'zip' => isset($options['store_postal_code']) && $options['store_postal_code'] ? $options['store_postal_code'] : '',
                    'phone' => isset($options['store_phone']) && $options['store_phone'] ? $options['store_phone'] : '',
                ),
                'metrics' => array_values([
                    'shopify_hooks' => (object) array('key' => 'shopify_hooks', 'value' => true),
                    'shop.products' => (object) array('key' => 'shop.products', 'value' => $product_count),
                    'shop.customers' => (object) array('key' => 'shop.customers', 'value' => $customer_count),
                    'shop.orders' => (object) array('key' => 'shop.orders', 'value' => $order_count),
                    'mc.products' => (object) array('key' => 'mc.products', 'value' => $mc_product_count),
                    'mc.orders' => (object) array('key' => 'mc.orders', 'value' => $mc_order_count),
                    'mc.has_chimpstatic' => (object) array('key' => 'mc.has_chimpstatic', 'value' => true),
                    'mc.has_duplicate_store' => (object) array('key' => 'mc.has_duplicate_store', 'value' => $duplicate_store_problem),
                    'mc.store_attached' => (object) array('key' => 'mc.store_attached', 'value' => $store_attached),
                    'mc.is_syncing' => (object) array('key' => 'mc.is_syncing', 'value' => $syncing_mc),
                    'mailchimp_api_connected' => (object) array('key' => 'mailchimp_api_connected', 'value' => (bool) $account_info),
                    'mc_list_id' => (object) array('key' => 'mc_list_id', 'value' => (bool) $list_id && $list_is_valid),
                    'mc_list_valid' => (object) array('key' => 'mc_list_valid', 'value' => $list_is_valid),
                    'mc.has_legacy_integration' => (object) array('key' => 'mc.has_legacy_integration', 'value' => $has_old_integration),
                    'admin.updated_at' => (object) array('key' => 'admin.updated_at', 'value' => $time->format('Y-m-d H:i:s')),
                    'product_sync_started' => (object) array('key' => 'product_sync_started', 'value' => get_option('mailchimp-woocommerce-sync.products.started_at')),
                    'product_sync_completed' => (object) array('key' => 'product_sync_completed', 'value' => get_option('mailchimp-woocommerce-sync.products.completed_at')),
                    'customer_sync_started' => (object) array('key' => 'customer_sync_started', 'value' => get_option('mailchimp-woocommerce-sync.customers.started_at')),
                    'customer_sync_completed' => (object) array('key' => 'customer_sync_completed', 'value' => get_option('mailchimp-woocommerce-sync.customers.completed_at')),
                    'order_sync_started' => (object) array('key' => 'order_sync_started', 'value' => get_option('mailchimp-woocommerce-sync.orders.started_at')),
                    'order_sync_completed' => (object) array('key' => 'order_sync_completed', 'value' => get_option('mailchimp-woocommerce-sync.orders.completed_at')),
                ]),
                'meta' => $this->getMeta(),
            ),
            'meta' => [
                'timestamp' => $time->format('Y-m-d H:i:s'),
                'platform' => [
                    'active' => $store_active,
                    'plan' => $plan,
                    'store_name' => get_option('blogname'),
                    'domain' => $url,
                    'secure_url' => $url,
                    'user_email' => isset($options['admin_email']) ? $options['admin_email'] : null,
                    'is_syncing' => $syncing_mc,
                    'sync_started_at' => get_option('mailchimp-woocommerce-sync.started_at'),
                    'sync_completed_at' => get_option('mailchimp-woocommerce-sync.completed_at'),
                    'subscribed_to_hooks' => true,
                    'uses_custom_rules' => false,
                    'ecomm_stats' => [
                        'products' => $product_count,
                        'customers' => $customer_count,
                        'orders' => $order_count,
                    ],
                    'shop' => [
                        'phone' => isset($options['store_phone']) && $options['store_phone'] ? $options['store_phone'] : '',
                    ],
                    'shop_sales' => $this->getShopSales(),
                ],
                'mailchimp' => [
                    'shop' => $shop ? $shop->toArray() : false,
                    'chimpstatic_installed' => $has_mailchimp_script,
                    'force_disconnect' => false,
                    'duplicate_store_problem' => $duplicate_store_problem,
                    'has_old_integration' => $has_old_integration,
                    'store_attached' => $store_attached,
                    'ecomm_stats' => [
                        'products' => $mc_product_count,
                        'customers' => $mc_customer_count,
                        'orders' => $mc_order_count,
                    ],
                    'list' => [
                        'id' => $list_id,
                        'name' => $list_name,
                        'double_opt_in' => mailchimp_list_has_double_optin(false),
                        'valid' => $list_is_valid,
                    ],
                    'account_info' => $account_info,
                ],
                'merge_tags' => [

                ]
            ],
            'logs' => static::logs($this->with_log_file, $this->with_log_search),
            'system_report' => $this->getSystemReport(),
        ];
    }

    /**
     * @param $domain
     * @return string|string[]
     */
    protected function baseDomain($domain)
    {
        return str_replace(
            ['http://', 'https://', 'www.'],
            '',
            rtrim(strtolower(trim($domain)), '/')
        );
    }

    /**
     * @param null $file
     * @param null $search
     * @return array
     */
    public function logs($file = null, $search = null)
    {
        $logs = new MailChimp_WooCommerce_Logs();
        $logs->limit(200);
        $logs->withView(!is_null($file) ? $file : $this->with_log_file);
        $logs->searching(!is_null($search) ? $search : $this->with_log_search);
        return $logs->handle();
    }

    /**
     * @return mixed
     */
    public function getShopSales()
    {
        global $wpdb;
        $statuses = implode( "','", apply_filters('woocommerce_reports_order_statuses', array(
            'completed',
            'processing',
            'on-hold'
        )));
        $result = $wpdb->get_var("
	SELECT SUM( order_item_meta.meta_value )
	FROM {$wpdb->prefix}woocommerce_order_items as order_items
	LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
	LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	WHERE 	term.slug IN ('" . $statuses . "')
	AND 	posts.post_status 	= 'publish'
	AND 	tax.taxonomy		= 'shop_order_status'
	AND 	order_items.order_item_type = 'line_item'
	AND 	order_item_meta.meta_key = '_qty'
");
        return apply_filters( 'woocommerce_reports_sales_overview_order_items', absint($result));
    }

    public function getSystemReport()
    {
        global $wp_version;

        $actions = $this->getLastActions();

        return array(
            array('key' => 'PhpVersion', 'value' => phpversion()),
            array('key' => 'Curl Enabled', 'value' => function_exists('curl_init')),
            array('key' => 'Curl Version', 'value' => $this->getCurlVersion()),
            array('key' => 'Wordpress Version', 'value' => $wp_version),
            array('key' => 'WooCommerce Version', 'value' => defined('WC_VERSION') ? WC_VERSION : null),
            array('key' => 'Active Plugins', 'value' => $this->getActivePlugins()),
            array('key' => 'Actions', 'value' => $actions),
        );
    }

    public function getCurlVersion()
    {
        $version = function_exists('curl_version') ? curl_version() : null;
        return is_array($version) ? $version['version'] : null;
    }

    public function getActivePlugins()
    {
        $active_plugins = "<ul>";
        $plugins = wp_get_active_and_valid_plugins();
        foreach ($plugins as $plugin) {
            $plugin_data = get_plugin_data($plugin);
            $active_plugins .= '<li><span class="font-bold">'.$plugin_data['Name'].'</span>: '.$plugin_data['Version'].'</li>';
        }
        $active_plugins .= "</ul>";
        return print_r($active_plugins, true);
    }

    public function getLastActions()
    {
        global $wpdb;
        if (!class_exists('ActionScheduler') || !ActionScheduler::is_initialized( 'store' ) ) {
            return array();
        }
        if (!ActionScheduler::store()) {
            return array();
        }
        $oldest_and_newest = '<ul>';

        foreach (array_keys(ActionScheduler::store()->get_status_labels()) as $status) {
            if ('in-progress' === $status) {
                continue;
            }
            $newest = $this->get_action_status_date($status, 'newest' );
            $status = ucfirst($status);
            $oldest_and_newest .= "<li><span class='font-bold'>{$status}</span>: {$newest}</li>";
        }

        $oldest_and_newest .= '</ul>';

        return $oldest_and_newest;
    }

    /**
     * @return array|object|null
     */
    public function getMeta()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE 'mailchimp-woocommerce-%'");
        $response = array();
        $date = new \DateTime('now');
        foreach ($results as $result) {
            $response[] = array(
                'key' => str_replace('mailchimp-woocommerce-', '', $result->option_name),
                'value' => $result->option_value,
                'updated_at' => $date->format('Y-m-d H:i:s'),
            );
        }
        return $response;
    }

    /**
     * This is where we need to hook into tower from the store owner's support request.
     * We can enable and disable this feature which will generate an API token specific to
     * tower which will be used for authentication coming from our server to this specific store.
     *
     * @param bool $enable
     * @return array|mixed|object|null
     */
    public function toggle($enable = true)
    {
        $command = (bool) $enable ? 'enable' : 'disable';
        $store_id = mailchimp_get_store_id();
        $key = mailchimp_get_api_key();
        $list_id = mailchimp_get_list_id();
        $post_url = "https://tower.vextras.com/admin-api/woocommerce/{$command}/{$store_id}";

        if ((bool) $enable) {
            mailchimp_set_data('tower.token', $support_token = wp_generate_password());
        } else {
            $support_token = mailchimp_get_data('tower.token');
            delete_option('mailchimp-woocommerce-tower.support_token');
        }

        try {
            $payload = array(
                'headers' => array(
                    'Content-type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Store-Platform' => 'woocommerce',
                    'X-List-Id' => $list_id,
                    'X-Store-Key' => base64_encode("{$store_id}:{$key}"),
                ),
                'body' => json_encode(array(
                    'support_token' => $support_token,
                    'domain' => get_option('siteurl'),
                    'data' => array(
                        'list_id' => $list_id,
                        'is_connected' => mailchimp_is_configured(),
                        'rest_url' => MailChimp_WooCommerce_Rest_Api::url(''),
                    ),
                )),
                'timeout'     => 30,
            );
            $response = wp_remote_post($post_url, $payload);
            mailchimp_log('tower', 'trace', $response);
            return json_decode($response['body']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get oldest or newest scheduled date for a given status.
     *
     * @param string $status Action status label/name string.
     * @param string $date_type Oldest or Newest.
     * @return DateTime
     */
    protected function get_action_status_date( $status, $date_type = 'oldest' )
    {
        $order = 'oldest' === $date_type ? 'ASC' : 'DESC';
        $store = ActionScheduler::store();
        $action = $store->query_actions(
            array(
                'claimed'  => false,
                'status'   => $status,
                'per_page' => 1,
                'order'    => $order,
            )
        );
        if ( ! empty( $action ) ) {
            $date_object = $store->get_date( $action[0] );
            $action_date = $date_object->format( 'Y-m-d H:i:s O' );
        } else {
            $action_date = '&ndash;';
        }
        return $action_date;
    }
}

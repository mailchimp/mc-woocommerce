<?php

/**
 * Mailchimp Pixel Tracking Integration
 *
 * Tracks WooCommerce e-commerce events and sends them to the Mailchimp Pixel SDK.
 * Follows the same architectural pattern as the WooCommerce Google Analytics integration.
 *
 * @link       https://mailchimp.com
 * @since      1.0.0
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/includes/tracking
 */

/**
 * Mailchimp Pixel Tracking Class
 *
 * Hooks into WooCommerce events and prepares data for the Mailchimp Pixel SDK.
 * Data is stored in $script_data and output to window.mcPixel.data in the footer.
 *
 * Events supported:
 * PRODUCT_VIEWED
 * CART_VIEWED
 * CHECKOUT_STARTED
 * PURCHASED
 * PRODUCT_CATEGORY_VIEWED
 * SEARCH_SUBMITTED
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/includes/tracking
 */
class MailChimp_WooCommerce_Pixel_Tracking
{

    /**
     * Singleton instance
     *
     * @var MailChimp_WooCommerce_Pixel_Tracking|null
     */
    protected static $_instance = null;

    protected $track_on_next_page_load = false;

    /**
     * Script data storage
     *
     * @var array
     */
    protected $script_data = array();

    /**
     * Get singleton instance
     *
     * @return MailChimp_WooCommerce_Pixel_Tracking
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor - Register all hooks
     */
    public function __construct()
    {
        $this->track_on_next_page_load = (bool) apply_filters('mailchimp_woocommerce_track_on_next_page_load', true);
        $this->attach_event_data();
    }

    /**
     * Attach event data hooks
     *
     * Hooks into WooCommerce actions/filters to capture event data
     */
    protected function attach_event_data()
    {
        // Product detail page - single product viewed
        add_action('woocommerce_after_single_product', array( $this, 'track_product_view' ));

        // Add to cart (non-AJAX) - store product data for added_to_cart event
        add_action('woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6);

        // woo cart item removed
        add_action('woocommerce_cart_item_removed', array( $this, 'track_remove_from_cart'), 10, 2);

        // Cart quantity updated (e.g. incrementing quantity on cart page / block cart)
        add_action('woocommerce_after_cart_item_quantity_update', array( $this, 'track_quantity_update' ), 10, 4);

        // Product loop - pre-load all products on listing pages for AJAX add to cart
        add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'preload_listing_products' ), 10, 2);

        // Cart page view
        add_action('wp', array( $this, 'track_cart_view' ));

        // Checkout page - shortcode-based
        add_action('woocommerce_before_checkout_form', array( $this, 'track_checkout_started' ));

        // Checkout page - block-based (detect via has_block on wp action)
        add_action('wp', array( $this, 'track_checkout_started_block' ));

        // Order complete / Purchase
        add_action('woocommerce_thankyou', array( $this, 'track_purchase' ));

        // Category/archive pages - use wp hook for universal support (blocks + shortcodes)
        add_action('wp', array( $this, 'track_category_view' ));

        // to handle ajax'y calls
        if ($this->track_on_next_page_load) {
            add_action('wp', [$this, 'hydrate_script_data_from_session'], 5);
        }

        // Search
        add_action('pre_get_posts', array( $this, 'track_search' ));

        // user email from standard checkout
        add_action('wp_ajax_mailchimp_set_user_by_email', array( $this, 'set_user_by_email'));
        add_action('wp_ajax_nopriv_mailchimp_set_user_by_email', array( $this, 'set_user_by_email'));
    }

    public function set_user_by_email()
    {
        if ($this->doingAjax() && isset($_POST['email']) && ! empty($_POST['email'])) {
            $this->track_identity($_POST['email']);
        }
    }

    public function track_identity($email)
    {
        if (empty($email)) {
            return;
        }
        $email = trim(str_replace(' ','+', $email));
        if (is_email($email)) {
            $this->append_script_data('identity', array(
                'email' => $email,
            ));
            $this->append_script_data('events', 'IDENTITY');
        }
    }

    /**
     * Track product view on single product page
     */
    public function track_product_view()
    {
        global $product;
        if ($product && is_product()) {
            $this->append_script_data('product', $this->get_formatted_product($product));
            $this->append_script_data('events', 'PRODUCT_VIEWED');
        }
    }

    /**
     * Track add to cart event
     *
     * @param string $cart_item_key Cart item key
     * @param int    $product_id Product ID
     * @param int    $quantity Quantity added
     * @param int    $variation_id Variation ID
     * @param array  $variation Variation data
     * @param array  $cart_item_data Cart item data
     */
    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $product = wc_get_product($variation_id ? $variation_id : $product_id);
        if ($product) {
            $this->append_script_data('events', 'PRODUCT_ADDED_TO_CART');
            $this->append_script_data('added_to_cart', $payload = $this->get_formatted_product($product, $quantity));
            // Make it available to AJAX response — queue supports multiple adds
            if (WC()->session) {
                if ($this->track_on_next_page_load) {
                    $this->track_cart_on_next_page_load($payload);
                }
                $queue = WC()->session->get('mc_pixel_atc_queue', []);
                $queue[] = $payload;
                WC()->session->set('mc_pixel_atc_queue', $queue);
            }
        }
    }

    /**
     * Not using this right now but we might - it's a "next page" event.
     * @param $payload
     * @return void
     */
    protected function track_cart_on_next_page_load($payload)
    {
        if (WC()->session) {
            // Queue for next page view
            $queued = WC()->session->get('mc_pixel_queued', [
                'events' => [],
            ]);
            if (!in_array('PRODUCT_ADDED_TO_CART', $queued['events'], true)) {
                $queued['events'][] = 'PRODUCT_ADDED_TO_CART';
            }
            if (!isset($queued['added_to_cart'])) {
                $queued['added_to_cart'] = [];
            }
            $queued['added_to_cart'][] = $payload;
            WC()->session->set('mc_pixel_queued', $queued);
        }
    }

    /**
     * The rest route is registered in class-mailchimp-woocommerce-rest-api.php
     * Returns all queued add-to-cart payloads and clears the queue.
     *
     * @return mixed|WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_last_added_to_cart_from_session()
    {
        if (!WC()->session) return rest_ensure_response(null);
        $queue = WC()->session->get('mc_pixel_atc_queue', []);
        WC()->session->__unset('mc_pixel_atc_queue');
        // Also clear the next-page-load queue for add-to-cart events
        // so hydrate_script_data_from_session doesn't re-fire them.
        $this->clear_queued_events('added_to_cart', 'PRODUCT_ADDED_TO_CART');
        if (empty($queue)) return rest_ensure_response(null);
        return rest_ensure_response($queue);
    }

    /**
     * The rest route is registered in class-mailchimp-woocommerce-rest-api.php
     * Returns all queued remove-from-cart payloads and clears the queue.
     *
     * @return mixed|WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_last_removed_from_cart_from_session()
    {
        if (! WC()->session) return rest_ensure_response(null);
        $queue = WC()->session->get('mc_pixel_rfc_queue', []);
        WC()->session->__unset('mc_pixel_rfc_queue');
        // Also clear the next-page-load queue for remove-from-cart events
        $this->clear_queued_events('removed_from_cart', 'PRODUCT_REMOVED_FROM_CART');
        if (empty($queue)) return rest_ensure_response(null);
        return rest_ensure_response($queue);
    }

    /**
     * Remove a specific event type and its payload from the next-page-load queue.
     * Called when the REST endpoint has already handled the events via AJAX,
     * so hydrate_script_data_from_session doesn't re-fire them.
     *
     * @param string $payload_key The data key (e.g. 'added_to_cart', 'removed_from_cart')
     * @param string $event_name  The event name (e.g. 'PRODUCT_ADDED_TO_CART')
     */
    protected function clear_queued_events($payload_key, $event_name)
    {
        if (!WC()->session) return;

        $queued = WC()->session->get('mc_pixel_queued');
        if (empty($queued)) return;

        unset($queued[$payload_key]);

        if (!empty($queued['events'])) {
            $queued['events'] = array_values(array_diff($queued['events'], [$event_name]));
        }

        if (empty($queued['events'])) {
            WC()->session->__unset('mc_pixel_queued');
        } else {
            WC()->session->set('mc_pixel_queued', $queued);
        }
    }

    public function track_remove_from_cart($cart_item_key, $cart) {
        if (! WC()->session) return;

        // Woo stores removed item data here after removal.
        $removed = $cart->removed_cart_contents[ $cart_item_key ] ?? null;
        if (! $removed) return;

        $product_id   = $removed['product_id'] ?? 0;
        $variation_id = $removed['variation_id'] ?? 0;
        $qty          = (int) ($removed['quantity'] ?? 1);

        $pid = $variation_id ? $variation_id : $product_id;
        $product = wc_get_product($pid);
        if (! $product) return;

        $payload = $this->get_formatted_product($product, $qty);

        $this->append_script_data('events', 'PRODUCT_REMOVED_FROM_CART');
        $this->append_script_data('removed_from_cart', $payload);

        // Queue for AJAX REST endpoint — supports multiple removes
        $queue = WC()->session->get('mc_pixel_rfc_queue', []);
        $queue[] = $payload;
        WC()->session->set('mc_pixel_rfc_queue', $queue);

        // Queue for next page load (traditional POST redirect)
        if ($this->track_on_next_page_load) {
            $queued = WC()->session->get('mc_pixel_queued', ['events' => []]);
            if (!in_array('PRODUCT_REMOVED_FROM_CART', $queued['events'], true)) {
                $queued['events'][] = 'PRODUCT_REMOVED_FROM_CART';
            }
            if (!isset($queued['removed_from_cart'])) {
                $queued['removed_from_cart'] = [];
            }
            $queued['removed_from_cart'][] = $payload;
            WC()->session->set('mc_pixel_queued', $queued);
        }
    }

    /**
     * Track cart item quantity update (e.g. user increments qty on cart page).
     * Treated as an add-to-cart event so the pixel fires for the updated quantity.
     *
     * @param string $cart_item_key Cart item key
     * @param int    $quantity New quantity
     * @param int    $old_quantity Previous quantity
     * @param array  $cart Cart instance
     */
    public function track_quantity_update($cart_item_key, $quantity, $old_quantity, $cart)
    {
        if (! WC()->session) return;
        if ($quantity <= $old_quantity) return; // only track increases

        $cart_item = $cart->get_cart_item($cart_item_key);
        if (! $cart_item) return;

        $product_id   = $cart_item['product_id'] ?? 0;
        $variation_id = $cart_item['variation_id'] ?? 0;

        $pid = $variation_id ? $variation_id : $product_id;
        $product = wc_get_product($pid);
        if (! $product) return;

        $added_qty = $quantity - $old_quantity;
        $payload = $this->get_formatted_product($product, $added_qty);

        $this->append_script_data('events', 'PRODUCT_ADDED_TO_CART');
        $this->append_script_data('added_to_cart', $payload);

        // Queue for AJAX REST endpoint
        $queue = WC()->session->get('mc_pixel_atc_queue', []);
        $queue[] = $payload;
        WC()->session->set('mc_pixel_atc_queue', $queue);

        // Queue for next page load
        if ($this->track_on_next_page_load) {
            $this->track_cart_on_next_page_load($payload);
        }
    }

    public function hydrate_script_data_from_session()
    {
        if (!WC()->session) return;

        $queued = WC()->session->get('mc_pixel_queued');
        if (empty($queued)) return;

        // Merge into your existing script_data structure
        if (!empty($queued['events'])) {
            foreach ($queued['events'] as $evt) {
                $this->append_script_data('events', $evt);
            }
        }

        if (!empty($queued['added_to_cart'])) {
            // added_to_cart is now an array of payloads
            foreach ((array) $queued['added_to_cart'] as $item) {
                $this->append_script_data('added_to_cart', $item);
            }
        }

        if (!empty($queued['removed_from_cart'])) {
            // removed_from_cart is now an array of payloads
            foreach ((array) $queued['removed_from_cart'] as $item) {
                $this->append_script_data('removed_from_cart', $item);
            }
        }

        // Clear all queues — the page-load path is handling these events now,
        // so wipe the REST endpoint queues to prevent the JS fetch from
        // sending the same events a second time.
        WC()->session->__unset('mc_pixel_queued');
        WC()->session->__unset('mc_pixel_atc_queue');
        WC()->session->__unset('mc_pixel_rfc_queue');
    }

    /**
     * Pre-load products on listing pages (for AJAX add to cart)
     *
     * @param  string     $button Add to cart button HTML
     * @param  WC_Product $product Product object
     * @return string Button HTML (unchanged)
     */
    public function preload_listing_products($button, $product)
    {
        $this->append_script_data('products', $this->get_formatted_product($product));
        return $button;
    }

    /**
     * Track cart view
     */
    public function track_cart_view()
    {
        if (is_cart() && WC()->cart && ! WC()->cart->is_empty()) {
            $cart_data = $this->get_formatted_cart();
            $this->append_script_data('cart', $cart_data);
            $this->append_script_data('events', 'CART_VIEWED');
        }
    }

    /**
     * Track checkout started
     */
    public function track_checkout_started()
    {
        if (! WC()->cart->is_empty()) {
            $checkout_data = $this->get_formatted_checkout();
            $this->append_script_data('checkout', $checkout_data);
            $this->append_script_data('events', 'CHECKOUT_STARTED');
            if (($customer = WC()->cart->get_customer())) {
                if ($customer->get_email()) {
                    $this->track_identity($customer->get_email());
                } else if ($customer->get_billing_email()) {
                    $this->track_identity($customer->get_billing_email());
                }
            }
        }
    }

    /**
     * Track purchase/order completion
     *
     * @param int $order_id Order ID
     */
    public function track_purchase($order_id)
    {
        if (! $order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        // hook the identity in here
        mailchimp_log('identity', 'tracking identity through order purchased hook', $order->get_billing_email());
        $this->track_identity($order->get_billing_email());

        $order_data = $this->get_formatted_order($order);

        $this->append_script_data('order', $order_data);
        $this->append_script_data('events', 'PURCHASED');
    }

    /**
     * Track category view
     */
    public function track_category_view()
    {
        if (is_product_category() || is_shop()) {
            $category_data = array();

            if (is_product_category()) {
                $term = get_queried_object();
                if ($term) {
                    $category_data = array(
                        'categoryId'   => $term->term_id,
                        'categoryName' => $term->name,
                    );
                }
            } elseif (is_shop()) {
                $category_data = array(
                    'categoryId'   => 'shop',
                    'categoryName' => 'Shop',
                );
            }

            if (! empty($category_data)) {
                $this->append_script_data('category', $category_data);
                $this->append_script_data('events', 'PRODUCT_CATEGORY_VIEWED');
            }
        }
    }

    /**
     * Track search
     *
     * @param WP_Query $query WordPress query object
     */
    public function track_search($query)
    {
        if (! is_admin() && $query->is_main_query() && $query->is_search() && isset($query->query_vars['s'])) {
            $search_query = $query->query_vars['s'];
            $search_data  = array(
                'query'        => $search_query,
                'resultsCount' => $query->found_posts,
            );
            $this->append_script_data('search', $search_data);
            $this->append_script_data('events', 'SEARCH_SUBMITTED');
        }
    }

    /**
     * Format product for Pixel SDK
     *
     * @param  WC_Product $product Product object
     * @param  int        $quantity Quantity (optional)
     * @return array Formatted product data
     */
    protected function get_formatted_product($product, $quantity = null)
    {
        $parent_id = $product->get_parent_id();
        $image_id  = $product->get_image_id();

        $formatted = array(
            'id'         => (string) $product->get_id(),
            'productId'  => (string) ( $parent_id ? $parent_id : $product->get_id() ),
            'title'      => $product->get_name(),
            'price'      => (float) $product->get_price(),
            'currency'   => get_woocommerce_currency(),
            'sku'        => $product->get_sku() ? $product->get_sku() : '',
            'imageUrl'   => $image_id ? wp_get_attachment_url($image_id) : '',
            'productUrl' => get_permalink($product->get_id()),
            'vendor'     => '',
            'categories' => $this->get_product_categories($product),
        );

        if ($quantity !== null) {
            $formatted['quantity'] = (int) $quantity;
        }

        return $formatted;
    }

    /**
     * Get product categories
     *
     * @param  WC_Product $product Product object
     * @return array Category names
     */
    protected function get_product_categories($product)
    {
        $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
        $terms      = get_the_terms($product_id, 'product_cat');

        if (! $terms || is_wp_error($terms)) {
            return array();
        }

        return array_map(
            function ($term) {
                return $term->name;
            },
            array_slice($terms, 0, 5)
        );
    }

    /**
     * Format cart line item for Pixel SDK
     *
     * @param  array $item Cart item
     * @return array Formatted cart line item
     */
    protected function get_formatted_cart_item($item)
    {
        return array(
            'item'     => $this->get_formatted_product($item['data']),
            'quantity' => (int) $item['quantity'],
            'price'    => (float) $item['line_total'],
            'currency' => get_woocommerce_currency(),
        );
    }

    /**
     * Get formatted cart data
     *
     * @return array Formatted cart
     */
    protected function get_formatted_cart()
    {
        $cart = WC()->cart;

        $line_items = array();
        foreach ($cart->get_cart() as $cart_item) {
            $line_items[] = $this->get_formatted_cart_item($cart_item);
        }

        return array(
            'id'         => $this->get_cart_id(),
            'lineItems'  => $line_items,
            'totalPrice' => (float) $cart->get_total('edit'),
            'currency'   => get_woocommerce_currency(),
        );
    }

    /**
     * Get formatted checkout data
     *
     * @return array Formatted checkout
     */
    protected function get_formatted_checkout()
    {
        $cart = WC()->cart;

        $line_items = array();
        foreach ($cart->get_cart() as $cart_item) {
            $line_items[] = $this->get_formatted_cart_item($cart_item);
        }

        return array(
            'id'             => 'checkout_' . $this->get_cart_id(),
            'cartId'         => $this->get_cart_id(),
            'lineItems'      => $line_items,
            'subtotalPrice'  => (float) $cart->get_subtotal(),
            'totalTax'       => (float) $cart->get_total_tax(),
            'totalShipping'  => (float) $cart->get_shipping_total(),
            'totalPrice'     => (float) $cart->get_total('edit'),
            'currency'       => get_woocommerce_currency(),
        );
    }

    /**
     * Format order line item for Pixel SDK
     *
     * @param  WC_Order_Item_Product $item Order item
     * @return array Formatted order line item
     */
    protected function get_formatted_order_item($item)
    {
        $product = $item->get_product();
        if (! $product) {
            return null;
        }

        return array(
            'item'     => $this->get_formatted_product($product),
            'quantity' => (int) $item->get_quantity(),
            'price'    => (float) $item->get_total(),
            'currency' => get_woocommerce_currency(),
        );
    }

    /**
     * Get formatted order data
     *
     * @param  WC_Order $order Order object
     * @return array Formatted order
     */
    protected function get_formatted_order($order)
    {
        $line_items = array();
        foreach ($order->get_items() as $item) {
            $formatted_item = $this->get_formatted_order_item($item);
            if ($formatted_item) {
                $line_items[] = $formatted_item;
            }
        }

        return array(
            'id'             => (string) $order->get_id(),
            'lineItems'      => $line_items,
            'subtotalPrice'  => (float) $order->get_subtotal(),
            'totalTax'       => (float) $order->get_total_tax(),
            'totalShipping'  => (float) $order->get_shipping_total(),
            'totalPrice'     => (float) $order->get_total(),
            'currency'       => $order->get_currency(),
            'customerId'     => $order->get_customer_id() ? (string) $order->get_customer_id() : '',
        );
    }

    /**
     * Get cart ID from WooCommerce session
     *
     * @return string Cart ID
     */
    protected function get_cart_id()
    {
        if (! WC()->session) {
            return '';
        }
        return WC()->session->get_customer_id();
    }

    /**
     * Append data to script data array
     *
     * @param string $type Data type (products, cart, order, etc.)
     * @param mixed  $data Data to append
     */
    public function append_script_data($type, $data)
    {
        if (! isset($this->script_data[ $type ])) {
            $this->script_data[ $type ] = array();
        }

        // For events, just collect them in an array (deduplicated)
        if ($type === 'events') {
            if (! in_array($data, $this->script_data[ $type ], true)) {
                $this->script_data[ $type ][] = $data;
            }
        } elseif ($type === 'products') {
            // Products list: always append (no dedup needed for listing preloads)
            $this->script_data[ $type ][] = $data;
        } elseif (in_array($type, ['added_to_cart', 'removed_from_cart'], true)) {
            // Cart mutation queues: dedup identical payloads.
            // Prevents double-fire when track_add_to_cart writes to script_data
            // AND hydrate_script_data_from_session re-adds the same item from session.
            if (!in_array($data, $this->script_data[ $type ])) {
                $this->script_data[ $type ][] = $data;
            }
        } else {
            // For single objects (product, cart, order), store directly
            $this->script_data[ $type ] = $data;
        }
    }

    /**
     * Get all script data as JSON
     *
     * @return string JSON-encoded script data
     */
    public function get_script_data()
    {
        return wp_json_encode($this->script_data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Output inline script data to footer
     *
     * Always outputs window.mcPixel with cartId so block JS can access it.
     * Only outputs data if there are events to send.
     */
    public function inline_script_data()
    {
        ?>
        <script type="text/javascript">
        window.mcPixel = window.mcPixel || {};
        window.mcPixel._handled = {};
        window.mcPixel.cartId = '<?php echo esc_js($this->get_cart_id()); ?>';
        <?php if (! empty($this->script_data)) : ?>
        window.mcPixel.data = <?php echo $this->get_script_data(); ?>;
        <?php endif; ?>
        </script>
        <?php
    }

    /**
     * Enqueue tracking script
     */
    public function enqueue_tracking_script()
    {
        wp_enqueue_script(
            'mailchimp-woocommerce-pixel-tracking',
            plugin_dir_url(dirname(__DIR__)) . 'public/js/mailchimp-woocommerce-pixel-tracking.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script('mailchimp-woocommerce-pixel-tracking', 'mcPixelConfig', array(
            'restBase' => esc_url_raw(rest_url('mailchimp-for-woocommerce/v1/')),
        ));
    }

    /**
     * Track checkout started on block checkout pages.
     *
     * Detects block checkout via has_block() and populates checkout data
     * server-side so the standard JS (or block JS as fallback) can fire CHECKOUT_STARTED.
     */
    public function track_checkout_started_block()
    {
        if (! function_exists('has_block') || ! function_exists('wc_get_page_id')) {
            return;
        }

        $checkout_page_id = wc_get_page_id('checkout');
        if (! $checkout_page_id || $checkout_page_id < 1) {
            return;
        }

        if (! is_page($checkout_page_id)) {
            return;
        }

        // Only handle block checkout — shortcode checkout is handled by woocommerce_before_checkout_form
        $post = get_post($checkout_page_id);
        if (! $post || ! has_block('woocommerce/checkout', $post)) {
            return;
        }

        if (! WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        if (($customer = WC()->cart->get_customer())) {
            if ($customer->get_email()) {
                $this->track_identity($customer->get_email());
            } else if ($customer->get_billing_email()) {
                $this->track_identity($customer->get_billing_email());
            }
        }

        $checkout_data = $this->get_formatted_checkout();
        $this->append_script_data('checkout', $checkout_data);
        $this->append_script_data('events', 'CHECKOUT_STARTED');
    }

    /**
     * Enqueue block pixel tracking script.
     *
     * Registers and enqueues the compiled block pixel tracking JS
     * with auto-detected dependencies from the webpack asset file.
     */
    public function enqueue_block_tracking_script()
    {
        $script_path = dirname(__DIR__, 2) . '/blocks/build/pixel-tracking.js';
        $asset_path  = dirname(__DIR__, 2) . '/blocks/build/pixel-tracking.asset.php';

        if (! file_exists($script_path)) {
            return;
        }

        $asset = file_exists($asset_path)
            ? require $asset_path
            : array( 'dependencies' => array( 'wp-hooks' ), 'version' => '1.0.0' );

        wp_enqueue_script(
            'mailchimp-woocommerce-pixel-tracking-blocks',
            plugin_dir_url(dirname(__DIR__)) . 'blocks/build/pixel-tracking.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    /**
     * @return bool
     */
    protected function doingAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    protected function cookie($key, $default = null)
    {
        // if we're not allowed to use cookies, just return the default
        if (!mailchimp_allowed_to_use_cookie($key)) {
            return $default;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }
}

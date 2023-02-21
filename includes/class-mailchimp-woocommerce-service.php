<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/17/16
 * Time: 12:03 PM
 */
class MailChimp_Service extends MailChimp_WooCommerce_Options
{
    protected $user_email = null;
    protected $previous_email = null;
    protected $user_language = null;
    protected $cart_subscribe = null;
    protected $force_cart_post = false;
    protected $cart_was_submitted = false;
    protected $cart = array();
    protected $validated_cart_db = false;
    /** @var null|static */
    protected static $_instance = null;

    /**
     * @return MailChimp_Service
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) {
            return static::$_instance;
        }
        $env = mailchimp_environment_variables();
        static::$_instance = new MailChimp_Service();
        static::$_instance->setVersion($env->version);
        return static::$_instance;
    }

    /**
     * hook fired when we know everything is booted
     */
    public function wooIsRunning()
    {
        // make sure the site option for setting the mailchimp_carts has been saved.
        $this->validated_cart_db = get_site_option('mailchimp_woocommerce_db_mailchimp_carts');
        $this->is_admin = current_user_can('administrator');
    }

    /**
     * @param $r
     * @param $url
     * @return mixed
     */
    public function addHttpRequestArgs( $r, $url ) {
        // not sure whether or not we need to implement something like this yet.
        //$r['headers']['Authorization'] = 'Basic ' . base64_encode('username:password');
        return $r;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    protected function cookie($key, $default = null)
    {
        // if we're not allowed to use cookies, just return the default
        if ($this->is_admin || !mailchimp_allowed_to_use_cookie($key)) {
            return $default;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * @param WC_Order $order
     */
    public function onNewPayPalOrder($order)
    {
        $this->onNewOrder($order->get_id());
    }

    /**
     * This should only fire on a web based order so we can do real campaign tracking here.
     *
     * @param $order_id
     * @return void|array
     */
    public function onNewOrder($order_id)
    {
        if (!mailchimp_is_configured()) {
        	return;
        }

        // see if we have a session id and a campaign id, also only do this when this user is not the admin.
        $campaign_id = $this->getCampaignTrackingID();
        if (empty($campaign_id)) {
            $campaign_id =  get_post_meta($order_id, 'mailchimp_woocommerce_campaign_id', true);
            // make sure this campaign ID has a valid format before we submit something
            if (!$this->campaignIdMatchesFormat($campaign_id)) {
                $campaign = null;
            }
        }

        // grab the landing site cookie if we have one here.
        $landing_site = $this->getLandingSiteCookie();
        if (empty($landing_site)) {
            $landing_site =  get_post_meta($order_id, 'mailchimp_woocommerce_landing_site', true);
            if (!$landing_site) $campaign = null;
        }

        // expire the landing site cookie so we can rinse and repeat tracking
        $this->expireLandingSiteCookie();

        // remove this record from the db.
        $this->clearCartData();

        return array (
            'campaign_id' => $campaign_id,
            'landing_site' => $landing_site
        );
    }

	/**
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 */
    public function handleOrderStatusChanged($order_id, $old_status, $new_status)
    {
        if (!mailchimp_is_configured()) return;
        
        $tracking = null;
        $newOrder = false;

        if ("pending" == $old_status && ("processing" == $new_status || "completed" == $new_status)) {
            $tracking = $this->onNewOrder($order_id);
            $newOrder = true;
        }

        mailchimp_log('debug', "Order ID {$order_id} was {$old_status} and is now {$new_status}", array('new_order' => $newOrder, 'tracking' => $tracking));

        $this->onOrderSave($order_id, $tracking, $newOrder);
    }

	/**
	 * @param $order_id
	 * @param null $tracking
	 * @param null $newOrder
	 */
    public function onOrderSave($order_id, $tracking = null, $newOrder = null)
    {
        if (!mailchimp_is_configured()) return;

        // queue up the single order to be processed.
        $campaign_id = isset($tracking) && isset($tracking['campaign_id']) ? $tracking['campaign_id'] : null;
        $landing_site = isset($tracking) && isset($tracking['landing_site']) ? $tracking['landing_site'] : null;
        $language = $newOrder ? substr( get_locale(), 0, 2 ) : null;
        
        $gdpr_fields = isset($_POST['mailchimp_woocommerce_gdpr']) ? 
            $_POST['mailchimp_woocommerce_gdpr'] : false;

        // update the post meta with campaign tracking and landing site details
        if (!empty($campaign_id)) {
            update_post_meta($order_id, 'mailchimp_woocommerce_campaign_id', $campaign_id);
        }
        if (!empty($landing_site)) {
            update_post_meta($order_id, 'mailchimp_woocommerce_landing_site', $landing_site);
        }

        // if we have gdpr fields in the post - let's save them to the order
        if (!empty($gdpr_fields)) {
            update_post_meta($order_id, "mailchimp_woocommerce_gdpr_fields", $gdpr_fields);
        }

        $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, $campaign_id, $landing_site, $language, $gdpr_fields);
        $handler->is_update = $newOrder ? !$newOrder : null;
        $handler->is_admin_save = is_admin();
        
        mailchimp_handle_or_queue($handler, 90);
    }

    /**
     * @param $order_id
     */
    public function onPartiallyRefunded($order_id)
    {
        if (!mailchimp_is_configured()) return;

        $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, null, null);
        $handler->partially_refunded = true;
        mailchimp_handle_or_queue($handler);
    }

    /**
     * Clear the card data for a user.
     */
    public function clearCartData()
    {
        if ($user_email = $this->getCurrentUserEmail()) {
            $this->deleteCart(mailchimp_hash_trim_lower($user_email));
        }
    }

	/**
	 * @param null $updated
	 *
	 * @return bool|mixed|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function handleCartUpdated($updated = null)
    {
        if (mailchimp_carts_disabled()) {
            return $updated;
        }

        if ($updated === false || $this->is_admin || $this->cart_was_submitted || !mailchimp_is_configured()) {
            return !is_null($updated) ? $updated : false;
        }

        if (empty($this->cart)) {
            $this->cart = $this->getCartItems();
        }

        if (($user_email = $this->getCurrentUserEmail())) {

            // let's skip this right here - no need to go any further.
            if (mailchimp_email_is_privacy_protected($user_email)) {
                return !is_null($updated) ? $updated : false;
            }

            // if the user chose to send to subscribers only we need to do a quick check
            // to see if this email has already subscribed.
            if (!$this->cart_subscribe && (mailchimp_carts_subscribers_only() || mailchimp_submit_subscribed_only())) {
                $transient_key = mailchimp_hash_trim_lower($user_email).".mc.status";
                $cached_status = mailchimp_get_transient($transient_key);
                if ($cached_status === null) {
                    $cached_status = mailchimp_get_subscriber_status($user_email);
                    mailchimp_set_transient($transient_key, $cached_status ? $cached_status : false, 300);
                }
                if ($cached_status !== 'subscribed') {
                    mailchimp_debug('filter', "preventing {$user_email} from submitting cart data due to subscriber settings.");
                    return $updated;
                }
            }

            $previous = $this->getPreviousEmailFromSession();

            $uid = mailchimp_hash_trim_lower($user_email);

            $unique_sid = $this->getUniqueStoreID();

            // delete the previous records.
            if (!empty($previous) && $previous !== $user_email) {

                if ($this->api()->deleteCartByID($unique_sid, $previous_email = mailchimp_hash_trim_lower($previous))) {
                    mailchimp_log('ac.cart_swap', "Deleted cart [$previous] :: ID [$previous_email]");
                }

                // going to delete the cart because we are switching.
                $this->deleteCart($previous_email);
            }

            // delete the current cart record if there is one
            $this->api()->deleteCartByID($unique_sid, $uid);

            if ($this->cart && !empty($this->cart)) {

                // track the cart locally so we can repopulate things for cross device compatibility.
                $this->trackCart($uid, $user_email);

                $this->cart_was_submitted = true;

                // grab the cookie data that could play important roles in the submission
                $campaign = $this->getCampaignTrackingID();
                
                // get user language or default to admin main language
                $language = $this->user_language ?: substr(get_locale(), 0, 2);
                
                // fire up the job handler
                $handler = new MailChimp_WooCommerce_Cart_Update($uid, $user_email, $campaign, $this->cart, $language);

                // if they had the checkbox checked - go ahead and subscribe them if this is the first post.
                //$handler->setStatus($this->cart_subscribe);

                mailchimp_handle_or_queue($handler);
            }

            return !is_null($updated) ? $updated : true;
        }

        return !is_null($updated) ? $updated : false;
    }

    /**
     * @param $post_id
     */
    public function handleNewCoupon($post_id)
    {
        $this->handleCouponSaved($post_id, new WC_Coupon($post_id));
    }

    /**
     * @param $post_id
     * @param null $coupon
     */
    public function handleCouponSaved($post_id, $coupon = null)
    {
        if (!mailchimp_is_configured()) return;

        if ($coupon instanceof WC_Coupon) {
            mailchimp_handle_or_queue(new MailChimp_WooCommerce_SingleCoupon($post_id));
        }
    }

    /**
     * @param $post_id
     */
    public function handleCouponRestored($post_id)
    {
        $this->handleCouponSaved($post_id, new WC_Coupon($post_id));
    }

    /**
     * @param WC_Data          $object   The deleted or trashed object.
	 * @param WP_REST_Response $response The response data.
     * @param WP_REST_Request  $request  The request sent to the API.
     */
    public function handleAPICouponTrashed($object, $response, $request)
    {
        try {
            $deleted = mailchimp_get_api()->deletePromoRule(mailchimp_get_store_id(), $request['id']);
            if ($deleted) mailchimp_log('api.promo_code.deleted', "deleted promo code {$request['id']}");
            else mailchimp_log('api.promo_code.delete_fail', "Unable to delete promo code {$request['id']}");
        } catch (Exception $e) {
            mailchimp_error('delete promo code', $e->getMessage());
        }
    }

    /**
     * When a product post has been updated, handle or queue syncing when key fields have changed.
     *
     * @param int     $post_ID     The ID of the post/product being updated
     * @param WP_Post $post_after  The post object as it existed before the update
     * @param WP_Post $post_before The post object as it exists after the update
     * @return void
     */
    public function handleProductUpdated( int $post_ID, WP_Post $post_after, WP_Post $post_before )
    {
        // Only work with products that have certain statuses
        if ('product' !== $post_after->post_type
            || in_array($post_after->post_status, array('trash', 'auto-draft', 'draft', 'pending'))
            || ! mailchimp_is_configured()
        ) {
            return;
        }

        // Check if product title or description has been altered
        if ($post_after->post_title !== $post_before->post_title
            || $post_after->post_content !== $post_before->post_content
        ) {
            mailchimp_handle_or_queue( new MailChimp_WooCommerce_Single_Product($post_ID), 5);
        }
    }

    /**
     * @param WC_Product $product
     * @param $data
     */
    public function handleProcessProductMeta($product, $data)
    {
        if (!is_array($data) || empty($data) || !$product) {
            return;
        }

        $valid_keys = array(
            '_thumbnail_id',
            'description',
            'image_id',
            'price',
            'sku',
            'regular_price',
            'sale_price',
            '_stock_status',
            'stock_quantity',
            '_stock',
            'stock_status',
            'manage_stock',
            'gallery_image_ids',
            'name',
            'status',
            'slug',
        );

        // if there's not a valid prop in the update, just skip this.
        if (!array_intersect($valid_keys, $data)) {
            return;
        }

        mailchimp_debug('action', "handleProcessProductMeta {$product->get_id()} update being queued", array(
            'data' => $data,
        ));

        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($product->get_id()), 5);
    }

	/**
	 * When the _stock, _thumbnail_id,
     * meta is updated for a product, handle or queue syncing updates.
	 *
	 * @param int    $meta_id     The ID of the post meta entry that was updated
	 * @param int    $object_id   The ID of the object the post meta entry is attached to
	 * @param string $meta_key    The key of the meta entry that was updated
	 * @param mixed  $_meta_value The value of the meta entry that was updated
	 * @return void
	 */
	public function handleProductMetaUpdated($meta_id, $object_id, $meta_key, $_meta_value)
    {
		// If we're not working with the meta key used to store stock quantity, bail
		if (!in_array($meta_key, array('_thumbnail_id'), true)) {
			return;
		}

		// Confirm that we're working with an object that is a WooCommerce product with a certain status
		$product = wc_get_product($object_id);
		if ($product instanceof WC_Product &&
            !in_array($product->get_status(), array('trash', 'auto-draft', 'draft', 'pending'))
		) {
		    mailchimp_debug('queue', "handling meta update for meta [{$meta_key}] on product {$object_id}");
			mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($object_id), 5);
		}
	}

	/**
	 * If a product has been updated and isn't an existing post, handle or queue syncing updates.
	 *
	 * @param int     $post_ID           The ID of the post that was updated/created
	 * @param WP_Post $post              The post object that was updated/created
	 * @param bool    $is_existing_post  Whether the updated post existed before the update
	 * @return void
	 */
	public function handleProductCreated($post_ID, WP_Post $post, $is_existing_post)
    {
		// Since the handleProductUpdated() function above handles product updates, bail for existing posts/products.
		if ($is_existing_post || !mailchimp_is_configured()) {
			return;
		}

		// If the product is of a certain status, process it.
		if (!in_array($post->post_status, array('trash', 'auto-draft', 'draft', 'pending'))) {
			mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($post_ID), 5);
		}
	}

	/**
	 * Fire new order and order save handling/queueing events when a shop_order post is saved.
	 *
	 * @param int     $post_ID          The ID of the order
	 * @param WP_Post $post             The post object of the order
	 * @param bool    $is_existing_post Whether the order existed before the update
	 * @return void
	 */
	public function handleOrderSaved( $post_ID, WP_Post $post, $is_existing_post)
    {
		if (!mailchimp_is_configured()) {
			return;
		}

		if (!in_array($post->post_status, array('trash', 'auto-draft', 'draft', 'pending'))) {
			$tracking = $this->onNewOrder($post_ID);
			$this->onOrderSave($post_ID, $tracking, !$is_existing_post);
		}
	}

    /**
     * @param $post_id
     */
    public function handlePostTrashed($post_id)
    {
        if (!mailchimp_is_configured()) return;

        switch (get_post_type($post_id)) {
            case 'shop_coupon':
                try {
                    $deleted = mailchimp_get_api()->deletePromoRule(mailchimp_get_store_id(), $post_id);
                    if ($deleted) mailchimp_log('promo_code.deleted', "deleted promo code {$post_id}");
                    else mailchimp_log('promo_code.delete_fail', "Unable to delete promo code {$post_id}");
                } catch (Exception $e) {
                    mailchimp_error('delete promo code', $e->getMessage());
                }
                break;
            case 'product':
                try {
                    $deleted = mailchimp_get_api()->deleteStoreProduct(mailchimp_get_store_id(), $post_id);
                    if ($deleted) mailchimp_log('product.deleted', "deleted product {$post_id}");
                    else mailchimp_log('product.delete_fail', "Unable to deleted product {$post_id}");
                } catch (Exception $e) {
                    mailchimp_error('delete product', $e->getMessage());
                }
                break;
        }
    }

    /**
     * @param $post_id
     * @return void
     */
    public function handlePostRestored($post_id)
    {
        if (!mailchimp_is_configured() || !($post = get_post($post_id))) {
        	return;
        }

        // don't handle any of these statuses because they're not ready for the show
        if (in_array($post->post_status, array('trash', 'auto-draft', 'draft', 'pending'))) {
            return;
        }

        switch(get_post_type($post_id)) {
            case 'shop_coupon':
                $this->handleCouponRestored($post_id);
                break;
            case 'product':
                mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($post_id), 5);
                break;
        }
    }

    /**
     * @param $user_id
     */
    public function handleUserRegistration($user_id)
    {
        if (!mailchimp_is_configured()) return;

        $subscribed = (bool) isset($_POST['mailchimp_woocommerce_newsletter']) && $_POST['mailchimp_woocommerce_newsletter'];

        if (isset($_POST['mailchimp_woocommerce_newsletter']) && $_POST['mailchimp_woocommerce_newsletter']) {
            $gdpr_fields = isset($_POST['mailchimp_woocommerce_gdpr']) ? 
                $_POST['mailchimp_woocommerce_gdpr'] : false;
        } else {
        	$gdpr_fields = null;
        }

        // update the user meta with the 'is_subscribed' form element
        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', $subscribed);

        if ($subscribed) {
            $job = new MailChimp_WooCommerce_User_Submit($user_id, '1', null, null, $gdpr_fields);
            mailchimp_handle_or_queue($job);
        }
    }

    /**
     * @param $user_id
     * @param $old_user_data
     */
    function handleUserUpdated($user_id, $old_user_data)
    {
        if (!mailchimp_is_configured()) return;

        // check if user_my_account_opt_in_save is processing on frontend.
        if ( !is_admin() ) return;

        // only update this person if they were marked as subscribed before
        $is_subscribed = get_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', true);
        $gdpr_fields = get_user_meta($user_id, 'mailchimp_woocommerce_gdpr_fields', true);

        $job = new MailChimp_WooCommerce_User_Submit(
            $user_id,
            $is_subscribed,
            $old_user_data,
            null,
            !empty($gdpr_fields) ? $gdpr_fields : null
        );
        // only send this update if the user actually has a boolean value.
        mailchimp_handle_or_queue($job);
    }

    /**
     * Delete all the options pointing to the pages, and re-start the sync process.
     * @param bool $only_products
     * @return bool
     */
    protected function syncProducts($only_products = false)
    {
        if (!$this->isAdmin()) return false;
        $this->removePointers(true, ($only_products ? false : true));
        update_option('mailchimp-woocommerce-sync.orders.prevent', $only_products);
        MailChimp_WooCommerce_Process_Products::push();
        return true;
    }

    /**
     * Delete all the options pointing to the pages, and re-start the sync process.
     * @return bool
     */
    protected function syncOrders()
    {
        if (!$this->isAdmin()) return false;
        $this->removePointers(false);
        // since the products are all good, let's sync up the orders now.
        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Orders());
        return true;
    }

    /**
     * @return bool|string
     */
    public function getCurrentUserEmail()
    {
        if (isset($this->user_email) && !empty($this->user_email)) {
            return $this->user_email = strtolower($this->user_email);
        }

        $user = wp_get_current_user();
        $email = ($user->ID > 0 && isset($user->user_email)) ? $user->user_email : $this->getEmailFromSession();

        return $this->user_email = strtolower($email);
    }

    /**
     * @return bool|array
     */
    public function getCartItems()
    {
        if (!($this->cart = $this->getWooSession('cart', false))) {
            $this->cart = !function_exists('WC') ? false : WC()->cart->get_cart();
        } else {
            $cart_session = array();
            foreach ( $this->cart as $key => $values ) {
                $cart_session[$key] = $values;
                unset($cart_session[$key]['data']); // Unset product object
            }
            return $this->cart = $cart_session;
        }

        return is_array($this->cart) ? $this->cart : false;
    }

	/**
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function handleCampaignTracking()
    {
        if (!mailchimp_allowed_to_use_cookie('mailchimp_user_email')) {
            return;
        }

        // set the landing site cookie if we don't have one.
        $this->setLandingSiteCookie();

        $cookie_duration = $this->getCookieDuration();

        // if we have a query string of the mc_cart_id in the URL, that means we are sending a campaign from MC
        if (isset($_GET['mc_cart_id']) && !isset($_GET['removed_item'])) {

            // try to pull the cart from the database.
            if (($cart = $this->getCart($_GET['mc_cart_id'])) && !empty($cart)) {

                // set the current user email
                $this->user_email = trim(str_replace(' ','+', $cart->email));

                if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                    $this->previous_email = $current_email;
                    mailchimp_set_cookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/');
                }

                // cookie the current email
                mailchimp_set_cookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );

                $cart_data = unserialize($cart->cart);

                if (!empty($cart_data)) {
                    // set the cart data.
                    $this->setWooSession('cart', unserialize($cart->cart));

                    mailchimp_debug('carts', "manually setting cart data for {$this->user_email}", array(
                        'cart_id' => $_GET['mc_cart_id'],
                        'cart' => $cart->cart,
                    ));
                }
            }
        }

        if (isset($_GET['mc_cid'])) {
            $this->setCampaignTrackingID($_GET['mc_cid'], $cookie_duration);
        }

        if (isset($_GET['mc_eid'])) {
            mailchimp_set_cookie('mailchimp_email_id', trim($_GET['mc_eid']), $cookie_duration, '/' );
        }
    }

    /**
     * @return mixed|null
     */
    public function getCampaignTrackingID()
    {
    	if ($this->isAdmin()) {
    		return false;
	    }

        $cookie = $this->cookie('mailchimp_campaign_id', false);

        if (empty($cookie)) {
            $cookie = $this->getWooSession('mailchimp_campaign_id', false);
        }

        // we must follow a pattern at minimum in order to think this is possibly a valid campaign ID.
        if (!$this->campaignIdMatchesFormat($cookie)) {
            return false;
        }

        return $cookie;
    }

	/**
	 * @param $id
	 * @param $cookie_duration
	 *
	 * @return $this
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function setCampaignTrackingID($id, $cookie_duration)
    {
        if (!mailchimp_is_configured()) {
            return $this;
        }

        $cid = trim($id);

        // we must follow a pattern at minimum in order to think this is possibly a valid campaign ID.
        if (!$this->campaignIdMatchesFormat($cid)) {
            return $this;
        }

        // don't throw the error if it's not found.
        if (!$this->api()->getCampaign($cid, false)) {
            $cid = null;
        }
        
        mailchimp_set_cookie('mailchimp_campaign_id', $cid, $cookie_duration, '/' );
        $this->setWooSession('mailchimp_campaign_id', $cid);

        return $this;
    }

    /**
     * @param $cid
     * @return bool
     */
    public function campaignIdMatchesFormat($cid)
    {
        if (!is_string($cid) || empty($cid)) return false;
        return (bool) preg_match("/^[a-zA-Z0-9]{10,12}$/", $cid, $matches);
    }

    /**
     * @return bool
     * Checks if the current request is a WP REST API request.
     */
    function is_rest() {
        if (defined('REST_REQUEST') && REST_REQUEST
            || isset($_GET['rest_route'])
            && strpos( $_GET['rest_route'] , '/', 0 ) === 0)
            return true;

        global $wp_rewrite;
        if ($wp_rewrite === null) $wp_rewrite = new WP_Rewrite();

        $rest_url = wp_parse_url( trailingslashit( rest_url( ) ) );
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        return strpos( $current_url['path'] ?? '/', $rest_url['path'], 0 ) === 0;
    }

    /**
     * @return mixed|null
     */
    public function getLandingSiteCookie()
    {
        $cookie = $this->cookie('mailchimp_landing_site', false);

        if (empty($cookie)) {
            $cookie = $this->getWooSession('mailchimp_landing_site', false);
        }

        return $cookie;
    }

    /**
     * @return $this
     */
    public function setLandingSiteCookie()
    {
        // if we're not allowed to use this cookie, just return
        if (!mailchimp_allowed_to_use_cookie('mailchimp_landing_site')) {
            return $this;
        }

        if (isset($_GET['expire_landing_site'])) $this->expireLandingSiteCookie();

        // if we already have a cookie here, we need to skip it.
        if ($this->getLandingSiteCookie() != false) return $this;

        // grab the current landing url since it's a referral.
        $landing_site = home_url() . wp_unslash($_SERVER['REQUEST_URI']);
        
        // Catch all possible file requests to avoid false positives
        // We need to catch just real pages of the website
        // Catching images, videos and fonts file types
        preg_match("/^.*\.(ai|bmp|gif|ico|jpeg|jpg|png|ps|psd|svg|tif|tiff|fnt|fon|otf|ttf|3g2|3gp|avi|flv|h264|m4v|mkv|mov|mp4|mpg|mpeg|rm|swf|vob|wmv|aif|cda|mid|midi|mp3|mpa|ogg|wav|wma|wpl)$/i", $landing_site, $matches);
        
        if (!empty($landing_site) && !wp_doing_ajax() && ( count($matches) == 0 ) && !$this->is_rest() ) {
            mailchimp_set_cookie('mailchimp_landing_site', $landing_site, $this->getCookieDuration(), '/' );
            $this->setWooSession('mailchimp_landing_site', $landing_site);
        }

        return $this;
    }

    /**
     * @return array|bool|string
     */
    public function getReferer()
    {
        if (function_exists('wp_get_referer')) {
            return wp_get_referer();
        }
        if (!empty($_REQUEST['_wp_http_referer'])) {
            return wp_unslash($_REQUEST['_wp_http_referer']);
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            return wp_unslash( $_SERVER['HTTP_REFERER']);
        }
        return false;
    }

    /**
     * @return $this
     */
    public function expireLandingSiteCookie()
    {
        if (!mailchimp_allowed_to_use_cookie('mailchimp_landing_site')) {
            return $this;
        }
        if ( !$this->is_rest() ) {
            mailchimp_set_cookie('mailchimp_landing_site', false, $this->getCookieDuration(), '/' );
            $this->setWooSession('mailchimp_landing_site', false);
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function getEmailFromSession()
    {
        return $this->cookie('mailchimp_user_email', false);
    }

    /**
     * @return bool
     */
    protected function getPreviousEmailFromSession()
    {
        if ($this->previous_email) {
            return $this->previous_email = strtolower($this->previous_email);
        }
        $email = $this->cookie('mailchimp_user_previous_email', false);
        return $email ? strtolower($email) : false;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getWooSession($key, $default = null)
    {
        if (!function_exists('WC')) return $default;

        if (!($woo = WC()) || empty($woo->session)) {
            return $default;
        }

        // not really sure why this would be the case, but if there is no session we can't get it anyway.
        if (!is_object($woo->session) || !method_exists($woo->session, 'get')) {
            return $default;
        }

        return $woo->session->get($key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setWooSession($key, $value)
    {
        if (!function_exists('WC')) return $this;

        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->set($key, $value);

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function removeWooSession($key)
    {
        if (!function_exists('WC')) return $this;

        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->__unset($key);
        return $this;
    }

    /**
     *
     */
    public function get_user_by_hash()
    {
        if ($this->doingAjax() && isset($_GET['hash'])) {
            if (($cart = $this->getCart($_GET['hash']))) {
                $this->respondJSON(array('success' => true, 'email' => $cart->email));
            }
        }
        $this->respondJSON(array('success' => false, 'email' => false));
    }

	/**
	 * @param $email
	 *
	 * @return bool
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function set_user_from_block_checkout($email)
    {
        if (!mailchimp_allowed_to_use_cookie('mailchimp_user_email')) {
            return false;
        }
        if (!empty($email)) {
            $cookie_duration = $this->getCookieDuration();
            $this->user_email = trim(str_replace(' ','+', $email));
            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                mailchimp_set_cookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/' );
            }
            mailchimp_set_cookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );
            $this->getCartItems();
//            if (isset($_GET['mc_language'])) {
//                $this->user_language = $_GET['mc_language'];
//            }
            $this->handleCartUpdated();
            return true;
        }
        return false;
    }

	/**
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function set_user_by_email()
    {
        if (mailchimp_carts_disabled()) {
            $this->respondJSON(array('success' => false, 'message' => 'filter blocked due to carts being disabled'));
        }

        if ($this->is_admin) {
            $this->respondJSON(array('success' => false, 'message' => 'admin carts are not tracked.'));
        }

        if (!mailchimp_allowed_to_use_cookie('mailchimp_user_email')) {
            $this->respondJSON(array('success' => false, 'email' => false, 'message' => 'filter blocked due to cookie preferences'));
        }

        if ($this->doingAjax() && isset($_POST['email'])) {
            $cookie_duration = $this->getCookieDuration();

            $this->user_email = trim(str_replace(' ','+', $_POST['email']));

            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                mailchimp_set_cookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/' );
            }

            mailchimp_set_cookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );

            $this->getCartItems();

            if (isset($_POST['mc_language'])) {
                $this->user_language = $_POST['mc_language'];
            }

            if (isset($_POST['subscribed'])) {
                $this->cart_subscribe = (bool) $_POST['subscribed'];
            }

            $this->handleCartUpdated();

            $this->respondJSON(array(
                'success' => true,
                'email' => $this->user_email,
                'previous' => $this->previous_email,
                'cart' => $this->cart,
            ));
        }

        $this->respondJSON(array('success' => false, 'email' => false));
    }

    /**
     * @param string $time
     * @return int
     */
    protected function getCookieDuration($time = 'thirty_days')
    {
        $durations = array(
            'one_day' => 86400, 'seven_days' => 604800, 'fourteen_days' => 1209600, 'thirty_days' => 2419200,
        );

        if (!array_key_exists($time, $durations)) {
            $time = 'thirty_days';
        }

        return time() + $durations[$time];
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    protected function get($key, $default = false)
    {
        if (!isset($_REQUEST['mailchimp-woocommerce']) || !isset($_REQUEST['mailchimp-woocommerce'][$key])) {
            return $default;
        }
        return $_REQUEST['mailchimp-woocommerce'][$key];
    }

    /**
     * @param $uid
     * @return array|bool|null|object|void
     */
    protected function getCart($uid)
    {
        if (!$this->validated_cart_db) return false;

        global $wpdb;

        $table = "{$wpdb->prefix}mailchimp_carts";
        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        if (($saved_cart = $wpdb->get_row($sql)) && !empty($saved_cart)) {
            return $saved_cart;
        }

        return false;
    }

	/**
	 * @param $uid
	 *
	 * @return bool
	 */
    protected function deleteCart($uid)
    {
        if (!$this->validated_cart_db) return false;

        global $wpdb;
        $table = "{$wpdb->prefix}mailchimp_carts";
        $sql = $wpdb->prepare("DELETE FROM $table WHERE id = %s", $uid);
        $wpdb->query($sql);

        return true;
    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     */
    protected function trackCart($uid, $email)
    {
        if (!$this->validated_cart_db) return false;

        $hash = md5(strtolower($email));
        $transient_key = "mailchimp-woocommerce-cart-{$hash}";

        // let's set a transient here to block dup inserts
        if (get_site_transient($transient_key)) {
            return false;
        }

        // insert the transient
        set_site_transient($transient_key, true, 5);

        global $wpdb;

        // Some people don't want to see these logs when they're in debug mode
        $wpdb->suppress_errors();

        $table = "{$wpdb->prefix}mailchimp_carts";

        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        $user_id = get_current_user_id();

        if (($saved_cart = $wpdb->get_row($sql)) && is_object($saved_cart)) {
            $statement = "UPDATE {$table} SET `cart` = '%s', `email` = '%s', `user_id` = %s WHERE `id` = '%s'";
            $sql = $wpdb->prepare($statement, array(maybe_serialize($this->cart), $email, $user_id, $uid));
            try {
                $wpdb->query($sql);
                delete_site_transient($transient_key);
            } catch (Exception $e) {
                return false;
            }
        } else {
            try {
                $wpdb->insert("{$wpdb->prefix}mailchimp_carts", array(
                    'id' => $uid,
                    'email' => $email,
                    'user_id' => (int) $user_id,
                    'cart'  => maybe_serialize($this->cart),
                    'created_at'   => gmdate('Y-m-d H:i:s', time()),
                ));
                delete_site_transient($transient_key);
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $data
     */
    protected function respondJSON($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * @param null $obj_id
     * @return bool
     */
    public function mailchimp_process_single_job($obj_id = null) {
        try {
            // not sure why this is happening - but we need to prepare for it and return false when it does.
            if (empty($obj_id)) {
                return false;
            }
            // get job row from db
            global $wpdb;
            $sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mailchimp_jobs	WHERE obj_id = %s", $obj_id );
            $job_row = $wpdb->get_row( $sql );
            
            if (is_null($job_row) || !is_object($job_row)) {
                mailchimp_error('action_scheduler.process_job.fail','Job '.current_action().' not found at '.$wpdb->prefix.'_mailchimp_jobs database table :: obj_id '.$obj_id);
                return false;
            }
            // get variables
            $job = unserialize($job_row->job);
            
            $job_id =$job_row->id;

            // process job
            $job->handle();
            
            // delete processed job
            $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mailchimp_jobs WHERE id = %s AND obj_id = %s", array($job_id, $obj_id));
            $wpdb->query($sql);
            
            return true;
        } catch (Exception $e) {
            $message = !empty($e->getMessage()) ? ' - ' . $e->getMessage() :'';

            mailchimp_debug('action_scheduler.process_job.fail', (isset($job) ? get_class($job) : '') . ' :: obj_id '.$obj_id . ' :: ' .get_class($e) . $message);
        }
        return false;
    }

	/**
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function mailchimp_process_sync_manager()
    {
        $sync_stats_manager = new MailChimp_WooCommerce_Process_Full_Sync_Manager();
        $sync_stats_manager->handle();
    }

    /**
     * Display the Mailchimp checkbox on the admin page
     * @param $user
     */
    public function user_subscribed_profile( $user )
    {
        $admin = MailChimp_WooCommerce_Admin::instance();
        $admin->display_user_profile_info( $user );
    }

    /**
     * Update the user meta from the admin page
     * @param $user_id
     */
    public function user_update_subscribe_status( $user_id )
    {
    	$subscribed = isset($_POST['mailchimp_woocommerce_is_subscribed_radio']) ? $_POST['mailchimp_woocommerce_is_subscribed_radio'] : '';
        $gdpr_fields = isset($_POST['mailchimp_woocommerce_gdpr']) ? $_POST['mailchimp_woocommerce_gdpr'] : null;

        // set a site transient that will prevent overlapping updates from refreshing the page on the admin user view
        mailchimp_set_transient("updating_subscriber_status.{$user_id}", true, 300);

        mailchimp_log("profile", 'user_update_subscribe_status', array(
            'subscribed' => $subscribed,
            'user_id' => $user_id,
            'gdpr_fields' => $gdpr_fields,
        ));

	    $user = get_user_by('id', $user_id);

	    if ( $user && $user->user_email ) {
		    $email_hash = md5( strtolower( trim( $user->user_email ) ) );
		    $list_id = mailchimp_get_list_id();
		    $transient = "mailchimp-woocommerce-subscribed.{$list_id}.{$email_hash}";
		    delete_site_transient( $transient );
	    }

        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', $subscribed);
        update_user_meta($user_id, 'mailchimp_woocommerce_gdpr_fields', $gdpr_fields);
        mailchimp_set_transient("mailchimp_woocommerce_gdpr_fields_{$user_id}", $gdpr_fields, 300);
    }
}
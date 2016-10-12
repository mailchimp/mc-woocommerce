<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 2/17/16
 * Time: 12:03 PM
 */
class MailChimp_Service extends MailChimp_Woocommerce_Options
{
    protected $user_email = null;
    protected $previous_email = null;
    protected $force_cart_post = false;
    protected $pushed_orders = array();
    protected $cart_was_submitted = false;
    protected $cart = array();

    /**
     * hook fired when we know everything is booted
     */
    public function wooIsRunning()
    {
        $this->handleAdminFunctions();
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
        if ($this->is_admin) {
            return $default;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * @param $order_id
     */
    public function handleOrderStatusChanged($order_id)
    {
        if ($this->hasOption('mailchimp_api_key') && !array_key_exists($order_id, $this->pushed_orders)) {

            // register this order is already in process..
            $this->pushed_orders[$order_id] = true;

            // see if we have a session id and a campaign id, also only do this when this user is not the admin.
            $campaign_id = $this->getCampaignTrackingID();

            // queue up the single order to be processed.
            $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, $campaign_id);
            wp_queue($handler);
        }
    }

    /**
     * @return bool|void
     */
    public function handleCartUpdated()
    {
        if ($this->is_admin || $this->cart_was_submitted || !$this->hasOption('mailchimp_api_key')) {
            return false;
        }

        if (empty($this->cart)) {
            $this->cart = $this->getCartItems();
        }

        if (($user_email = $this->getCurrentUserEmail())) {

            $previous = $this->getPreviousEmailFromSession();

            $uid = md5(trim(strtolower($user_email)));

            // delete the previous records.
            if (!empty($previous) && $previous !== $user_email) {
                if ($this->api()->deleteCartByID($this->getUniqueStoreID(), $previous_email = md5(trim(strtolower($previous))))) {
                    mailchimp_log('ac.cart_swap', "Deleted cart [$previous] :: ID [$previous_email]");
                }
            }

            if ($this->cart && !empty($this->cart)) {

                $this->cart_was_submitted = true;

                // grab the cookie data that could play important roles in the submission
                $campaign = $this->getCampaignTrackingID();

                // fire up the job handler
                $handler = new MailChimp_WooCommerce_Cart_Update($uid, $user_email, $campaign, $this->cart);
                wp_queue($handler);
            }

            return true;
        }

        return false;
    }

    /**
     * Save post metadata when a post is saved.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function handlePostSaved($post_id, $post, $update) {
        if ('product' == $post->post_type) {
            wp_queue(new MailChimp_WooCommerce_Single_Product($post_id), 5);
        } elseif ('shop_order' == $post->post_type) {
            wp_queue(new MailChimp_WooCommerce_Single_Order($post_id, null, null));
        }
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
    public function getCartItems() {
        if (!($this->cart = $this->getWooSession('cart', false))) {
            $this->cart = WC()->cart->get_cart();
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
     * Set the cookie of the mailchimp campaigns if we have one.
     */
    public function handleCampaignTracking()
    {
        $cookie_duration = $this->getCookieDuration();

        if (isset($_REQUEST['mc_cid'])) {
            $this->setCampaignTrackingID($_REQUEST['mc_cid'], $cookie_duration);
        }

        if (isset($_REQUEST['mc_eid'])) {
            @setcookie('mailchimp_email_id', trim($_REQUEST['mc_eid']), $cookie_duration, '/' );
        }
    }

    /**
     * @return mixed|null
     */
    public function getCampaignTrackingID()
    {
        $cookie = $this->cookie('mailchimp_campaign_id', false);
        if (empty($cookie)) {
            $cookie = $this->getWooSession('mailchimp_tracking_id', false);
        }
        return $cookie;
    }

    /**
     * @param $id
     * @param $cookie_duration
     * @return $this
     */
    public function setCampaignTrackingID($id, $cookie_duration)
    {
        $cid = trim($id);

        @setcookie('mailchimp_campaign_id', $cid, $cookie_duration, '/' );
        $this->setWooSession('mailchimp_campaign_id', $cid);

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
        if (!($woo = WC()) || empty($woo->session)) {
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
        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->__unset($key);
        return $this;
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
     * Just a wrapper to call various methods from MailChimp to the store.
     * Authentication is based on the secret keys being correct or it will fail.
     *
     * The get requests need:
     * 1. mailchimp-woocommerce[action]
     * 2. mailchimp-woocommerce[submission]
     * 3. various other parts based on the api call.
     */
    protected function handleAdminFunctions()
    {
        if (isset($_GET['reset_cookies'])) {
            $buster = time()-300;

            setcookie('mailchimp_user_previous_email', '', $buster);
            setcookie('mailchimp_user_email', '', $buster);
            setcookie('mailchimp_campaign_id', '', $buster);
            setcookie('mailchimp_email_id', '', $buster);

            $this->previous_email = null;
            $this->user_email = null;
        }

        $methods = array(
            'plugin-version' => 'respondAdminGetPluginVersion',
            'submit-email' => 'respondAdminSubmitEmail',
            'track-campaign' => 'respondAdminTrackCampaign',
            'get-tracking-data' => 'respondAdminGetTrackingData',
            'verify' => 'respondAdminVerify',
        );

        if (($action = $this->get('action'))) {

            if ($action === 'sync') {
                return $this->sync();
            }

            if (array_key_exists($action, $methods)) {
                if (!in_array($action, array('submit-email', 'track-campaign', 'get-tracking-data'))) {
                    $this->authenticate();
                }
                $this->respondJSON($this->{$methods[$action]}());
            }
        }
    }

    /**
     * Delete all the options pointing to the pages, and re-start the sync process.
     * @return void
     */
    protected function sync()
    {
        // only do this if we're an admin user.
        if ($this->isAdmin()) {

            delete_option('mailchimp-woocommerce-errors.store_info');
            delete_option('mailchimp-woocommerce-sync.orders.completed_at');
            delete_option('mailchimp-woocommerce-sync.orders.current_page');
            delete_option('mailchimp-woocommerce-sync.products.completed_at');
            delete_option('mailchimp-woocommerce-sync.products.current_page');
            delete_option('mailchimp-woocommerce-sync.syncing');
            delete_option('mailchimp-woocommerce-sync.started_at');
            delete_option('mailchimp-woocommerce-sync.completed_at');
            delete_option('mailchimp-woocommerce-validation.api.ping');
            delete_option('mailchimp-woocommerce-cached-api-lists');
            delete_option('mailchimp-woocommerce-cached-api-ping-check');

            $job = new MailChimp_WooCommerce_Process_Products();
            $job->flagStartSync();
            wp_queue($job);

            wp_redirect('/options-general.php?page=mailchimp-woocommerce&tab=api_key&success_notice=re-sync-started');
        }

        return;
    }

    /**
     * @return array
     */
    protected function respondAdminGetPluginVersion()
    {
        return array('success' => true, 'version' => $this->getVersion());
    }

    /**
     * @return array
     */
    protected function respondAdminVerify()
    {
        return array('success' => true);
    }

    /**
     * @return array
     */
    protected function respondAdminSubmitEmail()
    {
        if ($this->is_admin) {
            return array('success' => false);
        }

        $submission = $this->get('submission');

        if (is_array($submission) && isset($submission['email'])) {

            $cookie_duration = $this->getCookieDuration();

            $this->user_email = trim(str_replace(' ','+', $submission['email']));

            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                @setcookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/' );
            }

            @setcookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );

            $this->getCartItems();

            $this->handleCartUpdated();

            return array(
                'success' => true,
                'email' => $this->user_email,
                'previous' => $this->previous_email,
                'cart' => $this->cart,
            );
        }
        return array('success' => false);
    }

    /**
     * @return array
     */
    protected function respondAdminTrackCampaign()
    {
        if ($this->is_admin) {
            return array('success' => false);
        }

        $submission = $this->get('submission');

        if (is_array($submission) && isset($submission['campaign_id'])) {

            $duration = $this->getCookieDuration();

            $campaign_id = trim($submission['campaign_id']);
            $email_id = trim($submission['email_id']);

            @setcookie('mailchimp_campaign_id', $campaign_id, $duration, '/');
            @setcookie('mailchimp_email_id', $email_id, $duration, '/');

            return $this->respondAdminGetTrackingData();
        }
        return array('success' => false);
    }

    /**
     * @return array
     */
    protected function respondAdminGetTrackingData()
    {
        return array(
            'success' => true,
            'campaign_id' => $this->cookie('mailchimp_campaign_id', 'n/a'),
            'email_id' => $this->cookie('mailchimp_email_id', 'n/a')
        );
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
     * @return bool
     */
    protected function authenticate()
    {
        if (trim((string) $this->getUniqueStoreID()) !== trim((string) $this->get('store_id'))) {
            $this->respondJSON(array('success' => false, 'message' => 'Not Authorized'));
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
}

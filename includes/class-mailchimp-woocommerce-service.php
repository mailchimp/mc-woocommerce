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

    /**
     * hook fired when we know everything is booted
     */
    public function wooIsRunning()
    {
        $this->handleAdminFunctions();
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
        if (is_admin()) {
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
            $session_id = $this->cookie('mailchimp_session_id');
            $campaign_id = $this->cookie('mailchimp_campaign_id');

            // queue up the single order to be processed.
            $handler = new MailChimp_WooCommerce_Single_Order($order_id, $session_id, $campaign_id);
            wp_queue($handler);
        }
    }

    /**
     * @return bool|void
     */
    public function handleCartUpdated()
    {
        if ($this->cart_was_submitted || is_admin() || $this->is_admin || !$this->hasOption('mailchimp_api_key')) {
            return false;
        }

        $this->cart_was_submitted = true;

        if (($user_email = $this->getCurrentUserEmail())) {

            $previous = $this->getPreviousEmailFromSession();

            $uid = md5(trim($user_email));

            if (!empty($previous) && $previous !== $user_email) {
                $this->api()->deleteCartByID($this->getUniqueStoreID(), md5(trim($previous)));
            }

            // grab the cookie data that could play important roles in the submission
            $campaign = $this->cookie('mailchimp_campaign_id');

            slack()->notice('Abandoned Cart Queued :: '.$user_email.' :: ID ['.$uid.']');

            // fire up the job handler
            $handler = new MailChimp_WooCommerce_Cart_Update($uid, $user_email, $campaign, $this->getCartItems());
            wp_queue($handler);

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
        if ('product' != $post->post_type) {
            return;
        }

        wp_queue(new MailChimp_WooCommerce_Single_Product($post_id), 5);
    }

    /**
     * @return bool|string
     */
    public function getCurrentUserEmail()
    {
        if (isset($this->user_email) && !empty($this->user_email)) {
            return $this->user_email;
        }

        $user = wp_get_current_user();
        return ($user->ID > 0 && isset($user->user_email)) ? $user->user_email : $this->getEmailFromSession();
    }

    /**
     * @return mixed
     */
    public function getCartItems() {
        return WC()->cart->cart_contents;
    }

    /**
     * @return int
     */
    public function getCartItemCount()
    {
        return WC()->cart->get_cart_contents_count();
    }

    /**
     * Set the cookie of the mailchimp campaigns if we have one.
     */
    public function handleCampaignTracking()
    {
        $cookie_duration = $this->getCookieDuration();

        if ( isset( $_REQUEST['mc_cid'] ) ) {
            @setcookie( 'mailchimp_campaign_id', trim( $_REQUEST['mc_cid'] ), $cookie_duration, '/' );
        }

        if ( isset( $_REQUEST['mc_eid'] ) ) {
            @setcookie( 'mailchimp_email_id', trim( $_REQUEST['mc_eid'] ), $cookie_duration, '/' );
        }
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
            return $this->previous_email;
        }
        return $this->cookie('mailchimp_user_previous_email', false);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getWooSession($key, $default = null)
    {
        return (!empty(WC()->session) ? WC()->session->get($key, $default) : $default);
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
        $submission = $this->get('submission');

        if (is_array($submission) && isset($submission['email'])) {

            $this->user_email = trim($submission['email']);

            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                @setcookie('mailchimp_user_previous_email',$this->user_email, $this->getCookieDuration(), '/' );
            }

            @setcookie('mailchimp_user_email', $this->user_email, $this->getCookieDuration(), '/' );

            $cart = $this->getCartItems();
            $repost = count($cart) > 0 ? $this->handleCartUpdated() : false;

            return array(
                'success' => true,
                'cart_item_count' => count($cart),
                'email' => $this->getCurrentUserEmail(),
                'previous' => $current_email,
                're_submitting' => $repost,
                'cart' => $cart,
            );
        }
        return array('success' => false);
    }

    /**
     * @return array
     */
    protected function respondAdminTrackCampaign()
    {
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

<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Single_Order extends WP_Job
{
    public $order_id;
    public $cart_session_id;
    public $campaign_id;

    /**
     * MailChimp_WooCommerce_Single_Order constructor.
     * @param null $order_id
     * @param null $cart_session_id
     * @param null $campaign_id
     */
    public function __construct($order_id = null, $cart_session_id = null, $campaign_id = null)
    {
        if (!empty($order_id)) {
            $this->order_id = $order_id;
        }
        if (!empty($cart_session_id)) {
            $this->cart_session_id = $cart_session_id;
        }
        if (!empty($campaign_id)) {
            $this->campaign_id = $campaign_id;
        }
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // only if we have the right parameters to do the work
        if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

            $job = new MailChimp_WooCommerce_Transform_Orders();
            $api = new MailChimpApi($options['mailchimp_api_key']);

            $call = $api->getStoreOrder($store_id, $this->order_id) ? 'updateStoreOrder' : 'addStoreOrder';

            // if we already pushed this order into the system, we need to unset it now just in case there
            // was another campaign that had been sent and this was only an order update.
            if ($call === 'updateStoreOrder') {
                $job->campaign_id = null;
            }

            // will either add or update the order
            try {
                $api->$call($store_id, ($order = $job->transform(get_post($this->order_id))));
            } catch (\Exception $e) {
                error_log('MailChimp::processSingleOrder :: #'.$this->order_id.' :: '.$e->getMessage());
            }

            // if we're adding a new order and the session id is here, we need to delete the AC cart record.
            if ($call === 'addStoreOrder' && !empty($this->cart_session_id)) {
                $api->deleteCartByID($store_id, $this->cart_session_id);
            }
        }

        return false;
    }
}

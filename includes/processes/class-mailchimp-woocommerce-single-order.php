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
    public $landing_site;
    public $is_update = false;

    /**
     * MailChimp_WooCommerce_Single_Order constructor.
     * @param null $order_id
     * @param null $cart_session_id
     * @param null $campaign_id
     * @param null $landing_site
     */
    public function __construct($order_id = null, $cart_session_id = null, $campaign_id = null, $landing_site = null)
    {
        if (!empty($order_id)) $this->order_id = $order_id;
        if (!empty($cart_session_id)) $this->cart_session_id = $cart_session_id;
        if (!empty($campaign_id)) $this->campaign_id = $campaign_id;
        if (!empty($landing_site)) $this->landing_site = $landing_site;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $this->process();
        return false;
    }

    public function process()
    {
        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // only if we have the right parameters to do the work
        if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

            $job = new MailChimp_WooCommerce_Transform_Orders();
            $api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);

            // set the campaign ID
            $job->campaign_id = $this->campaign_id;

            $call = ($api_response = $api->getStoreOrder($store_id, $this->order_id)) ? 'updateStoreOrder' : 'addStoreOrder';

            if ($call === 'addStoreOrder' && $this->is_update === true) {
                return false;
            }

            // if we already pushed this order into the system, we need to unset it now just in case there
            // was another campaign that had been sent and this was only an order update.
            if ($call === 'updateStoreOrder') {
                $job->campaign_id = null;
                $this->campaign_id = null;
                $this->landing_site = null;
            }

            // will either add or update the order
            try {

                // transform the order
                $order = $job->transform(get_post($this->order_id));

                // will be the same as the customer id. an md5'd hash of a lowercased email.
                $this->cart_session_id = $order->getCustomer()->getId();

                $log = "$call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}";

                // only do this stuff on new orders
                if ($call === 'addStoreOrder') {

                    // apply a campaign id if we have one.
                    if (!empty($this->campaign_id)) {
                        $log .= ' :: campaign id ' . $this->campaign_id;
                        $order->setCampaignId($this->campaign_id);
                    }

                    // apply the landing site if we have one.
                    if (!empty($this->landing_site)) {
                        $log .= ' :: landing site ' . $this->landing_site;
                        $order->setLandingSite($this->landing_site);
                    }

                }

                mailchimp_log('order_submit.submitting', $log);

                // update or create
                $api_response = $api->$call($store_id, $order, false);

                if (empty($api_response)) {
                    return $api_response;
                }

                mailchimp_log('order_submit.success', $log);

                // if we're adding a new order and the session id is here, we need to delete the AC cart record.
                if (!empty($this->cart_session_id)) {
                    $api->deleteCartByID($store_id, $this->cart_session_id);
                }

                return $api_response;

            } catch (\Exception $e) {

                mailchimp_log('order_submit.tracing_error', $message = strtolower($e->getMessage()));

                if (!isset($order)) {
                    // transform the order
                    $order = $job->transform(get_post($this->order_id));
                    $this->cart_session_id = $order->getCustomer()->getId();
                }

                // this can happen when a customer changes their email.
                if (isset($order) && strpos($message, 'not be changed')) {

                    try {

                        mailchimp_log('order_submit.deleting_customer', "#{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}");

                        // delete the customer before adding it again.
                        $api->deleteCustomer($store_id, $order->getCustomer()->getId());

                        // update or create
                        $api_response = $api->$call($store_id, $order, false);

                        $log = "Deleted Customer :: $call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}";

                        if (!empty($job->campaign_id)) {
                            $log .= ' :: campaign id '.$job->campaign_id;
                        }

                        mailchimp_log('order_submit.success', $log);

                        // if we're adding a new order and the session id is here, we need to delete the AC cart record.
                        if (!empty($this->cart_session_id)) {
                            $api->deleteCartByID($store_id, $this->cart_session_id);
                        }

                        return $api_response;

                    } catch (\Exception $e) {
                        mailchimp_log('order_submit.error', 'deleting-customer-re-add :: #'.$this->order_id.' :: '.$e->getMessage());
                    }
                }
            }
        }

        return false;
    }
}


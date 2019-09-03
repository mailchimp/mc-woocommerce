<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Single_Order extends Mailchimp_Woocommerce_Job
{
    public $id;
    public $cart_session_id;
    public $campaign_id;
    public $landing_site;
    public $is_update = false;
    public $is_admin_save = false;
    public $partially_refunded = false;
    protected $woo_order_number = false;
    protected $is_amazon_order = false;
    protected $is_privacy_restricted = false;

    /**
     * MailChimp_WooCommerce_Single_Order constructor.
     * @param null $id
     * @param null $cart_session_id
     * @param null $campaign_id
     * @param null $landing_site
     */
    public function __construct($id = null, $cart_session_id = null, $campaign_id = null, $landing_site = null)
    {
        if (!empty($id)) $this->id = $id;
        if (!empty($cart_session_id)) $this->cart_session_id = $cart_session_id;
        if (!empty($campaign_id)) $this->campaign_id = $campaign_id;
        if (!empty($landing_site)) $this->landing_site = $landing_site;
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_Single_Order
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
        if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
            mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
            return false;
        }

        $store_id = mailchimp_get_store_id();

        if (!($woo_order_number = $this->getRealOrderNumber())) {
            mailchimp_log('order_submit.failure', "There is no real order number to use.");
            return false;
        }

        // see if we need to prevent this order from being submitted.
        if ($this->shouldPreventSubmission()) {
            if ($this->is_amazon_order) {
                mailchimp_log('validation.amazon', "Order #{$woo_order_number} was placed through Amazon. Skipping!");
            } elseif ($this->is_privacy_restricted) {
                mailchimp_log('validation.gdpr', "Order #{$woo_order_number} is GDPR restricted. Skipping!");
            }
            return false;
        }

        $job = new MailChimp_WooCommerce_Transform_Orders();

        // set the campaign ID
        $job->campaign_id = $this->campaign_id;

        $call = ($api_response = $api->getStoreOrder($store_id, $woo_order_number)) ? 'updateStoreOrder' : 'addStoreOrder';

        $new_order = $call === 'addStoreOrder';

        if (!$this->is_admin_save && $new_order && $this->is_update === true) {
            return false;
        }

        // if we already pushed this order into the system, we need to unset it now just in case there
        // was another campaign that had been sent and this was only an order update.
        if (!$new_order) {
            $job->campaign_id = null;
            $this->campaign_id = null;
            $this->landing_site = null;
        }

        // will either add or update the order
        try {

            if (!($order_post = get_post($this->id))) {
                return false;
            }

            // transform the order
            $order = $job->transform($order_post);

            // will be the same as the customer id. an md5'd hash of a lowercased email.
            $this->cart_session_id = $order->getCustomer()->getId();

            // see if we have a campaign ID already from the order transformer / cookie.
            $campaign_id = $order->getCampaignId();

            // if the campaign ID is empty, and we have a cart session id
            if (empty($campaign_id) && !empty($this->cart_session_id)) {
                // pull the cart info from Mailchimp
                if (($abandoned_cart_record = $api->getCart($store_id, $this->cart_session_id))) {
                    // set the campaign ID
                    $order->setCampaignId($this->campaign_id = $abandoned_cart_record->getCampaignID());
                }
            }

            // delete the AC cart record.
            $deleted_abandoned_cart = !empty($this->cart_session_id) && $api->deleteCartByID($store_id, $this->cart_session_id);

            // skip amazon orders and skip privacy protected orders.
            if ($order->isFlaggedAsAmazonOrder()) {
                mailchimp_log('validation.amazon', "Order #{$woo_order_number} was placed through Amazon. Skipping!");
                return false;
            } elseif ($order->isFlaggedAsPrivacyProtected()) {
                mailchimp_log('validation.gdpr', "Order #{$woo_order_number} is GDPR restricted. Skipping!");
                return false;
            }

            // if the order is in failed or cancelled status - and it's brand new, we shouldn't submit it.
            if ($new_order && in_array($order->getFinancialStatus(), array('failed', 'cancelled'))) {
                return false;
            }

            // if the order is brand new, and we already have a paid status,
            // we need to double up the post to force the confirmation + the invoice.
            if ($new_order && $order->getFinancialStatus() === 'paid') {
                $order->setFinancialStatus('pending');
                $order->confirmAndPay(true);
            }

            mailchimp_debug('order_submit', "#{$woo_order_number}", $order->toArray());

            // if we're overriding this we need to set it here.
            if ($this->partially_refunded) {
                $order->setFinancialStatus('partially_refunded');
            }

            $log = "$call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}";

            // only do this stuff on new orders
            if ($new_order) {
                // apply a campaign id if we have one.
                if (!empty($this->campaign_id)) {
                    try {
                        $order->setCampaignId($this->campaign_id);
                        $log .= ' :: campaign id ' . $this->campaign_id;
                    }
                    catch (\Exception $e) {
                        mailchimp_log('single_order_set_campaign_id.error', 'No campaign added to order, with provided ID: '. $this->campaign_id. ' :: '. $e->getMessage(). ' :: in '.$e->getFile().' :: on '.$e->getLine());
                    }
                }

                // apply the landing site if we have one.
                if (!empty($this->landing_site)) {
                    $log .= ' :: landing site ' . $this->landing_site;
                    $order->setLandingSite($this->landing_site);
                }
            }

            try {
                // update or create
                $api_response = $api->$call($store_id, $order, false);
            } catch (\Exception $e) {
                // if for whatever reason we get a product not found error, we need to iterate
                // through the order items, and use a "create mode only" on each product
                // then re-submit the order once they're in the database again.
                if (mailchimp_string_contains($e->getMessage(), 'product with the provided ID')) {
                    $api->handleProductsMissingFromAPI($order);
                    // make another attempt again to add the order.
                    $api_response = $api->$call($store_id, $order, false);
                } elseif (mailchimp_string_contains($e->getMessage(), 'campaign with the provided ID')) {
                    // the campaign was invalid, we need to remove it and re-submit
                    $order->setCampaignId(null);
                    // make another attempt again to add the order.
                    $api_response = $api->$call($store_id, $order, false);
                } else {
                    throw $e;
                }
            }

            if (empty($api_response)) {
                mailchimp_error('order_submit.failure', "$call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()} produced a blank response from MailChimp");
                return $api_response;
            }

            if ($deleted_abandoned_cart) {
                $log .= " :: abandoned cart deleted [{$this->cart_session_id}]";
            }

            mailchimp_log('order_submit.success', $log);

            // if the customer has a flag to double opt in - we need to push this data over to MailChimp as pending
            // before the order is submitted.
            if ($order->getCustomer()->requiresDoubleOptIn() && $order->getCustomer()->getOriginalSubscriberStatus()) {
                try {
                    $list_id = mailchimp_get_list_id();
                    $merge_fields = $order->getCustomer()->getMergeFields();
                    $email = $order->getCustomer()->getEmailAddress();

                    try {
                        $member = $api->member($list_id, $email);
                        if ($member['status'] === 'transactional') {

                            $api->update($list_id, $email, 'pending', $merge_fields);
                            mailchimp_tell_system_about_user_submit($email, mailchimp_get_subscriber_status_options('pending'), 60);
                            mailchimp_log('double_opt_in', "Updated {$email} Using Double Opt In - previous status was '{$member['status']}'", $merge_fields);
                        }
                    } catch (\Exception $e) {
                        // if the error code is 404 - need to subscribe them becausce it means they were not on the list.
                        if ($e->getCode() == 404) {
                            $api->subscribe($list_id, $email, false, $merge_fields);
                            mailchimp_tell_system_about_user_submit($email, mailchimp_get_subscriber_status_options(false), 60);
                            mailchimp_log('double_opt_in', "Subscribed {$email} Using Double Opt In", $merge_fields);
                        } else {
                            mailchimp_error('double_opt_in.update', $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    mailchimp_error('double_opt_in.create', $e->getMessage());
                }
            }

            return $api_response;
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            $this->release();
            mailchimp_error('order_submit.error', mailchimp_error_trace($e, "RateLimited :: #{$this->id}"));
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            mailchimp_error('order_submit.tracing_error', $e);
            if (!isset($order)) {
                // transform the order
                $order = $job->transform(get_post($this->id));
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
                    mailchimp_error('order_submit.error', mailchimp_error_trace($e, 'deleting-customer-re-add :: #'.$this->id));
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getRealOrderNumber()
    {
        try {
            if (empty($this->id) || !($order_post = get_post($this->id))) {
                return false;
            }
            $woo = new WC_Order($order_post);
            return $this->woo_order_number = $woo->get_order_number();
        } catch (\Exception $e) {
            $this->woo_order_number = false;
            mailchimp_error('order_sync.failure', mailchimp_error_trace($e, "{$this->id} could not be loaded"));
            return false;
        }
    }

    /**
     * @return bool
     */
    public function shouldPreventSubmission()
    {
        try {
            if (empty($this->id) || !($order_post = get_post($this->id))) {
                return false;
            }
            $woo = new WC_Order($order_post);
            $email = $woo->get_billing_email();

            // just skip these altogether because we can't submit any amazon orders anyway.
            $this->is_amazon_order = mailchimp_email_is_amazon($email);

            // see if this is a privacy restricted email address.
            $this->is_privacy_restricted = mailchimp_email_is_privacy_protected($email);

            return $this->is_amazon_order || $this->is_privacy_restricted;
        } catch (\Exception $e) {
            return false;
        }
    }
}


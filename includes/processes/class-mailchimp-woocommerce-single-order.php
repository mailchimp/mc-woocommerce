<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
$HPOS_enabled = false;
if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {	$HPOS_enabled = true; }
/* HPOS_enabled - flag for data from db, where hpos is enabled or not */

class MailChimp_WooCommerce_Single_Order extends Mailchimp_Woocommerce_Job
{
    public $id;
    public $cart_session_id;
    public $campaign_id;
    public $landing_site;
    public $user_language;
    public $is_update = false;
    public $is_admin_save = false;
    public $is_full_sync = false;
    public $partially_refunded = false;
    public $gdpr_fields = false;
    protected $woo_order_number = false;
    protected $is_amazon_order = false;
    protected $is_privacy_restricted = false;

	/**
	 * MailChimp_WooCommerce_Single_Order constructor.
	 *
	 * @param null $id
	 * @param null $cart_session_id
	 * @param null $campaign_id
	 * @param null $landing_site
	 * @param null $user_language
	 * @param null $gdpr_fields
	 */
    public function __construct($id = null, $cart_session_id = null, $campaign_id = null, $landing_site = null, $user_language = null, $gdpr_fields = null)
    {
        if (!empty($id)) $this->id = $id;
        if (!empty($cart_session_id)) $this->cart_session_id = $cart_session_id;
        if (!empty($campaign_id)) $this->campaign_id = $campaign_id;
        if (!empty($landing_site)) $this->landing_site = $landing_site;
        if (!empty($user_language)) $this->user_language = $user_language;
        if (!empty($gdpr_fields)) $this->gdpr_fields = $gdpr_fields;
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
	 * @param $is_full_sync
	 *
	 * @return $this
	 */
    public function set_full_sync($is_full_sync)
    {
        $this->is_full_sync = $is_full_sync;

        return $this;
    }

	/**
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function handle()
    {
        $this->process();
        return false;
    }

	/**
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
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

        $order = wc_get_order($woo_order_number);
		$wordpress_user_id = null;

        if ( $order ) {
			$wordpress_user_id = $order->get_user_id();
            $user   = get_user_by( 'ID', $order->get_user_id() );
            $allowed_roles = array('customer', 'subscriber');
            $allowed_roles = apply_filters('mailchimp_campaign_user_roles', $allowed_roles );

            if (  $user && count( array_intersect($allowed_roles,  $user->roles) ) === 0 ) {
                mailchimp_log('order_process', "Order #{$woo_order_number} skipped, user #{$order->get_user_id()} user role is not in the list");
                return false;
            }
        }

        $job = new MailChimp_WooCommerce_Transform_Orders();

        // set the campaign ID
        $job->campaign_id = $this->campaign_id;

        try {
            $call = ($api_response = $api->getStoreOrder($store_id, $woo_order_number, true)) ? 'updateStoreOrder' : 'addStoreOrder';
        } catch (Exception $e) {
            if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
                sleep(2);
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "RateLimited :: #{$this->id}"));
                $this->retry();
            }
            $call = 'addStoreOrder';
        }

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

	    $email = null;

        // will either add or update the order
        try {

            if($HPOS_enabled){                 
                if (!($order_post = wc_get_order($this->id))) {
                    return false;
                }
            }
		    else {                 
                if (!($order_post = get_post($this->id))) {
                    return false;
                }
            }		

            /*if (!($order_post = get_post($this->id))) {
                return false;
            }*/

            // transform the order
            $order = $job->transform($order_post);

            // don't allow this to happen.
            if ($order->getOriginalWooStatus() === 'checkout-draft') {
                mailchimp_debug('filter', "Order {$woo_order_number} is in draft status and can not be submitted");
                return false;
            }

            // if the order is new, and has been flagged as a status that should not be pushed over to
            // Mailchimp - just ignore it and log it.
            if ($new_order && $order->shouldIgnoreIfNotInMailchimp()) {
                mailchimp_debug('filter', "order {$woo_order_number} is in {$order->getOriginalWooStatus()} status, and is being skipped for now.");
                return false;
            }

            // see if we need to prevent this order from being submitted.
            $email = $order->getCustomer()->getEmailAddress();
            // see if we have a bad email

            if ($this->shouldSkipOrder($email, $order->getId())) {
                return false;
            }

            $status = $order->getCustomer()->getOptInStatus();
            $transient_key = mailchimp_hash_trim_lower($email).".mc.status";
            $current_status = null;

            if (!$status && mailchimp_submit_subscribed_only()) {
                try {
                    $subscriber = $api->member(mailchimp_get_list_id(), $email);
                    $current_status = $subscriber['status'];
                    mailchimp_set_transient($transient_key, $current_status);
                    if ($current_status != 'subscribed') {
                        mailchimp_debug('filter', "#{$woo_order_number} was blocked due to subscriber only settings and current mailchimp status was {$current_status}");
                        return false;
                    }
                } catch (Exception $e) {
                    mailchimp_set_transient($transient_key, $current_status);
                    mailchimp_debug('filter', "#{$woo_order_number} was blocked due to subscriber only settings");
                    return false;
                }
                $pulled_member = true;
            }

            if ($this->is_full_sync) {
                // see if this store has the auto subscribe setting enabled on initial sync
                $plugin_options = get_option('mailchimp-woocommerce');
                $should_auto_subscribe = (bool) $plugin_options['mailchimp_auto_subscribe'];

                // since we're syncing the customer for the first time, this is where we need to add the override
                // for subscriber status. We don't get the checkbox until this plugin is actually installed and working!
                if (!$status) {
                    try {
                        if (!$pulled_member) {
                            $subscriber = $api->member(mailchimp_get_list_id(), $order->getCustomer()->getEmailAddress());
                            $current_status = $subscriber['status'];
                            $pulled_member = true;
                        }

                        if ($pulled_member && $current_status != 'archived' && isset($subscriber)) {
                            $status = !in_array( $subscriber['status'], array('unsubscribed', 'transactional') );
                            $order->getCustomer()->setOptInStatus($status);
                            if ($subscriber['status'] === 'transactional') {
                                $new_status = '0';
                            } else if ($subscriber['status'] === 'subscribed') {
                                $new_status = '1';
                            } else {
                                $new_status = $subscriber['status'];
                            }

                            // if the wordpress user id is not empty, and the status is subscribed, we can update the
	                        // subscribed status meta so it reflects the current status of Mailchimp during a sync.

                            if ($wordpress_user_id && $current_status) {
                                update_user_meta($wordpress_user_id, 'mailchimp_woocommerce_is_subscribed', $new_status);
	                        }
                        }
                    } catch (Exception $e) {
                        if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
                            mailchimp_error('order_sync.error', mailchimp_error_trace($e, "GET subscriber :: {$order->getId()}"));
                            throw $e;
                        }
                        // if they are using double opt in, we need to pass this in as false here so it doesn't auto subscribe.
                        try {
                            $doi = mailchimp_list_has_double_optin(true);
                        } catch (Exception $e_doi) {
                            throw $e_doi;
                        }

                        $status = $doi ? false : $should_auto_subscribe;
                        $order->getCustomer()->setOptInStatus($status);
                    }
                }
            }

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

            if ($order->getOriginalWooStatus() !== 'pending') {
                // delete the AC cart record.
                $deleted_abandoned_cart = !empty($this->cart_session_id) && $api->deleteCartByID($store_id, $this->cart_session_id);
            }

            // skip amazon orders and skip privacy protected orders.
            if ($order->isFlaggedAsAmazonOrder()) {
                mailchimp_log('validation.amazon', "Order #{$woo_order_number} was placed through Amazon. Skipping!");
                return false;
            } elseif ($order->isFlaggedAsPrivacyProtected()) {
                mailchimp_log('validation.gdpr', "Order #{$woo_order_number} is GDPR restricted. Skipping!");
                return false;
            }

            if ($new_order) {
                // if single sync and
                // if the order is in failed or cancelled status - and it's brand new, we shouldn't submit it.
                if (!$this->is_full_sync && in_array($order->getFinancialStatus(), array('failed', 'cancelled')) || $order->getOriginalWooStatus() === 'pending') {
                    mailchimp_log('order_submit', "#{$order->getId()} has a financial status of {$order->getFinancialStatus()} and was skipped.");
                    return false;
                }
                // if full sync and
                // if the original woocommerce status is actually pending, we need to skip these on new orders because
                // it is probably happening due to 3rd party payment processing and it's still pending. These orders
                // don't always make it over because someone could be cancelling out of the payment there.
                if ($this->is_full_sync && !in_array(strtolower($order->getFinancialStatus()), array('processing', 'completed', 'paid'))) {
                    mailchimp_log('order_submit', "#{$order->getId()} has a financial status of {$order->getFinancialStatus()} and was skipped.");
                    return false;
                }

            }

            // if the order is brand new, and we already have a paid status,
            // we need to double up the post to force the confirmation + the invoice.
            if ($new_order && $order->getFinancialStatus() === 'paid') {
                $order->setFinancialStatus('pending');
                $order->confirmAndPay(true);
            }

            // if we're overriding this we need to set it here.
            if ($this->partially_refunded) {
                $order->setFinancialStatus('partially_refunded');
            }

            $log = "$call :: #{$order->getId()} :: email: {$email}";

            // if we have the saved order meta from previous syncs let's use it.
            // This should help with reporting after people may have disconnected and reconnected to a new store.
            if (($saved = get_post_meta($order_post->ID, 'mailchimp_woocommerce_campaign_id', true))) {
                $this->campaign_id = $saved;
            }
            // only do this stuff on new orders
            if ($new_order) {

            	// if the campaign ID is empty, let's try to pull the last clicked campaign from Mailchimp.
	            // but only do this if we're not in a syncing status.
            	if (empty($this->campaign_id) && !$this->is_full_sync) {
            		// see if we have a saved version
		            // pull the last clicked campaign for this email address
		            $job = new MailChimp_WooCommerce_Pull_Last_Campaign($email);
		            $this->campaign_id = $job->handle();

		            if (!empty($this->campaign_id)) {
		            	mailchimp_debug('campaign_id', "Pulled campaign tracking from mailchimp user activity for {$email}");
		            }
	            }

                // apply a campaign id if we have one.
                if (!empty($this->campaign_id)) {
                    try {
                        $order->setCampaignId($this->campaign_id);
                        $log .= ' :: campaign id ' . $this->campaign_id;
                        // save it for later if we don't have this value.
	                    if($HPOS_enabled){ 
                            $order_c = wc_get_order( $order_post->ID );
                            $order_c->update_meta_data('mailchimp_woocommerce_campaign_id', $campaign_id);
                            $order_c->save();
                        }
                        else{ update_post_meta($order_post->ID, 'mailchimp_woocommerce_campaign_id', $campaign_id); }		
                        //update_post_meta($order_post->ID, 'mailchimp_woocommerce_campaign_id', $campaign_id);
                    }
                    catch (Exception $e) {
                        mailchimp_log('single_order_set_campaign_id.error', 'No campaign added to order, with provided ID: '. $this->campaign_id. ' :: '. $e->getMessage(). ' :: in '.$e->getFile().' :: on '.$e->getLine());
                    }
                }

                // apply the landing site if we have one.
                if (!empty($this->landing_site)) {
                    $log .= ' :: landing site ' . $this->landing_site;
                    $order->setLandingSite($this->landing_site);
                }
            }

            if ($this->is_full_sync) {
                $line_items = $order->items();

                // if we don't have any line items, we need to create the mailchimp product
                // with a price of 1.00 and we'll use the inventory quantity to adjust correctly.
                if (empty($line_items) || !count($line_items)) {

                    // this will create an empty product placeholder, or return the pre populated version if already
                    // sent to Mailchimp.
                    $product = $api->createEmptyLineItemProductPlaceholder();

                    $line_item = new MailChimp_WooCommerce_LineItem();
                    $line_item->setId($product->getId());
                    $line_item->setPrice(1);
                    $line_item->setProductId($product->getId());
                    $line_item->setProductVariantId($product->getId());
                    $line_item->setQuantity((int) $order->getOrderTotal());

                    $order->addItem($line_item);

                    mailchimp_log('order_submit.error', "Order {$order->getId()} does not have any line items, so we are using 'empty_line_item_placeholder' instead.");
                }
            }

            mailchimp_debug('order_submit', " #{$woo_order_number}", $order->toArray());

            try {
                // update or create
                $api_response = $api->$call($store_id, $order, false);
            } catch (Exception $e) {
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
                mailchimp_error('order_submit.failure', "$call :: #{$order->getId()} :: email: {$email} produced a blank response from MailChimp");
                return isset($api_response) ? $api_response : false;
            }

            if (isset($deleted_abandoned_cart) && $deleted_abandoned_cart) {
                $log .= " :: abandoned cart deleted [{$this->cart_session_id}]";
            }

            // if we require double opt in on the list, and the customer requires double opt in,
            // we should mark them as pending so they get the opt in email now.
            if (mailchimp_list_has_double_optin()) {
                $status_if_new = $order->getCustomer()->getOriginalSubscriberStatus() ? 'pending' : 'transactional';
            } else {
                // if true, subscribed - otherwise transactional
                $status_if_new = $order->getCustomer()->getOptInStatus() ? 'subscribed' : 'transactional';
            }

            // if this is not currently in mailchimp - and we have the saved GDPR fields from
            // we can use the post meta for gdpr fields that were saved during checkout.
            if (!$this->is_full_sync && $new_order && empty($this->gdpr_fields)) {
                $this->gdpr_fields = get_post_meta($order->getId(), 'mailchimp_woocommerce_gdpr_fields', true);
            }

            // Maybe sync subscriber to set correct member.language
            mailchimp_member_data_update($email, $this->user_language, 'order', $status_if_new, $order, $this->gdpr_fields, !$this->is_full_sync);

            mailchimp_log('order_submit.success', $log);

            if ($this->is_full_sync && $new_order) {
                // if the customer has a flag to double opt in - we need to push this data over to MailChimp as pending
                //TODO: RYAN: this is the only place getOriginalSubscriberStatus() is called, but the iterate method uses another way. 
                // mailchimp_update_member_with_double_opt_in($order, ($should_auto_subscribe || $status));
                mailchimp_update_member_with_double_opt_in($order, ((isset($should_auto_subscribe) && $should_auto_subscribe) || $order->getCustomer()->getOriginalSubscriberStatus()));
            }

            return $api_response;
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            mailchimp_error('order_submit.error', mailchimp_error_trace($e, "RateLimited :: #{$this->id}"));
            $this->applyRateLimitedScenario();
            throw $e;
        } catch (MailChimp_WooCommerce_ServerError $e) {
            mailchimp_error('order_submit.error', mailchimp_error_trace($e, "{$call} :: #{$this->id}"));
            throw $e;
        } catch (MailChimp_WooCommerce_Error $e) {
            mailchimp_error('order_submit.error', mailchimp_error_trace($e, "{$call} :: #{$this->id}"));
            throw $e;
        } catch (Exception $e) {
            $message = strtolower($e->getMessage());
            mailchimp_error('order_submit.tracing_error', $e);
            if (!isset($order)) {
                // transform the order
                
                if($HPOS_enabled){                 
                    $order = $job->transform(wc_get_order($this->id));                    
                }
                else {                 
                    $order = $job->transform(get_post($this->id));
                }		

                /*$order = $job->transform(get_post($this->id));*/
                $this->cart_session_id = $order->getCustomer()->getId();
            }
            // this can happen when a customer changes their email.
            if (isset($order) && strpos($message, 'not be changed')) {
                try {
                    mailchimp_log('order_submit.deleting_customer', "#{$order->getId()} :: email: {$email}");
                    // delete the customer before adding it again.
                    $api->deleteCustomer($store_id, $order->getCustomer()->getId());
                    // update or create
                    $api_response = $api->$call($store_id, $order, false);
                    $log = "Deleted Customer :: $call :: #{$order->getId()} :: email: {$email}";
                    if (!empty($job->campaign_id)) {
                        $log .= ' :: campaign id '.$job->campaign_id;
                    }
                    mailchimp_log('order_submit.success', $log);
                    // if we're adding a new order and the session id is here, we need to delete the AC cart record.
                    if (!empty($this->cart_session_id)) {
                        $api->deleteCartByID($store_id, $this->cart_session_id);
                    }
                    return $api_response;
                } catch (Exception $e) {
                    mailchimp_error('order_submit.error', mailchimp_error_trace($e, 'deleting-customer-re-add :: #'.$this->id));
                }
            }
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function getRealOrderNumber()
    {
        try {

            if($HPOS_enabled){                 
                if (empty($this->id) || !($order_post = wc_get_order($this->id))) {
                    return false;
                }
            }
		    else {                 
                if (empty($this->id) || !($order_post = get_post($this->id))) {
                    return false;
                }
            }		

            /*if (empty($this->id) || !($order_post = get_post($this->id))) {
                return false;
            }*/


            $woo = wc_get_order($order_post);
            if ( !$woo )
                mailchimp_log('order_sync.failure', "Order #{$this->id}. Can’t submit order without a valid ID");

            return $this->woo_order_number = $woo ? $woo->get_order_number() : false;
        } catch (Exception $e) {
            $this->woo_order_number = false;
            mailchimp_error('order_sync.failure', mailchimp_error_trace($e, "{$this->id} could not be loaded"));
            return false;
        }
    }

    /**
     * @param $email
     * @param $order_id
     * @return bool
     */
    protected function shouldSkipOrder($email, $order_id)
    {
        if (!is_email($email)) {
            mailchimp_log('validation.bad_email', "Order #{$order_id} has an invalid email address. Skipping!");
            return true;
        }

        // make sure we can submit this order to MailChimp or skip it.
        if (mailchimp_email_is_amazon($email)) {
            mailchimp_log('validation.amazon', "Order #{$order_id} was placed through Amazon. Skipping!");
            return true;
        }

        if (mailchimp_email_is_privacy_protected($email)) {
            mailchimp_log('validation.gdpr', "Order #{$order_id} is GDPR restricted. Skipping!");
            return true;
        }

        return false;
    }
}


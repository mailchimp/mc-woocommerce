<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Orders extends MailChimp_WooCommerce_Abstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_orders';
    public $items = array();

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'orders';
    }

    /**
     * @param $item
     * @return bool|mixed
     * @throws Exception
     */
    protected function iterate($item)
    {
        if ($item instanceof MailChimp_WooCommerce_Order) {

            // see if we need to prevent this order from being submitted.
            $email = $item->getCustomer()->getEmailAddress();

            // see if we have a bad email
            if ($this->shouldSkipOrder($email, $item->getId())) {
                return false;
            }

            // see if this store has the auto subscribe setting enabled on initial sync
            $should_auto_subscribe = (bool) $this->getOption('mailchimp_auto_subscribe', true);

            // since we're syncing the customer for the first time, this is where we need to add the override
            // for subscriber status. We don't get the checkbox until this plugin is actually installed and working!
            if (!($status = $item->getCustomer()->getOptInStatus())) {
                try {
                    $subscriber = $this->mailchimp()->member(mailchimp_get_list_id(), $item->getCustomer()->getEmailAddress());
                    $status = !in_array($subscriber['status'], array('unsubscribed', 'transactional'));
                } catch (\Exception $e) {
                    if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
                        mailchimp_error('order_sync.error', mailchimp_error_trace($e, "GET subscriber :: {$item->getId()}"));
                        throw $e;
                    }
                    $status = $should_auto_subscribe;
                }
                $item->getCustomer()->setOptInStatus($status);
            }

            try {
                $type = $this->mailchimp()->getStoreOrder($this->store_id, $item->getId(), true) ? 'update' : 'create';
            } catch (MailChimp_WooCommerce_Error $e) {
                if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
                    mailchimp_error('order_sync.error', mailchimp_error_trace($e, "GET order :: {$item->getId()}"));
                    throw $e;
                }
                $type = 'create';
            }

            $call = $type === 'create' ? 'addStoreOrder' : 'updateStoreOrder';

            try {

                // if the order is in failed or cancelled status - and it's brand new, we shouldn't submit it.
                if ($call === 'addStoreOrder' && !in_array(strtolower($item->getFinancialStatus()), array('processing', 'completed', 'paid'))) {
                    mailchimp_log('order_sync', "#{$item->getId()} has a financial status of {$item->getFinancialStatus()} and was skipped.");
                    return false;
                }

                mailchimp_debug('order_sync', "#{$item->getId()}", $item->toArray());

                try {
                    // make the call
                    $response = $this->mailchimp()->$call($this->store_id, $item, false);
                } catch (\Exception $e) {
                    // if for whatever reason we get a product not found error, we need to iterate
                    // through the order items, and use a "create mode only" on each product
                    // then re-submit the order once they're in the database again.
                    if (mailchimp_string_contains($e->getMessage(), 'product with the provided ID')) {
                        $this->mailchimp()->handleProductsMissingFromAPI($item);
                        // make the call again after the product updates
                        $response = $this->mailchimp()->$call($this->store_id, $item, false);
                    } else {
                        throw $e;
                    }
                }

                if (empty($response)) {
                    mailchimp_error('order_submit.failure', "$call :: #{$item->getId()} :: email: {$item->getCustomer()->getEmailAddress()} produced a blank response from MailChimp");
                    return $response;
                }

                mailchimp_log('order_submit.success', "$call :: #{$item->getId()} :: email: {$item->getCustomer()->getEmailAddress()}");

                $this->items[] = array('response' => $response, 'item' => $item);

                // update the list member if they've got double opt in enabled, and this is a new order.
                if ($type === 'create') {
                    mailchimp_update_member_with_double_opt_in($item, ($should_auto_subscribe || $status));
                }

                return $response;
            } catch (MailChimp_WooCommerce_RateLimitError $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                throw $e;
            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            } catch (Exception $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            }
        }

        mailchimp_debug('order_submit', 'no order found', $item);

        return false;
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('order_submit.completed', 'Done with the order sync.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();

        // this is the last thing we're doing so it's complete as of now.
        $this->flagStopSync();
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

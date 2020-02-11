<?php

/**
 * Class MailChimp_WooCommerce_Transform_Orders
 */
class MailChimp_WooCommerce_Transform_Orders
{
    public $campaign_id = null;
    protected $is_syncing = false;

    /**
     * @param int $page
     * @param int $limit
     * @return object
     * @throws Exception
     */
    public function compile($page = 1, $limit = 5)
    {
        $this->is_syncing = true;

        $response = (object) array(
            'endpoint' => 'orders',
            'page' => $page ? $page : 1,
            'limit' => (int) $limit,
            'count' => 0,
            'valid' => 0,
            'drafts' => 0,
            'stuffed' => false,
            'items' => array(),
        );

        if ((($orders = $this->getOrderPosts($page, $limit)) && !empty($orders))) {
            foreach ($orders as $post) {
                $response->count++;

                if ($post->post_status === 'auto-draft') {
                    $response->drafts++;
                    continue;
                }

                try {
                    $order = $this->transform($post);
                    if (!$order->isFlaggedAsAmazonOrder()) {
                        $response->valid++;
                        $response->items[] = $order;
                    }
                } catch (\Exception $e) {
                    mailchimp_error('initial_sync', $e->getMessage(), array('post' => $post));
                }
            }
        }

        $response->stuffed = ($response->count > 0 && (int) $response->count === (int) $limit) ? true : false;
        $this->is_syncing = false;

        return $response;
    }

    /**
     * @param WP_Post $post
     * @return MailChimp_WooCommerce_Order
     * @throws Exception
     */
    public function transform(WP_Post $post)
    {
        $woo = wc_get_order($post);

        $order = new MailChimp_WooCommerce_Order();

        // if the woo get order returns an empty value, we need to skip the whole thing.
        if (empty($woo)) {
            mailchimp_error('sync', 'get woo post was not found for order '.$post->ID);
            return $order;
        }

        // if the woo object does not have a "get_billing_email" method, then we need to skip this until
        // we know how to resolve these types of things.
        if (!method_exists($woo, 'get_billing_email')) {
            $message = "Post ID {$post->ID} was an order refund. Skipping this.";
            if ($this->is_syncing) {
                throw new MailChimp_WooCommerce_Error($message);
            }
            mailchimp_error('initial_sync', $message, array('post' => $post, 'order_class' => get_class($woo)));
            return $order;
        }

        $customer = $this->buildCustomerFromOrder($woo);

        $email = $woo->get_billing_email();

        // just skip these altogether because we can't submit any amazon orders anyway.
        if (mailchimp_email_is_amazon($email)) {
            return $order->flagAsAmazonOrder(true);
        } elseif (mailchimp_email_is_privacy_protected($email)) {
            return $order->flagAsPrivacyProtected(true);
        }

        $order->setId($woo->get_order_number());

        // if we have a campaign id let's set it now.
        if (!empty($this->campaign_id)) {
            try {
                $order->setCampaignId($this->campaign_id);
            } catch (\Exception $e) {
                mailchimp_log('transform_order_set_campaign_id.error', 'No campaign added to order, with provided ID: '. $this->campaign_id. ' :: '. $e->getMessage(). ' :: in '.$e->getFile().' :: on '.$e->getLine());
            }
        }

        $order->setProcessedAt($woo->get_date_created()->setTimezone(new \DateTimeZone('UTC')));

        $order->setCurrencyCode($woo->get_currency());

        // grab the current statuses - this will end up being custom at some point.
        $statuses = $this->getOrderStatuses();

        // grab the order status and set it into the object for future comparison.
        $order->setOriginalWooStatus(($status = $woo->get_status()));

        // if the order is "on-hold" status, and is not currently in Mailchimp, we need to ignore it
        // because the payment gateways are putting this on hold while they navigate to the payment processor
        // and they technically haven't paid yet.
        if (in_array($status, array('on-hold', 'failed'))) {
            $order->flagAsIgnoreIfNotInMailchimp(true);
        }

        // map the fulfillment and financial statuses based on the map above.
        $fulfillment_status = array_key_exists($status, $statuses) ? $statuses[$status]->fulfillment : null;
        $financial_status = array_key_exists($status, $statuses) ? $statuses[$status]->financial : $status;

        // set the fulfillment_status
        $order->setFulfillmentStatus($fulfillment_status);

        // set the financial status
        $order->setFinancialStatus($financial_status);

        // if the status is processing, we need to send this one first, then send a 'paid' status right after.
        if ($status === 'processing') {
            $order->confirmAndPay(true);
        }

        // only set this if the order is cancelled.
        if ($status === 'cancelled') {
            if (method_exists($woo, 'get_date_modified')) {
                $order->setCancelledAt($woo->get_date_modified()->setTimezone(new \DateTimeZone('UTC')));
            }
        }

        // set the total
        $order->setOrderTotal($order_total = $woo->get_total());

        // set the order URL if it's valid.
        if (($view_order_url = $woo->get_view_order_url()) && wc_is_valid_url($view_order_url)) {
            $order->setOrderURL($woo->get_view_order_url());
        }

        // set the total if refund
        if (($refund = $woo->get_total_refunded()) && $refund > 0){
            // If there's a refund, apply to order total.
            $order_spent = $order_total - $refund;
            $order->setOrderTotal($order_spent);
        }

        // if we have any tax
        $order->setTaxTotal($woo->get_total_tax());

        // if we have shipping
        if (method_exists($woo, 'get_shipping_total')) {
            $order->setShippingTotal($woo->get_shipping_total());
        }

        // set the order discount
        $order->setDiscountTotal($woo->get_total_discount());

        // set the customer
        $order->setCustomer($customer);

        // apply the addresses to the order
        $order->setShippingAddress($this->transformShippingAddress($woo));
        $order->setBillingAddress($this->transformBillingAddress($woo));

        // loop through all the order items
        foreach ($woo->get_items() as $key => $order_detail) {
            /** @var WC_Order_Item_Product $order_detail */

            // add it into the order item container.
            $item = $this->transformLineItem($key, $order_detail);

            $product = $order_detail->get_product();

            // if we can't find the product, we need to populate this
            if (empty($product)) {
                if (($empty_order_item = MailChimp_WooCommerce_Transform_Products::missing_order_item($order_detail))) {
                    $item->setFallbackTitle($empty_order_item->getTitle());
                    $item->setProductId($empty_order_item->getId());
                    $item->setProductVariantId($empty_order_item->getId());
                    $order->addItem($item);
                    continue;
                }
            }

            // if we don't have a product post with this id, we need to add a deleted product to the MC side
            if (!$product || ($trashed = 'trash' === $product->get_status())) {

                $pid = $order_detail->get_product_id();
                $title = $order_detail->get_name();
                
                try {
                    $deleted_product = MailChimp_WooCommerce_Transform_Products::deleted($pid, $title);
                } catch (\Exception $e) {
                    mailchimp_log('order.items.error', "Order #{$woo->get_id()} :: Product {$pid} does not exist!");
                    continue;
                }

                // check if it exists, otherwise create a new one.
                if ($deleted_product) {
                    // swap out the old item id and product variant id with the deleted version.
                    $item->setProductId("deleted_{$pid}");
                    $item->setProductVariantId("deleted_{$pid}");
                    
                    // add the item and continue on the loop.
                    $order->addItem($item);
                    continue;
                }

                mailchimp_log('order.items.error', "Order #{$woo->get_id()} :: Product {$pid} does not exist!");
                continue;
            }

            $order->addItem($item);
        }

        return $order;
    }

    /**
     * @param WC_Abstract_Order $order
     * @return MailChimp_WooCommerce_Customer
     * @throws Exception
     */
    public function buildCustomerFromOrder($order)
    {
        $customer = new MailChimp_WooCommerce_Customer();

        // attach the wordpress user to the Mailchimp customer object.
        $customer->setWordpressUser($order->get_user());

        $customer->setId(mailchimp_hash_trim_lower($order->get_billing_email()));
        $customer->setCompany($order->get_billing_company());
        $customer->setEmailAddress(trim($order->get_billing_email()));
        $customer->setFirstName($order->get_billing_first_name());
        $customer->setLastName($order->get_billing_last_name());
        $customer->setAddress($this->transformBillingAddress($order));

        if (!($stats = $this->getCustomerOrderTotals($order))) {
            $stats = (object) array('count' => 0, 'total' => 0);
        }

        $customer->setOrdersCount($stats->count);
        $customer->setTotalSpent($stats->total);

        // we now hold this data inside the customer object for usage in the order handler class
        // we only update the subscriber status on a member IF they were subscribed.
        $subscribed_on_order = $customer->wasSubscribedOnOrder($order->get_id());

        $customer->setOptInStatus($subscribed_on_order);

        $doi = mailchimp_list_has_double_optin();
        $status_if_new = $doi ? false : $subscribed_on_order;

        $customer->setOptInStatus($status_if_new);

        // if they didn't subscribe on the order, we need to check to make sure they're not already a subscriber
        // if they are, we just need to make sure that we don't unsubscribe them just because they unchecked this box.
        if ($doi || !$subscribed_on_order) {
            try {
                $subscriber = mailchimp_get_api()->member(mailchimp_get_list_id(), $customer->getEmailAddress());

                if ($subscriber['status'] === 'transactional') {
                    $customer->setOptInStatus(false);
                    // when the list requires a double opt in - flag it here.
                    if ($doi) {
                        $customer->requireDoubleOptIn(true);
                    }
                    return $customer;
                } elseif ($subscriber['status'] === 'pending') {
                    $customer->setOptInStatus(false);
                    return $customer;
                }

                $customer->setOptInStatus($subscriber['status'] === 'subscribed');
            } catch (\Exception $e) {
                // if double opt in is enabled - we need to make a request now that subscribes the customer as pending
                // so that the double opt in will actually fire.
                if ($doi && (!isset($subscriber) || empty($subscriber))) {
                    $customer->requireDoubleOptIn(true);
                }
            }
        }

        return $customer;
    }

    /**
     * @param $key
     * @param WC_Order_Item_Product $order_detail
     * @return MailChimp_WooCommerce_LineItem
     */
    protected function transformLineItem($key, $order_detail)
    {
        // fire up a new MC line item
        $item = new MailChimp_WooCommerce_LineItem();
        $item->setId($key);

        // set the fallback title for the order detail name just in case we need to create a product
        // from this order item.
        $item->setFallbackTitle($order_detail->get_name());

        $item->setPrice($order_detail->get_total());
        $item->setProductId($order_detail->get_product_id());
        $variation_id = $order_detail->get_variation_id();
        if (empty($variation_id)) $variation_id = $order_detail->get_product_id();
        $item->setProductVariantId($variation_id);
        $item->setQuantity($order_detail->get_quantity());

        if ($item->getQuantity() > 1) {
            $current_price = $item->getPrice();
            $price = ($current_price/$item->getQuantity());
            $item->setPrice($price);
        }

        return $item;
    }

    /**
     * @param WC_Abstract_Order $order
     * @return MailChimp_WooCommerce_Address
     */
    public function transformBillingAddress(WC_Abstract_Order $order)
    {
        // use the info from the order to compile an address.
        $address = new MailChimp_WooCommerce_Address();
        $address->setAddress1($order->get_billing_address_1());
        $address->setAddress2($order->get_billing_address_2());
        $address->setCity($order->get_billing_city());
        $address->setProvince($order->get_billing_state());
        $address->setPostalCode($order->get_billing_postcode());
        $address->setCountry($order->get_billing_country());
        $address->setPhone($order->get_billing_phone());

        $bfn = $order->get_billing_first_name();
        $bln = $order->get_billing_last_name();

        // if we have billing names set it here
        if (!empty($bfn) && !empty($bln)) {
            $address->setName("{$bfn} {$bln}");
        }

        return $address;
    }

    /**
     * @param WC_Abstract_Order $order
     * @return MailChimp_WooCommerce_Address
     */
    public function transformShippingAddress(WC_Abstract_Order $order)
    {
        $address = new MailChimp_WooCommerce_Address();

        $address->setAddress1($order->get_shipping_address_1());
        $address->setAddress2($order->get_shipping_address_2());
        $address->setCity($order->get_shipping_city());
        $address->setProvince($order->get_shipping_state());
        $address->setPostalCode($order->get_shipping_postcode());
        $address->setCountry($order->get_shipping_country());

        // shipping does not have a phone number, so maybe use this?
        $address->setPhone($order->get_billing_phone());

        $sfn = $order->get_shipping_first_name();
        $sln = $order->get_shipping_last_name();

        // if we have billing names set it here
        if (!empty($sfn) && !empty($sln)) {
            $address->setName("{$sfn} {$sln}");
        }

        return $address;
    }

    /**
     * @param int $page
     * @param int $posts
     * @return array|bool
     */
    public function getOrderPosts($page = 1, $posts = 5)
    {
        $offset = 0;
        if ($page > 1) {
            $offset = (($page-1) * $posts);
        }

        $params = array(
            'post_type' => wc_get_order_types(),
            //'post_status' => array_keys(wc_get_order_statuses()),
            'post_status' => 'wc-completed',
            'posts_per_page' => $posts,
            'offset' => $offset,
            'orderby' => 'id',
            'order' => 'ASC',
        );

        $orders = get_posts($params);
        if (empty($orders)) {
            sleep(2);
            $orders = get_posts($params);
        }

        return empty($orders) ? false : $orders;
    }

    /**
     * @param WC_Abstract_Order $order
     * @return object
     * @throws Exception
     */
    public function getCustomerOrderTotals($order)
    {
        if (!function_exists('wc_get_orders')) {
            return $this->getSingleCustomerOrderTotals($order->get_user_id());
        }

        $orders = wc_get_orders(array(
            'customer' => trim($order->get_billing_email()),
        ));

        $stats = (object) array('count' => 0, 'total' => 0);

        foreach ($orders as $order) {
            $order = wc_get_order($order);

            if ($order->get_status() !== 'cancelled' && (method_exists($order, 'is_paid') && $order->is_paid())) {
                $stats->total += $order->get_total();
                $stats->count ++;
            }
        }

        return $stats;
    }

    /**
     * @param $user_id
     * @return object
     * @throws Exception
     */
    protected function getSingleCustomerOrderTotals($user_id)
    {
        $customer = new WC_Customer($user_id);

        $customer->get_order_count();
        $customer->get_total_spent();

        return (object) array(
            'count' => $customer->get_order_count(),
            'total' => $customer->get_total_spent()
        );
    }

    /**
     * "Pending payment" in the UI fires the order confirmation email MailChimp
     * "Completed” in the UI fires the MailChimp Order Invoice
     * "Cancelled" does what we think it does
     *
     * @return array
     */
    public function getOrderStatuses()
    {
        return array(
            // Order received (unpaid)
            'pending'       => (object) array(
                'financial' => 'pending',
                'fulfillment' => null
            ),
            // Payment received and stock has been reduced – the order is awaiting fulfillment.
            // All product orders require processing, except those for digital downloads
            'processing'    => (object) array(
                'financial' => 'pending',
                'fulfillment' => null
            ),
            // Awaiting payment – stock is reduced, but you need to confirm payment
            'on-hold'       => (object) array(
                'financial' => 'on-hold',
                'fulfillment' => null
            ),
            // Order fulfilled and complete – requires no further action
            'completed'     => (object) array(
                'financial' => 'paid',
                'fulfillment' => 'fulfilled'
            ),
            // Cancelled by an admin or the customer – no further action required
            'cancelled'     => (object) array(
                'financial' => 'cancelled',
                'fulfillment' => null
            ),
            // Refunded by an admin – no further action required
            'refunded'      => (object) array(
                'financial' => 'refunded',
                'fulfillment' => null
            ),
            // Payment failed or was declined (unpaid). Note that this status may not show immediately and
            // instead show as Pending until verified (i.e., PayPal)
            'failed'        => (object) array(
                'financial' => 'failed',
                'fulfillment' => null
            ),
        );
    }
}

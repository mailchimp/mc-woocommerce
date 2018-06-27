<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/13/16
 * Time: 8:29 AM
 */
class MailChimp_WooCommerce_Transform_Orders
{
    public $campaign_id = null;

    /**
     * @param int $page
     * @param int $limit
     * @return \stdClass
     */
    public function compile($page = 1, $limit = 5)
    {
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

                $order = $this->transform($post);
                if (!$order->isFlaggedAsAmazonOrder()) {
                    $response->valid++;
                    $response->items[] = $order;
                }
            }
        }

        $response->stuffed = ($response->count > 0 && (int) $response->count === (int) $limit) ? true : false;

        return $response;
    }

    /**
     * @param WP_Post $post
     * @return MailChimp_WooCommerce_Order
     */
    public function transform(WP_Post $post)
    {
        $woo = new WC_Order($post);

        $order = new MailChimp_WooCommerce_Order();

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
            $order->setCampaignId($this->campaign_id);
        }

        $order->setProcessedAt($woo->get_date_created()->setTimezone(new \DateTimeZone('UTC')));

        $order->setCurrencyCode($woo->get_currency());

        // grab the current statuses - this will end up being custom at some point.
        $statuses = $this->getOrderStatuses();

        // grab the order status
        $status = $woo->get_status();

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
        $order->setOrderTotal($woo->get_total());

        // set the order URL
        $order->setOrderURL($woo->get_view_order_url());

        // if we have any tax
        $order->setTaxTotal($woo->get_total_tax());

        // if we have shipping
        if (method_exists($woo, 'get_shipping_total')) {
            $order->setShippingTotal($woo->get_shipping_total());
        }

        // set the order discount
        $order->setDiscountTotal($woo->get_total_discount());

        // set the customer
        $order->setCustomer($this->buildCustomerFromOrder($woo));

        // apply the addresses to the order
        $order->setShippingAddress($this->transformShippingAddress($woo));
        $order->setBillingAddress($this->transformBillingAddress($woo));

        // loop through all the order items
        foreach ($woo->get_items() as $key => $order_detail) {
            /** @var WC_Order_Item_Product $order_detail */

            // add it into the order item container.
            $item = $this->transformLineItem($key, $order_detail);

            // if we don't have a product post with this id, we need to add a deleted product to the MC side
            if (!($product = $order_detail->get_product()) || 'trash' === $product->get_status()) {

                $pid = $order_detail->get_product_id();

                try {
                    $deleted_product = MailChimp_WooCommerce_Transform_Products::deleted($pid);
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

        //if (($refund = $woo->get_total_refunded()) && $refund > 0){
            // this is where we would be altering the submission to tell us about the refund.
        //}

        return $order;
    }

    /**
     * @param WC_Order $order
     * @return MailChimp_WooCommerce_Customer
     */
    public function buildCustomerFromOrder(WC_Order $order)
    {
        $customer = new MailChimp_WooCommerce_Customer();

        $customer->setId(mailchimp_hash_trim_lower($order->get_billing_email()));
        $customer->setCompany($order->get_billing_company());
        $customer->setEmailAddress(trim($order->get_billing_email()));
        $customer->setFirstName($order->get_billing_first_name());
        $customer->setLastName($order->get_billing_last_name());
        $customer->setAddress($this->transformBillingAddress($order));

        if (!($stats = $this->getCustomerOrderTotals($order->get_user_id()))) {
            $stats = (object) array('count' => 0, 'total' => 0);
        }

        $customer->setOrdersCount($stats->count);
        $customer->setTotalSpent($stats->total);

        // we are saving the post meta for subscribers on each order... so if they have subscribed on checkout
        $subscriber_meta = get_post_meta($order->get_id(), 'mailchimp_woocommerce_is_subscribed', true);
        $subscribed_on_order = $subscriber_meta === '' ? false : (bool) $subscriber_meta;
        $customer->setOptInStatus($subscribed_on_order);

        // if they didn't subscribe on the order, we need to check to make sure they're not already a subscriber
        // if they are, we just need to make sure that we don't unsubscribe them just because they unchecked this box.
        if (!$subscribed_on_order) {
            try {
                $subscriber = mailchimp_get_api()->member(mailchimp_get_list_id(), $customer->getEmailAddress());
                $status = !in_array($subscriber['status'], array('unsubscribed', 'transactional'));
                $customer->setOptInStatus($status);
            } catch (\Exception $e) {}
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
     * @param WC_Order $order
     * @return MailChimp_WooCommerce_Address
     */
    public function transformBillingAddress(WC_Order $order)
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
     * @param WC_Order $order
     * @return MailChimp_WooCommerce_Address
     */
    public function transformShippingAddress(WC_Order $order)
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
        $params = array(
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
            'posts_per_page' => $posts,
            'paged' => $page,
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
     * returns an object with a 'total' and a 'count'.
     *
     * @param $user_id
     * @return object
     */
    public function getCustomerOrderTotals($user_id)
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
                'financial' => 'fulfilled',
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

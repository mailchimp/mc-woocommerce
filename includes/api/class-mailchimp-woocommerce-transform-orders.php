<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/13/16
 * Time: 8:29 AM
 */
class MailChimp_WooCommerce_Transform_Orders
{
    public $campaign_id = null;
    protected $use_user_address = false;

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
     * @param WC_Order $woo
     * @return array
     */
    protected function dates(WC_Order $woo)
    {
        if (method_exists($woo, 'get_date_modified')) {
            $created_at = $woo->get_date_modified();
            $updated_at = $woo->get_date_modified();
        } elseif (property_exists($woo, 'order_date') && property_exists($woo, 'modified_date')) {
            $created_at = $woo->order_date ? new \DateTime($woo->order_date) : null;
            $updated_at = $woo->modified_date ? new \DateTime($woo->modified_date) : null;
        } else {
            $created_at = $updated_at = new \DateTime();
        }

        return array($created_at, $updated_at);
    }

    /**
     * @param WP_Post $post
     * @return MailChimp_WooCommerce_Order
     */
    public function transform(WP_Post $post)
    {
        $woo = new WC_Order($post);

        $order = new MailChimp_WooCommerce_Order();

        $order->setId($woo->get_order_number());

        // just skip these altogether because we can't submit any amazon orders anyway.
        if (mailchimp_string_contains($woo->billing_email, '@marketplace.amazon.com')) {
            return $order->flagAsAmazonOrder(true);
        }

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
            $order->setCancelledAt($woo->get_date_modified()->setTimezone(new \DateTimeZone('UTC')));
        }

        // set the total
        $order->setOrderTotal($woo->get_total());

        // set the order URL
        $order->setOrderURL($woo->get_view_order_url());

        // if we have any tax
        $order->setTaxTotal($woo->get_total_tax());

        // if we have shipping.
        $order->setShippingTotal($woo->get_total_shipping());

        // set the order discount
        $order->setDiscountTotal($woo->get_total_discount());

        // set the customer
        $order->setCustomer($this->buildCustomerFromOrder($woo));

        // apply the addresses to the order
        $addresses = $this->getOrderAddresses($woo);
        $order->setShippingAddress($addresses->shipping);
        $order->setBillingAddress($addresses->billing);

        // loop through all the order items
        foreach ($woo->get_items() as $key => $order_detail) {

            // add it into the order item container.
            $item = $this->buildLineItem($key, $order_detail);

            // if we don't have a product post with this id, we need to add a deleted product to the MC side
            if (!($product_post = get_post($item->getProductId()))) {

                // check if it exists, otherwise create a new one.
                if (($deleted_product = MailChimp_WooCommerce_Transform_Products::deleted($item->getProductId()))) {

                    $deleted_product_id = "deleted_{$item->getProductId()}";

                    // swap out the old item id and product variant id with the deleted version.
                    $item->setProductId($deleted_product_id);
                    $item->setProductVariantId($deleted_product_id);

                    // add the item and continue on the loop.
                    $order->addItem($item);
                    continue;
                }

                mailchimp_log('order.items.error', "Order #{$woo->get_order_number()} :: Product {$item->getProductId()} does not exist!");
                continue;
            }

            $order->addItem($item);
        }

        // apply the coupon discounts
        if (function_exists('wc_get_coupon_id_by_code') && ($used_coupons = $woo->get_used_coupons()) && is_array($used_coupons)) {
            foreach ($used_coupons as $coupon_code) {
                if (($coupon_id = wc_get_coupon_id_by_code($coupon_code))) {
                    $coupon = new WC_Coupon($coupon_id);
                    $is_percentage = $coupon->get_discount_type() === 'percent';
                    $order->addDiscount($coupon_code, $coupon->get_amount('edit'), $is_percentage);
                }
            }
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

        $customer->setId(md5(trim(strtolower($order->billing_email))));
        $customer->setCompany($order->billing_company);
        $customer->setEmailAddress(trim($order->billing_email));
        $customer->setFirstName($order->billing_first_name);
        $customer->setLastName($order->billing_last_name);
        $customer->setOrdersCount(1);
        $customer->setTotalSpent($order->get_total());

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

        // use the info from the order to compile an address.
        $address = new MailChimp_WooCommerce_Address();
        $address->setAddress1($order->billing_address_1);
        $address->setAddress2($order->billing_address_2);
        $address->setCity($order->billing_city);
        $address->setProvince($order->billing_state);
        $address->setPostalCode($order->billing_postcode);
        $address->setCountry($order->billing_country);
        $address->setPhone($order->billing_phone);

        // if we have billing names set it here
        if (!empty($order->billing_first_name) && !empty($order->billing_last_name)) {
            $address->setName($order->billing_first_name.' '.$order->billing_last_name);
        }

        $customer->setAddress($address);

        if (($user = get_userdata($order->customer_user))) {
            /**
             * IF we wanted to use the user data instead we would do it here.
             * but we discussed using the billing address instead.
             */
            if ($this->use_user_address) {
                $customer->setId($user->ID);
                $customer->setEmailAddress($user->user_email);
                $customer->setFirstName($user->first_name);
                $customer->setLastName($user->last_name);

                if (($address = $this->getUserAddress($user->ID))) {
                    if (count($address->toArray()) > 3) {
                        $customer->setAddress($address);
                    }
                }
            }

            if (!($stats = $this->getCustomerOrderTotals($order->customer_user))) {
                $stats = (object) array('count' => 0, 'total' => 0);
            }

            $customer->setOrdersCount($stats->count);
            $customer->setTotalSpent($stats->total);
        }

        return $customer;
    }

    /**
     * @param $key
     * @param $order_detail
     * @return MailChimp_WooCommerce_LineItem
     */
    protected function buildLineItem($key, $order_detail)
    {
        // fire up a new MC line item
        $item = new MailChimp_WooCommerce_LineItem();
        $item->setId($key);

        if (isset($order_detail['item_meta']) && is_array($order_detail['item_meta'])) {

            foreach ($order_detail['item_meta'] as $meta_key => $meta_data_array) {

                if (!isset($meta_data_array[0])) {
                    continue;
                }

                switch ($meta_key) {

                    case '_line_subtotal':
                        $item->setPrice($meta_data_array[0]);
                        break;

                    case '_product_id':
                        $item->setProductId($meta_data_array[0]);
                        break;

                    case '_variation_id':
                        $item->setProductVariantId($meta_data_array[0]);
                        break;

                    case '_qty':
                        $item->setQuantity($meta_data_array[0]);
                        break;

                }
            }

            if ($item->getProductVariantId() <= 0) {
                $item->setProductVariantId($item->getProductId());
            }

        } elseif (isset($order_detail['item_meta_array']) && is_array($order_detail['item_meta_array'])) {

            /// Some users have the newer version of the item meta.

            foreach ($order_detail['item_meta_array'] as $meta_id => $object) {

                if (!isset($object->key)) {
                    continue;
                }

                switch ($object->key) {

                    case '_line_subtotal':
                        $item->setPrice($object->value);
                        break;

                    case '_product_id':
                        $item->setProductId($object->value);
                        break;

                    case '_variation_id':
                        $item->setProductVariantId($object->value);
                        break;

                    case '_qty':
                        $item->setQuantity($object->value);
                        break;
                }
            }

            if ($item->getProductVariantId() <= 0) {
                $item->setProductVariantId($item->getProductId());
            }
        }

        if ($item->getQuantity() > 1) {
            $current_price = $item->getPrice();
            $price = ($current_price/$item->getQuantity());
            $item->setPrice($price);
        }

        return $item;
    }

    /**
     * @param int $page
     * @param int $posts
     * @return array|bool
     */
    public function getOrderPosts($page = 1, $posts = 5)
    {
        $params = array(
            'post_type' => 'shop_order',
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
        $stats = (object) array('count' => 0, 'total' => 0);

        if (!empty($user_id)) {
            $orders = get_posts(apply_filters('woocommerce_my_account_my_orders_query', array(
                'numberposts' => -1,
                'meta_key'    => '_customer_user',
                'meta_value'  => $user_id,
                'post_type'   => 'shop_order',
                'post_status' => 'publish'
            )));

            foreach ($orders as $order) {
                $woo = new WC_Order($order);
                $stats->total += $woo->get_total();
                $stats->count++;
            }
            return $stats;
        }

        return false;
    }

    /**
     * @param WC_Order $order
     * @return object
     */
    public function getOrderAddresses(WC_Order $order)
    {
        // use the info from the order to compile an address.
        $billing = new MailChimp_WooCommerce_Address();
        $billing->setAddress1($order->billing_address_1);
        $billing->setAddress2($order->billing_address_2);
        $billing->setCity($order->billing_city);
        $billing->setProvince($order->billing_state);
        $billing->setPostalCode($order->billing_postcode);
        $billing->setCountry($order->billing_country);
        $billing->setPhone($order->billing_phone);

        // if we have billing names go ahead and apply them
        if (!empty($order->billing_first_name) && !empty($order->billing_last_name)) {
            $billing->setName($order->billing_first_name.' '.$order->billing_last_name);
        }

        $shipping = new MailChimp_WooCommerce_Address();
        $shipping->setAddress1($order->shipping_address_1);
        $shipping->setAddress2($order->shipping_address_2);
        $shipping->setCity($order->shipping_city);
        $shipping->setProvince($order->shipping_state);
        $shipping->setPostalCode($order->shipping_postcode);
        $shipping->setCountry($order->shipping_country);
        if (isset($order->shipping_phone)) {
            $shipping->setPhone($order->shipping_phone);
        }

        // if we have shipping names go ahead and apply them
        if (!empty($order->shipping_first_name) && !empty($order->shipping_last_name)) {
            $shipping->setName($order->shipping_first_name.' '.$order->shipping_last_name);
        }

        return (object) array('billing' => $billing, 'shipping' => $shipping);
    }

    /**
     * @param $user_id
     * @param string $type
     * @return MailChimp_WooCommerce_Address
     */
    public function getUserAddress($user_id, $type = 'billing')
    {
        $address = new MailChimp_WooCommerce_Address();

        // pull all the meta for this user.
        $meta = get_user_meta($user_id);

        // loop through all the possible address properties, and if we have on on the user, set the property
        // because it's more up to date.
        $address_props = array(
            $type.'_address_1' => 'setAddress1',
            $type.'_address_2' => 'setAddress2',
            $type.'_city' => 'setCity',
            $type.'_state' => 'setProvince',
            $type.'_postcode' => 'setPostalCode',
            $type.'_country' => 'setCountry',
            $type.'_phone' => 'setPhone',
        );

        // loop through all the address properties and set the values if we have one.
        foreach ($address_props as $address_key => $address_call) {
            if (isset($meta[$address_key]) && !empty($meta[$address_key]) && isset($meta[$address_key][0])) {
                $address->$address_call($meta[$address_key][0]);
            }
        }

        return $address;
    }

    /**
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

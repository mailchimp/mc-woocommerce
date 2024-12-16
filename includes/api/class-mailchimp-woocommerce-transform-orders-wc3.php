<?php

/**
 * Class MailChimp_WooCommerce_Transform_Orders
 */
class MailChimp_WooCommerce_Transform_Orders {

	protected $is_syncing = false;

    /**
     * @param $is_syncing
     * @return $this
     */
    public function setSyncing($is_syncing = true)
    {
        $this->is_syncing = (bool) $is_syncing;
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function isSyncing()
    {
        return $this->is_syncing;
    }

	/**
	 * @param int $page
	 * @param int $limit
	 *
	 * @return object
	 */
	public function compile( $page = 1, $limit = 5 ) {
		$this->is_syncing = true;

		$response = (object) array(
			'endpoint' => 'orders',
			'page'     => $page ? $page : 1,
			'limit'    => (int) $limit,
			'count'    => 0,
			'valid'    => 0,
			'drafts'   => 0,
			'stuffed'  => false,
			'items'    => array(),
		);

		if ( ( ( $orders = $this->getOrderPosts( $page, $limit ) ) && ! empty( $orders ) ) ) {
			foreach ( $orders as $post_id ) {
				$response->items[] = $post_id;
				$response->count++;
			}
		}

		$response->stuffed = $response->count > 0 && (int) $response->count === (int) $limit;
		$this->is_syncing  = false;

		return $response;
	}

	/**
	 * @param $woo
	 *
	 * @return MailChimp_WooCommerce_Order|mixed|void
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function transform( $woo ) {
		$order = new MailChimp_WooCommerce_Order();

		// if the woo get order returns an empty value, we need to skip the whole thing.
		if ( empty( $woo ) ) {
			return $order;
		}

		// this is a fallback safety check to make sure we're not submitting these orders.
		if ( $woo->get_status() === 'checkout-draft' ) {
			$order->setOriginalWooStatus( 'checkout-draft' );
			$order->flagAsIgnoreIfNotInMailchimp( true );
			return $order;
		}

		// if the woo object does not have a "get_billing_email" method, then we need to skip this until
		// we know how to resolve these types of things.
		// mailchimp_log('get_billing_mail', method_exists($woo, 'get_billing_email'), array($order->toArray(), $woo));
		if ( ! method_exists( $woo, 'get_billing_email' ) ) {
			$message = "Post ID {$woo->get_id()} was an order refund. Skipping this.";
			if ( $this->is_syncing ) {
				throw new MailChimp_WooCommerce_Error( $message );
			}
			mailchimp_error(
				'initial_sync',
				$message,
				array(
					'post'        => $woo,
					'order_class' => get_class( $woo ),
				)
			);
			return $order;
		}

		$customer = $this->buildCustomerFromOrder( $woo );

		$email = $woo->get_billing_email();

		// just skip these altogether because we can't submit any amazon orders anyway.
		if ( mailchimp_email_is_amazon( $email ) ) {
			return $order->flagAsAmazonOrder( true );
		} elseif ( mailchimp_email_is_privacy_protected( $email ) ) {
			return $order->flagAsPrivacyProtected( true );
		}

		$order->setId( $woo->get_order_number() );

		$order->setProcessedAt( $woo->get_date_created()->setTimezone( new DateTimeZone( 'UTC' ) ) );

		$order->setCurrencyCode( $woo->get_currency() );

		// grab the current statuses - this will end up being custom at some point.
		$statuses = $this->getOrderStatuses();

		// grab the order status and set it into the object for future comparison.
		$order->setOriginalWooStatus( ( $status = $woo->get_status() ) );

		// if the order is "on-hold" status, and is not currently in Mailchimp, we need to ignore it
		// because the payment gateways are putting this on hold while they navigate to the payment processor
		// and they technically haven't paid yet.
		if ( in_array( $status, array( 'on-hold', 'failed' ) ) ) {
			$order->flagAsIgnoreIfNotInMailchimp( true );
		}

		// map the fulfillment and financial statuses based on the map above.
		$fulfillment_status = array_key_exists( $status, $statuses ) ? $statuses[ $status ]->fulfillment : null;
		$financial_status   = array_key_exists( $status, $statuses ) ? $statuses[ $status ]->financial : $status;

		// set the fulfillment_status
		$order->setFulfillmentStatus( $fulfillment_status );

		// set the financial status
		$order->setFinancialStatus( $financial_status );

		// if the status is processing, we need to send this one first, then send a 'paid' status right after.
		if ( $status === 'processing' ) {
			$order->confirmAndPay( true );
		}

		// only set this if the order is cancelled.
		if ( $status === 'cancelled' ) {
			if ( method_exists( $woo, 'get_date_modified' ) ) {
				$order->setCancelledAt( $woo->get_date_modified()->setTimezone( new DateTimeZone( 'UTC' ) ) );
			}
		}

		// set the total
		$order->setOrderTotal( $order_total = $woo->get_total() );

		// set the order URL if it's valid.
		if ( ( $view_order_url = $woo->get_view_order_url() ) && wc_is_valid_url( $view_order_url ) ) {
			$order->setOrderURL( $woo->get_view_order_url() );
		}

		// set the total if refund
		if ( ( $refund = $woo->get_total_refunded() ) && $refund > 0 ) {
			// If there's a refund, apply to order total.
			$order_spent = $order_total - $refund;
			$order->setOrderTotal( $order_spent );
		}

		// if we have any tax
		$order->setTaxTotal( $woo->get_total_tax() );

		// if we have shipping
		if ( method_exists( $woo, 'get_shipping_total' ) ) {
			$order->setShippingTotal( $woo->get_shipping_total() );
		}

		// set the order discount
		$order->setDiscountTotal( $woo->get_total_discount() );

		// set the customer
		$order->setCustomer( $customer );

		// apply the addresses to the order
		$order->setShippingAddress( $this->transformShippingAddress( $woo ) );
		$order->setBillingAddress( $this->transformBillingAddress( $woo ) );

		// loop through all the order items
		foreach ( $woo->get_items() as $key => $order_detail ) {
			/** @var WC_Order_Item_Product $order_detail */

            $key = apply_filters( 'mailchimp_line_item_key', $key, $woo );
			// add it into the order item container.
			$item = $this->transformLineItem( $key, $order_detail );

			$product = $order_detail->get_product();

			// if we can't find the product, we need to populate this
			if ( empty( $product ) ) {
				if ( ( $empty_order_item = MailChimp_WooCommerce_Transform_Products::missing_order_item( $order_detail ) ) ) {
					$item->setFallbackTitle( $empty_order_item->getTitle() );
					$item->setProductId( $empty_order_item->getId() );
					$item->setProductVariantId( $empty_order_item->getId() );
					$order->addItem( $item );
					continue;
				}
			}

			// if we don't have a product post with this id, we need to add a deleted product to the MC side
			if ( ! $product || ( $trashed = 'trash' === $product->get_status() ) ) {

				$pid   = $order_detail->get_product_id();
				$title = $order_detail->get_name();

				try {
					$deleted_product = MailChimp_WooCommerce_Transform_Products::deleted( $pid, $title );
				} catch ( Exception $e ) {
					mailchimp_log( 'order.items.error', "Order #{$woo->get_id()} :: Product {$pid} does not exist!" );
					continue;
				}

				// check if it exists, otherwise create a new one.
				if ( $deleted_product ) {
					// swap out the old item id and product variant id with the deleted version.
					$item->setProductId( "deleted_{$pid}" );
					$item->setProductVariantId( "deleted_{$pid}" );

					// add the item and continue on the loop.
					$order->addItem( $item );
					continue;
				}

				mailchimp_log( 'order.items.error', "Order #{$woo->get_id()} :: Product {$pid} does not exist!" );
				continue;
			}

			$order->addItem( $item );
		}

		// let the store owner alter this if they need to use on-hold orders
		return apply_filters( 'mailchimp_filter_ecommerce_order', $order, $woo );
	}

	/**
	 * @param WC_Order|WC_Order_Refund $order
	 *
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function buildCustomerFromOrder( $order ) {
		$customer = new MailChimp_WooCommerce_Customer();

        $wordpress_user = $order->get_user();

        if (empty($wordpress_user)) {
            mailchimp_debug('order_logic', "order did not have a wordpress user id, checking by email {$order->get_billing_email()}");
            $wordpress_user = get_user_by('email', $order->get_billing_email());
            if ($wordpress_user) {
                mailchimp_debug('order_logic', "found a wordpress user by email {$order->get_billing_email()}");
            }
        }

		// attach the WordPress user to the Mailchimp customer object.
		$customer->setWordpressUser( $wordpress_user );

		$customer->setId( mailchimp_hash_trim_lower( $order->get_billing_email() ) );
		$customer->setCompany( $order->get_billing_company() );
		$customer->setEmailAddress( trim( $order->get_billing_email() ) );
		$customer->setFirstName( $order->get_billing_first_name() );
		$customer->setLastName( $order->get_billing_last_name() );
		$customer->setAddress( $this->transformBillingAddress( $order ) );

		// we now hold this data inside the customer object for usage in the order handler class
		// we only update the subscriber status on a member IF they were subscribed.
		$subscribed_on_order = $customer->wasSubscribedOnOrder( $order->get_id() );
		// this basically says "if they subscribed on the order, allow it, otherwise use the wordpress meta"
        $customer->setOptInStatus( $subscribed_on_order );
        // if we have a wordpress meta already saying they're subscribed, we can use this as a default value.
        $customer->applyWordpressUserSubscribeStatus();

        // if we are only going to submit existing people on the list during a sync this call is required.
        if ($this->is_syncing && !$customer->getOptInStatus() && mailchimp_sync_existing_contacts_only()) {
            $customer->syncSubscriberStatusFromMailchimp();
            mailchimp_debug("sync.logic", "customer {$customer->getEmailAddress()} was not subscribed in woo, but pulled from Mailchimp with a status of {$customer->getMailchimpStatus()}");
        }

		return $customer;
	}

	/**
	 * @param $key
	 * @param $order_detail
	 *
	 * @return MailChimp_WooCommerce_LineItem
	 */
	protected function transformLineItem( $key, $order_detail ) {
		// fire up a new MC line item
		$item = new MailChimp_WooCommerce_LineItem();
		$item->setId( $key );

		// set the fallback title for the order detail name just in case we need to create a product
		// from this order item.
		$item->setFallbackTitle( $order_detail->get_name() );

		$item->setPrice( $order_detail->get_total() );
		$item->setProductId( $order_detail->get_product_id() );
		$variation_id = $order_detail->get_variation_id();
		if ( empty( $variation_id ) ) {
			$variation_id = $order_detail->get_product_id();
		}
		$item->setProductVariantId( $variation_id );
		$item->setQuantity( $order_detail->get_quantity() );

		if ( $item->getQuantity() > 1 ) {
			$current_price = $item->getPrice();
			$price         = ( $current_price / $item->getQuantity() );
			$item->setPrice( $price );
		}

		return $item;
	}

	/**
	 * @param WC_Abstract_Order $order
	 * @return MailChimp_WooCommerce_Address
	 */
	public function transformBillingAddress( WC_Abstract_Order $order ) {
		// use the info from the order to compile an address.
		$address = new MailChimp_WooCommerce_Address();
		$address->setAddress1( $order->get_billing_address_1() );
		$address->setAddress2( $order->get_billing_address_2() );
		$address->setCity( $order->get_billing_city() );
		$address->setProvince( $order->get_billing_state() );
		$address->setPostalCode( $order->get_billing_postcode() );
		$address->setCountry( $order->get_billing_country() );
		$address->setPhone( $order->get_billing_phone() );

		$bfn = $order->get_billing_first_name();
		$bln = $order->get_billing_last_name();

		// if we have billing names set it here
		if ( ! empty( $bfn ) && ! empty( $bln ) ) {
			$address->setName( "{$bfn} {$bln}" );
		}

		return $address;
	}

	/**
	 * @param WC_Abstract_Order $order
	 * @return MailChimp_WooCommerce_Address
	 */
	public function transformShippingAddress( WC_Abstract_Order $order ) {
		$address = new MailChimp_WooCommerce_Address();

		$address->setAddress1( $order->get_shipping_address_1() );
		$address->setAddress2( $order->get_shipping_address_2() );
		$address->setCity( $order->get_shipping_city() );
		$address->setProvince( $order->get_shipping_state() );
		$address->setPostalCode( $order->get_shipping_postcode() );
		$address->setCountry( $order->get_shipping_country() );

		// shipping does not have a phone number, so maybe use this?
		$address->setPhone( $order->get_billing_phone() );

		$sfn = $order->get_shipping_first_name();
		$sln = $order->get_shipping_last_name();

		// if we have billing names set it here
		if ( ! empty( $sfn ) && ! empty( $sln ) ) {
			$address->setName( "{$sfn} {$sln}" );
		}

		return $address;
	}

	/**
	 * @param int $page
	 * @param int $posts
	 * @return array|bool
	 */
	public function getOrderPosts( $page = 1, $posts = 5 ) {
		$offset = 0;
		if ( $page > 1 ) {
			$offset = ( $page - 1 ) * $posts;
		}

		$params = array(
			'post_type'      => 'shop_order',
            'post_status'    => 'wc-completed',
			'posts_per_page' => $posts,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		$orders = MailChimp_WooCommerce_HPOS::get_orders( $params );

		return empty( $orders ) ? false : $orders;
	}

	/**
	 * @param $order
	 *
	 * @return object
	 * @throws Exception
	 */
	public function getCustomerOrderTotals( $order ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $this->getSingleCustomerOrderTotals( $order->get_user_id() );
		}

		$orders = wc_get_orders(
			array(
				'customer' => trim( $order->get_billing_email() ),
			)
		);

		$stats = (object) array(
			'count' => 0,
			'total' => 0,
		);

		foreach ( $orders as $order ) {
			$order = wc_get_order( $order );

			if ( $order->get_status() !== 'cancelled' && ( method_exists( $order, 'is_paid' ) && $order->is_paid() ) ) {
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
	protected function getSingleCustomerOrderTotals( $user_id ) {
		$customer = new WC_Customer( $user_id );

		$customer->get_order_count();
		$customer->get_total_spent();

		return (object) array(
			'count' => $customer->get_order_count(),
			'total' => $customer->get_total_spent(),
		);
	}

	/**
	 * "Pending payment" in the UI fires the order confirmation email MailChimp
	 * "Completed” in the UI fires the MailChimp Order Invoice
	 * "Cancelled" does what we think it does
	 *
	 * @return array
	 */
	public function getOrderStatuses() {
		return array(
			// Order received (unpaid)
			'pending'    => (object) array(
				'financial'   => 'pending',
				'fulfillment' => null,
			),
			// Payment received and stock has been reduced – the order is awaiting fulfillment.
			// All product orders require processing, except those for digital downloads
			'processing' => (object) array(
				'financial'   => 'pending',
				'fulfillment' => null,
			),
			// Awaiting payment – stock is reduced, but you need to confirm payment
			'on-hold'    => (object) array(
				'financial'   => 'on-hold',
				'fulfillment' => null,
			),
			// Order fulfilled and complete – requires no further action
			'completed'  => (object) array(
				'financial'   => 'paid',
				'fulfillment' => 'fulfilled',
			),
			// Cancelled by an admin or the customer – no further action required
			'cancelled'  => (object) array(
				'financial'   => 'cancelled',
				'fulfillment' => null,
			),
			// Refunded by an admin – no further action required
			'refunded'   => (object) array(
				'financial'   => 'refunded',
				'fulfillment' => null,
			),
			// Payment failed or was declined (unpaid). Note that this status may not show immediately and
			// instead show as Pending until verified (i.e., PayPal)
			'failed'     => (object) array(
				'financial'   => 'failed',
				'fulfillment' => null,
			),
		);
	}
}

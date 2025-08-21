<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/15/16
 * Time: 1:26 PM
 */
class MailChimp_WooCommerce_Cart {

	protected $store_id;
	protected $id;
	protected $customer;
	protected $checkout_url;
	protected $currency_code;
	protected $order_total;
	protected $tax_total;
	protected $lines = array();

	/**
	 * @param $unique_id
	 * @return $this
	 */
	public function setId( $unique_id ) {
		$this->id = $unique_id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param $store_id
	 * @return $this
	 */
	public function setStoreID( $store_id ) {
		$this->store_id = $store_id;

		return $this;
	}

	/**
	 * @return mixed|string
	 */
	public function getStoreID() {
		if ( empty( $this->store_id ) ) {
			$this->store_id = mailchimp_get_store_id();
		}

		return $this->store_id;
	}

	/**
	 * @param MailChimp_WooCommerce_Customer $customer
	 * @return $this
	 */
	public function setCustomer( MailChimp_WooCommerce_Customer $customer ) {
		$this->customer = $customer;

		return $this;
	}

	/**
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function getCustomer() {
		if ( empty( $this->customer ) ) {
			$this->customer = new MailChimp_WooCommerce_Customer();
		}

		return $this->customer;
	}

	/**
	 * @param $url
	 * @return $this
	 */
	public function setCheckoutUrl( $url ) {
		$this->checkout_url = $url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCheckoutURL() {
		if ( empty( $this->checkout_url ) ) {
			$this->checkout_url = wc_get_checkout_url();
		}

		return $this->checkout_url;
	}

	/**
	 * @return $this
	 */
	public function setCurrencyCode() {
        $default_currency = get_woocommerce_currency();
        $this->currency_code = apply_filters('mailchimp_woocommerce_cart_currency_code', $default_currency, $this->getCheckoutURL());

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode() {
		if ( empty( $this->currency_code ) ) {
            $default_currency = get_woocommerce_currency();
            $this->currency_code = apply_filters('mailchimp_woocommerce_cart_currency_code', $default_currency, $this->getCheckoutURL());
		}

		return $this->currency_code;
	}

	/**
	 * @param $total
	 * @return $this
	 */
	public function setOrderTotal( $total ) {
		$this->order_total = $total;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getOrderTotal() {
		return $this->order_total;
	}

	/**
	 * @param $total
	 * @return $this
	 */
	public function setTaxTotal( $total ) {
		$this->tax_total = $total;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getTaxTotal() {
		return $this->tax_total;
	}

	/**
	 * @param MailChimp_WooCommerce_LineItem $item
	 * @return $this
	 */
	public function addItem( MailChimp_WooCommerce_LineItem $item ) {
		$this->lines[] = $item;
		return $this;
	}

	/**
	 * @return array
	 */
	public function items() {
		return $this->lines;
	}

	/**
	 * @return mixed
	 */
	public function toArray() {
		return mailchimp_array_remove_empty(
			array(
				'id'            => (string) $this->getId(),
				'customer'      => $this->getCustomer()->toArray(),
				'checkout_url'  => (string) $this->getCheckoutURL(),
				'currency_code' => (string) $this->getCurrencyCode(),
				'order_total'   => floatval( $this->getOrderTotal() ),
				'tax_total'     => $this->getTaxTotal() > 0 ? floatval( $this->getTaxTotal() ) : null,
				'lines'         => array_map(
					function( $item ) {
						return $item->toArray();
					},
					$this->items()
				),
			)
		);
	}

	/**
	 * @return array
	 */
	public function toArrayForUpdate() {
		return mailchimp_array_remove_empty(
			array(
				'checkout_url'  => (string) $this->getCheckoutURL(),
				'currency_code' => (string) $this->getCurrencyCode(),
				'order_total'   => $this->getOrderTotal(),
				'tax_total'     => ( $this->getTaxTotal() > 0 ? $this->getTaxTotal() : null ),
				'lines'         => array_map(
					function( $item ) {
						return $item->toArray();
					},
					$this->items()
				),
			)
		);
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_Cart
	 */
	public function fromArray( array $data ) {
		$singles = array(
			'store_id',
			'id',
			'checkout_url',
			'currency_code',
			'order_total',
			'tax_total',
		);

		foreach ( $singles as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}

		if ( array_key_exists( 'customer', $data ) && is_array( $data['customer'] ) ) {
			$customer       = new MailChimp_WooCommerce_Customer();
			$this->customer = $customer->fromArray( $data['customer'] );
		}

		if ( array_key_exists( 'lines', $data ) && is_array( $data['lines'] ) ) {
			foreach ( $data['lines'] as $line_item ) {
				$item          = new MailChimp_WooCommerce_LineItem();
				$this->lines[] = $item->fromArray( $line_item );
			}
		}

		return $this;
	}
}

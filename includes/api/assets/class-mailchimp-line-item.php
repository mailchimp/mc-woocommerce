<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:16 PM
 */
class MailChimp_WooCommerce_LineItem {

	protected $id;
	protected $product_id;
	protected $product_variant_id;
	protected $quantity;
	protected $price;
	protected $fallback_title = null;
	protected $fallback_sku   = null;

	/**
	 * @return array
	 */
	public function getValidation() {
		return array(
			'id'                 => 'required|string',
			'product_id'         => 'required|string',
			'product_variant_id' => 'required|string',
			'quantity'           => 'required|integer',
			'price'              => 'required|numeric',
		);
	}

	/**
	 * @param $id
	 * @param $product_id
	 * @param $variant_id
	 * @param $quantity
	 * @param $price
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public static function make( $id, $product_id, $variant_id, $quantity, $price ) {
		$item                     = new MailChimp_WooCommerce_LineItem();
		$item->id                 = $id;
		$item->product_id         = $product_id;
		$item->product_variant_id = $variant_id;
		$item->quantity           = $quantity;
		$item->price              = $price;

		return $item;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getProductId() {
		return $this->product_id;
	}

	/**
	 * @param mixed $product_id
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function setProductId( $product_id ) {
		$this->product_id = $product_id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getProductVariantId() {
		return $this->product_variant_id;
	}

	/**
	 * @param mixed $product_variant_id
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function setProductVariantId( $product_variant_id ) {
		$this->product_variant_id = $product_variant_id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * @param mixed $quantity
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function setQuantity( $quantity ) {
		$this->quantity = $quantity;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @param mixed $price
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function setPrice( $price ) {
		$this->price = $price;

		return $this;
	}

	/**
	 * @param $fallback
	 * @return $this
	 */
	public function setFallbackTitle( $fallback ) {
		$this->fallback_title = $fallback;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getFallbackTitle() {
		return $this->fallback_title;
	}

	/**
	 * @param $fallback
	 * @return $this
	 */
	public function setFallbackSku( $fallback ) {
		$this->fallback_sku = $fallback;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getFallbackSku() {
		return $this->fallback_sku;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return mailchimp_array_remove_empty(
			array(
				'id'                 => (string) $this->id,
				'product_id'         => (string) $this->product_id,
				'product_variant_id' => (string) $this->product_variant_id,
				'quantity'           => (int) $this->quantity,
				'price'              => (string) $this->price,
			)
		);
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_LineItem
	 */
	public function fromArray( array $data ) {
		$singles = array(
			'id',
			'product_id',
			'product_variant_id',
			'quantity',
			'price',
		);

		foreach ( $singles as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}

		return $this;
	}
}

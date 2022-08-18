<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:17 PM
 */
class MailChimp_WooCommerce_ProductVariation {

	protected $id                 = null;
	protected $title              = null;
	protected $url                = null;
	protected $sku                = null;
	protected $price              = null;
	protected $inventory_quantity = null;
	protected $image_url          = null;
	protected $backorders         = null;
	protected $visibility         = null;

	/**
	 * @return array
	 */
	public function getValidation() {
		return array(
			'id'                 => 'required|string',
			'title'              => 'required|string',
			'url'                => 'url',
			'sku'                => 'string',
			'price'              => 'numeric',
			'inventory_quantity' => 'integer',
			'image_url'          => 'url',
			'backorders'         => 'string',
			'visibility'         => 'string',
		);
	}

	/**
	 * @return null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param null $id
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param null $title
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setTitle( $title ) {
		$this->title = strip_tags( $title );

		return $this;
	}

	/**
	 * @return null
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param null $url
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setUrl( $url ) {
		$this->url = $url;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSku() {
		return $this->sku;
	}

	/**
	 * @param null $sku
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setSku( $sku ) {
		$this->sku = $sku;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @param null $price
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setPrice( $price ) {
		$this->price = $price;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getInventoryQuantity() {
		return $this->inventory_quantity;
	}

	/**
	 * @param null $inventory_quantity
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setInventoryQuantity( $inventory_quantity ) {
		$this->inventory_quantity = $inventory_quantity;

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getImageUrl() {
		return ! empty( $this->image_url ) ? (string) $this->image_url : null;
	}

	/**
	 * @param null $image_url
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setImageUrl( $image_url ) {
		$this->image_url = $image_url;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getBackorders() {
		return $this->backorders;
	}

	/**
	 * @param null $backorders
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setBackorders( $backorders ) {
		$this->backorders = $backorders;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getVisibility() {
		return $this->visibility;
	}

	/**
	 * @param null $visibility
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function setVisibility( $visibility ) {
		$this->visibility = $visibility;

		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return mailchimp_array_remove_empty(
			array(
				'id'                 => (string) $this->getId(),
				'title'              => $this->getTitle(),
				'url'                => (string) $this->getUrl(),
				'sku'                => (string) $this->getSku(),
				'price'              => $this->getPrice(),
				'inventory_quantity' => (int) $this->getInventoryQuantity(),
				'image_url'          => (string) $this->getImageUrl(),
				'backorders'         => $this->getBackorders() ? 'true' : 'false',
				'visibility'         => (string) $this->getVisibility(),
			)
		);
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_ProductVariation
	 */
	public function fromArray( array $data ) {
		$singles = array(
			'id',
			'title',
			'url',
			'sku',
			'price',
			'inventory_quantity',
			'image_url',
			'backorders',
			'visibility',
		);

		foreach ( $singles as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}

		return $this;
	}
}

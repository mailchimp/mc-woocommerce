<?php

/**
 * Created by Vextras.
 */

class MailChimp_WooCommerce_Single_Product_Variation extends Mailchimp_Woocommerce_Job
{
	public $id;
	public $parent_product_id;
	public $fallback_title;
	protected $store_id;
	protected $api;
	protected $service;
	protected $mode = 'update_or_create';
	protected $order_item = null;

	/**
	 * MailChimp_WooCommerce_Single_Product constructor.
	 *
	 * @param null $id
	 * @param null $fallback_title
	 */
	public function __construct($id = null, $fallback_title = null)
	{
		$this->setId($id);
		$this->setProductParentId($id);
		$this->setFallbackTitle($fallback_title);
	}

	/**
	 * @param null $id
	 * @return MailChimp_WooCommerce_Single_Product_Variation
	 */
	public function setId($id)
	{
		if (!empty($id)) {
			// when we pass in a wc product or an object that has a method called get_id we can use it.
			if ($id instanceof WC_Product_Variation || (is_object($id) && method_exists($id, 'get_id'))) {
				$this->id = $id->get_id();
			} else {
				$this->id = $id instanceof WP_Post ? $id->ID : $id;
			}
		}
		return $this;
	}

	/**
	 * @param null $id
	 * @return MailChimp_WooCommerce_Single_Product_Variation
	 */
	public function setProductParentId($id)
	{
		if (!empty($id)) {
			// when we pass in a wc product or an object that has a method called get_id we can use it.
			if ($id instanceof WC_Product_Variation || (is_object($id) && method_exists($id, 'get_parent_id'))) {
				$this->parent_product_id = $id->get_parent_id();
			} else if ($id instanceof WP_Post){
				$product = MailChimp_WooCommerce_HPOS::get_product($id->ID);

				$this->parent_product_id = $product ? $product->get_parent_id() : null;
			} else {
				$product = MailChimp_WooCommerce_HPOS::get_product($id);

				$this->parent_product_id = $product ? $product->get_parent_id() : null;
			}
		}

		return $this;
	}

	/**
	 * @param $title
	 * @return $this
	 */
	public function setFallbackTitle($title)
	{
		$this->fallback_title = $title;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function createModeOnly()
	{
		$this->mode = 'create';
		return $this;
	}

	/**
	 * @return $this
	 */
	public function updateModeOnly()
	{
		$this->mode = 'update';

		return $this;
	}

	/**
	 * @return $this
	 */
	public function updateOrCreateMode()
	{
		$this->mode = 'update_or_create';

		return $this;
	}

	/**
	 * @param MailChimp_WooCommerce_LineItem $item
	 * @return $this
	 */
	public function fromOrderItem(MailChimp_WooCommerce_LineItem $item)
	{
		$this->order_item = $item;
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
	 * @return bool|MailChimp_WooCommerce_ProductVariation
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function process()
	{
		if (empty($this->id)) {
			return false;
		}

		if (!mailchimp_is_configured()) {
			mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
			return false;
		}

		$this->setProductParentId($this->id);

		$method = "no action";

		try {
			if( !($variant_post = MailChimp_WooCommerce_HPOS::get_product($this->id)) ){
				return false;
			}

			try {
				// pull the product variation from Mailchimp first to see what method we need to call next.
				mailchimp_debug('product_variation', 'getStoreProductVariant', [
					'id' => $this->id,
					'parent_id' => $this->parent_product_id,
				]);
				$mailchimp_product = $this->api()->getStoreProductVariant($this->store_id, $this->parent_product_id, $this->id, true);
			} catch (Exception $e) {
				if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
					throw $e;
				}
				$mailchimp_product = false;
			}

			// depending on if it's existing or not - we change the method call
			$method = $mailchimp_product ? 'updateStoreProductVariation' : 'addStoreProductVariation';

			// if the mode set is "create" and the product is in Mailchimp - just return the product.
			if ($this->mode === 'create' && !empty($mailchimp_product)) {
				return $mailchimp_product;
			}

			// if the mode is set to "update" and the product is not currently in Mailchimp - skip it.
			if ($this->mode === 'update' && empty($mailchimp_product)) {
				return false;
			}

			$product_variant = $this->transformer()->variant($variant_post, $this->fallback_title);

			if (empty($product_variant->getTitle()) && !empty($this->fallback_title)) {
				$product_variant->setTitle($this->fallback_title);
			}

			mailchimp_debug('product_variant_submit.debug', "#{$this->id}", $product_variant->toArray());

			if (!$product_variant->getId() || !$product_variant->getTitle()) {
				mailchimp_log('product_variant_submit.warning', "{$method} :: post #{$this->id} was invalid.");
				return false;
			}

			// either updating or creating the product
			$this->api()->{$method}($this->store_id, $product_variant, false);

			mailchimp_log('product_variant_submit.success', "{$method} :: #{$product_variant->getId()}");

			\Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-last_product_updated', $product_variant->getId());

			return $product_variant;

		} catch (MailChimp_WooCommerce_RateLimitError $e) {
			sleep(3);
			mailchimp_error('product_variant_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
			$this->applyRateLimitedScenario();
			throw $e;
		} catch (MailChimp_WooCommerce_ServerError $e) {
			mailchimp_error('product_variant_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
			throw $e;
		} catch (MailChimp_WooCommerce_Error $e) {
			mailchimp_log('product_variant_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
			throw $e;
		} catch (Exception $e) {
			mailchimp_log('product_variant_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
			throw $e;
		}
	}

	/**
	 * @return MailChimp_WooCommerce_MailChimpApi
	 */
	public function api()
	{
		if (is_null($this->api)) {

			$this->store_id = mailchimp_get_store_id();
			$options = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce', array());

			if (!empty($this->store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {
				return $this->api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);
			}

			throw new RuntimeException('The MailChimp API is not currently configured!');
		}

		return $this->api;
	}

	/**
	 * @return MailChimp_WooCommerce_Transform_Products
	 */
	public function transformer()
	{
		if (is_null($this->service)) {
			return $this->service = new MailChimp_WooCommerce_Transform_Products();
		}

		return $this->service;
	}
}

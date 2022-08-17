<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Single_Product extends Mailchimp_Woocommerce_Job
{
    public $id;
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
        $this->setFallbackTitle($fallback_title);
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_Single_Product
     */
    public function setId($id)
    {
        if (!empty($id)) {
            $this->id = $id instanceof WP_Post ? $id->ID : $id;
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
	 * @return bool|MailChimp_WooCommerce_Product
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

        $method = "no action";

        try {

            if (!($product_post = get_post($this->id))) {
                return false;
            }

            try {
                // pull the product from Mailchimp first to see what method we need to call next.
                $mailchimp_product = $this->api()->getStoreProduct($this->store_id, $this->id, true);
            } catch (Exception $e) {
                if ($e instanceof MailChimp_WooCommerce_RateLimitError) {
                    throw $e;
                }
                $mailchimp_product = false;
            }

            // depending on if it's existing or not - we change the method call
            $method = $mailchimp_product ? 'updateStoreProduct' : 'addStoreProduct';

            // if the mode set is "create" and the product is in Mailchimp - just return the product.
            if ($this->mode === 'create' && !empty($mailchimp_product)) {
                return $mailchimp_product;
            }

            // if the mode is set to "update" and the product is not currently in Mailchimp - skip it.
            if ($this->mode === 'update' && empty($mailchimp_product)) {
                return false;
            }

            // if qe instructed this job to build from the order item, let's do that instead of the product post.
            if ($this->order_item) {
                mailchimp_debug('product_submit.debug', 'using order item', array('item' => $this->order_item));
                $product = $this->transformer()->fromOrderItem($this->order_item);
            } else {
                $product = $this->transformer()->transform($product_post, $this->fallback_title);
            }

            if (empty($product->getTitle()) && !empty($this->fallback_title)) {
                $product->setTitle($this->fallback_title);
            }

            mailchimp_debug('product_submit.debug', "#{$this->id}", $product->toArray());

            if (!$product->getId() || !$product->getTitle()) {
                mailchimp_log('product_submit.warning', "{$method} :: post #{$this->id} was invalid.");
                return false;
            }

            // either updating or creating the product
            $this->api()->{$method}($this->store_id, $product, false);

            mailchimp_log('product_submit.success', "{$method} :: #{$product->getId()}");

            update_option('mailchimp-woocommerce-last_product_updated', $product->getId());

            return $product;

        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            mailchimp_error('product_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
            $this->applyRateLimitedScenario();
            throw $e;
        } catch (MailChimp_WooCommerce_ServerError $e) {
            mailchimp_error('product_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
            throw $e;
        } catch (MailChimp_WooCommerce_Error $e) {
            mailchimp_log('product_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
            throw $e;
        } catch (Exception $e) {
            mailchimp_log('product_submit.error', mailchimp_error_trace($e, "{$method} :: #{$this->id}"));
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
            $options = get_option('mailchimp-woocommerce', array());

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

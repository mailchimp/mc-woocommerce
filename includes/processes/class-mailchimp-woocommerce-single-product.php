<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Single_Product extends WP_Job
{
    public $product_id;
    protected $store_id;
    protected $api;
    protected $service;

    /**
     * MailChimp_WooCommerce_Single_Order constructor.
     * @param null|int $product_id
     */
    public function __construct($product_id = null)
    {
        if (!empty($product_id)) {
            $this->product_id = $product_id instanceof WP_Post ? $product_id->ID : $product_id;
        }
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $this->process();

        return false;
    }

    /**
     * @return bool|MailChimp_WooCommerce_Product
     */
    public function process()
    {
        if (empty($this->product_id)) {
            return false;
        }

        if (!mailchimp_is_configured()) {
            mailchimp_debug(get_called_class(), 'mailchimp is not configured properly');
            return false;
        }

        if ($this->api()->getStoreProduct($this->store_id, $this->product_id)) {
            $this->api()->deleteStoreProduct($this->store_id, $this->product_id);
        }

        try {

            if (!($product_post = get_post($this->product_id))) {
                return false;
            }

            $product = $this->transformer()->transform($product_post);

            mailchimp_debug('product_submit.debug', "#{$this->product_id}", $product->toArray());

            $this->api()->addStoreProduct($this->store_id, $product, false);

            mailchimp_log('product_submit.success', "addStoreProduct :: #{$product->getId()}");

            update_option('mailchimp-woocommerce-last_product_updated', $product->getId());

            return $product;

        } catch (MailChimp_WooCommerce_ServerError $e) {
            mailchimp_error('product_submit.error', mailchimp_error_trace($e, "addStoreProduct :: #{$this->product_id}"));
        } catch (MailChimp_WooCommerce_Error $e) {
            mailchimp_log('product_submit.error', mailchimp_error_trace($e, "addStoreProduct :: #{$this->product_id}"));
        } catch (Exception $e) {
            mailchimp_log('product_submit.error', mailchimp_error_trace($e, "addStoreProduct :: #{$this->product_id}"));
        }

        return false;
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

            throw new \RuntimeException('The MailChimp API is not currently configured!');
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

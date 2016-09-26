<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
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
     * @return MailChimp_Product
     * @throws Exception
     */
    public function process()
    {
        if ($this->api()->getStoreProduct($this->store_id, $this->product_id)) {
            $this->api()->deleteStoreProduct($this->store_id, $this->product_id);
        }

        $product = $this->transformer()->transform(get_post($this->product_id));

        $response = $this->api()->addStoreProduct($this->store_id, $product);

        if ($response) {
            mailchimp_log('product_submit.success', 'Added', array('api_response' => $response->toArray()));
        }

        update_option('mailchimp-woocommerce-last_product_updated', $product->getId());

        return $product;
    }

    /**
     * @return MailChimpApi
     */
    public function api()
    {
        if (is_null($this->api)) {

            $this->store_id = mailchimp_get_store_id();
            $options = get_option('mailchimp-woocommerce', array());

            if (!empty($this->store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {
                return $this->api = new MailChimpApi($options['mailchimp_api_key']);
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

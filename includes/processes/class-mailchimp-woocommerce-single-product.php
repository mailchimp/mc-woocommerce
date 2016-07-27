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
        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

            $job = new MailChimp_WooCommerce_Transform_Products();
            $api = new MailChimpApi($options['mailchimp_api_key']);

            $api->deleteStoreProduct($store_id, $this->product_id);
            $api->addStoreProduct($store_id, ($product = $job->transform(get_post($this->product_id))));

            update_option('mailchimp-woocommerce-last_product_updated', $product->getId());
        }

        return false;
    }
}

<?php

class Mailchimp_WooCommerce_Single_Product_Category extends Mailchimp_Woocommerce_Job
{
    public $id;
    public $service;
    public $api;
    public $store_id;

    /**
     * MailChimp_WooCommerce_Single_Product constructor.
     *
     * @param null $id
     * @param null $fallback_title
     */
    public function __construct($id = null)
    {
        $this->setId($id);
    }

    /**
     * @param null $id
     * @return Mailchimp_WooCommerce_Single_Product_Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function handle()
    {
        $this->process();

        return false;
    }

    /**
     * @return false|Mailchimp_WooCommerce_Product_Category
     * @throws Exception
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

        try {

            if( !($category_term = get_term($this->id, 'product_cat')) ){
                mailchimp_log('product_category', "tried to load product_category by ID {$this->id} but did not find it.");
                return false;
            }

            $category = $this->transformer()->transform($category_term);

            mailchimp_debug('product_category_submit.debug', "#{$this->id}", $category->toArray());

            if (!$category->getId() || !$category->getName()) {
                mailchimp_log('product_category_submit.warning', "post #{$this->id} was invalid.");
                return false;
            }

            // either updating or creating the product
            $categoryUpdated = $this->api()->updateProductCategory($this->store_id, $category->getId(), $category);

            if ($categoryUpdated) {
                $product_ids = get_posts(array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => [
                        [
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $category->getId(),
                        ]
                    ]
                ));

                if ($product_ids) {
                    $this->api()->syncProductsToCollection(mailchimp_get_store_id(), $category->getId(), $product_ids);
                }
            }

            mailchimp_log('product_category_submit.success', "#{$category->getId()}");
            // increment the sync counter
            mailchimp_register_synced_resource('products');
            \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-last_product_category_updated', $category->getId());

            return $category;
        } catch (Exception $e) {
            mailchimp_log('product_category_submit.error', array(
                'error' => $e->getMessage(),
            ));

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
     * @return MailChimp_WooCommerce_Transform_Product_Categories
     */
    public function transformer()
    {
        if (is_null($this->service)) {
            return $this->service = new MailChimp_WooCommerce_Transform_Product_Categories();
        }

        return $this->service;
    }
}
<?php

class Mailchimp_WooCommerce_Single_Product_Category extends Mailchimp_Woocommerce_Job
{
    public $id;
    public $service;
    public $api;
    public $store_id;
    private $handle_failed_products;
    /**
     * MailChimp_WooCommerce_Single_Product constructor.
     *
     * @param null $id
     * @param null $fallback_title
     */
    public function __construct($id = null, $handle_failed_products = true)
    {
        $this->setId($id);

        $this->handle_failed_products = $handle_failed_products;
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
            $category_updated = $this->api()->updateProductCategory($this->store_id, $category->getId(), $category);

            if ($category_updated) {
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
                    $synced_products = $this->api()->syncProductsToCollection(mailchimp_get_store_id(), $category->getId(), $product_ids);

                    mailchimp_debug('product_category.products_sync', "Synced products to category #{$this->id}", [
                        'products' => $synced_products['products'],
                        'failed_product_ids' => $synced_products['failed_product_ids']
                    ]);

                    if (count($synced_products['failed_product_ids']) > 0) {
                        if ($this->handle_failed_products) {
                            $this->handleFailedProductsSync($synced_products['failed_product_ids']);
                        } else {
                            mailchimp_debug('product_category.products_sync', 'Failed to resync products', [
                                'failed_product_ids' => $synced_products['failed_product_ids']
                            ]);
                        }
                    }
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
     * @param $product_ids
     * @return void
     */
    public function handleFailedProductsSync($product_ids)
    {
        foreach ($product_ids as $product_id) {
            mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($product_id));
        }

        mailchimp_handle_or_queue(new self($this->id, false), 1);
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
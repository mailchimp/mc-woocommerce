<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

class Mailchimp_Woocommerce_Blocks_Extend_Cart_Item {
    public static function init() {
        $extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get(Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class);
        $extend->register_endpoint_data(array(
            'endpoint' => CartItemSchema::IDENTIFIER,
            'namespace' => 'mailchimp-pixel',
            'data_callback' => array(__CLASS__, 'get_product_identity'),
            'schema_callback' => array(__CLASS__, 'get_schema'),
            'schema_type' => ARRAY_A,
        ));
    }

    public static function get_product_identity($cart_item) {
        $product = $cart_item['data'];
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : 0;

        return array('product_id' => (string) ($parent_id ?: $product->get_id()));
    }

    public static function get_schema() {
        return array(
            'product_id' => array(
                'description' => __('Parent catalog product ID for pixel events.', 'mailchimp-for-woocommerce'),
                'type' => 'string',
                'readonly' => true,
            ),
        );
    }
}

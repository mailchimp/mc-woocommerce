<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/13/16
 * Time: 8:29 AM
 */
class MailChimp_WooCommerce_Transform_Products
{
    /**
     * @param int $page
     * @param int $limit
     * @return \stdClass
     */
    public function compile($page = 1, $limit = 10)
    {
        $response = (object) array(
            'endpoint' => 'products',
            'page' => $page,
            'limit' => (int) $limit,
            'count' => 0,
            'stuffed' => false,
            'items' => array(),
        );

        if ((($products = $this->getProductPosts($page, $limit)) && !empty($products))) {
            foreach ($products as $post) {
                $response->items[] = $this->transform($post);
                $response->count++;
            }
        }

        $response->stuffed = ($response->count > 0 && (int) $response->count === (int) $limit) ? true : false;

        return $response;
    }

    /**
     * @param WP_Post $post
     * @return MailChimp_WooCommerce_Product
     */
    public function transform(WP_Post $post)
    {
        $woo = new WC_Product($post);

        $variant_posts = $this->getProductVariantPosts($post->ID);

        $variants = $variant_posts ? array_merge(array($woo), $variant_posts) : array($woo);

        $is_variant = count($variants) > 1;

        $product = new MailChimp_WooCommerce_Product();

        $product->setId($woo->get_id());
        $product->setHandle($post->post_name);
        $product->setImageUrl(get_the_post_thumbnail_url($post));
        $product->setDescription($post->post_content);
        $product->setPublishedAtForeign(mailchimp_date_utc($post->post_date));
        $product->setTitle($woo->get_title());
        $product->setUrl($woo->get_permalink());

        foreach ($variants as $variant) {

            $product_variant = $this->variant($is_variant, $variant, $woo->get_title());

            $product_variant_title = $product_variant->getTitle();

            if (empty($product_variant_title)) {
                $product_variant->setTitle($woo->get_title());
            }

            $product_variant_image = $product_variant->getImageUrl();

            if (empty($product_variant_image)) {
                $product_variant->setImageUrl($product->getImageUrl());
            }

            $product->addVariant($product_variant);
        }

        return $product;
    }

    /**
     * @param $is_variant
     * @param WP_Post $post
     * @param string $fallback_title
     * @return MailChimp_WooCommerce_ProductVariation
     */
    public function variant($is_variant, $post, $fallback_title = null)
    {
        if ($post instanceof WC_Product || $post instanceof WC_Product_Variation) {
            $woo = $post;
        } else {
            if (isset($post->post_type) && $post->post_type === 'product_variation') {
                $woo = new WC_Product_Variation($post->ID);
            } else {
                $woo = new WC_Product($post);
            }
        }

        $variant = new MailChimp_WooCommerce_ProductVariation();

        $variant->setId($woo->get_id());
        $variant->setUrl($woo->get_permalink());
        $variant->setBackorders($woo->backorders_allowed());
        $variant->setImageUrl(get_the_post_thumbnail_url($post));
        $variant->setInventoryQuantity(($woo->managing_stock() ? $woo->get_stock_quantity() : 1));
        $variant->setPrice($woo->get_price());
        $variant->setSku($woo->get_sku());

        if ($woo instanceof WC_Product_Variation) {

            $variation_title = $woo->get_title();
            if (empty($variation_title)) $variation_title = $fallback_title;

            $title = array($variation_title);

            foreach ($woo->get_variation_attributes() as $attribute => $value) {
                if (is_string($value)) {
                    $name = ucfirst(str_replace(array('attribute_pa_', 'attribute_'), '', $attribute));
                    $title[] = "$name = $value";
                }
            }

            $variant->setTitle(implode(' :: ', $title));
            $variant->setVisibility(($woo->variation_is_visible() ? 'visible' : ''));
        } else {
            $variant->setVisibility(($woo->is_visible() ? 'visible' : ''));
            $variant->setTitle($woo->get_title());
        }

        return $variant;
    }

    /**
     * @param int $page
     * @param int $posts
     * @return array|bool
     */
    public function getProductPosts($page = 1, $posts = 10)
    {
        $products = get_posts(array(
            'post_type' => array('product'),
            'posts_per_page' => $posts,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));

        if (empty($products)) {
            return false;
        }

        return $products;
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function getProductVariantPosts($id)
    {
        $variants = get_posts(array(
            'numberposts' => 99999,
            'order' => 'ASC',
            'orderby' => 'ID',
            'post_type' => 'product_variation',
            'post_parent' => $id,
        ));

        if (empty($variants)) {
            return false;
        }

        return $variants;
    }

    /**
     * @param $id
     * @return MailChimp_WooCommerce_Product
     */
    public static function deleted($id)
    {
        $store_id = mailchimp_get_store_id();
        $api = mailchimp_get_api();

        if (!($product = $api->getStoreProduct($store_id, "deleted_{$id}"))) {
            $product = new MailChimp_WooCommerce_Product();

            $product->setId("deleted_{$id}");
            $product->setTitle("deleted_{$id}");

            $variant = new MailChimp_WooCommerce_ProductVariation();
            $variant->setId("deleted_{$id}");
            $variant->setTitle("deleted_{$id}");

            $product->addVariant($variant);

            return $api->addStoreProduct($store_id, $product);
        }

        return $product;
    }
}

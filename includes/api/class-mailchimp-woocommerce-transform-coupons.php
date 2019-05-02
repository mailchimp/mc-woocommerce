<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 10/06/17
 * Time: 8:29 AM
 */
class MailChimp_WooCommerce_Transform_Coupons
{
    /**
     * @param int $page
     * @param int $limit
     * @return \stdClass
     */
    public function compile($page = 1, $limit = 5)
    {
        $response = (object) array(
            'endpoint' => 'coupons',
            'page' => $page ? $page : 1,
            'limit' => (int) $limit,
            'count' => 0,
            'stuffed' => false,
            'items' => array(),
        );

        if ((($coupons = $this->getCouponPosts($page, $limit)) && !empty($coupons))) {
            foreach ($coupons as $post) {
                $response->items[] = $this->transform($post->ID);
                $response->count++;
            }
        }

        $response->stuffed = ($response->count > 0 && (int) $response->count === (int) $limit) ? true : false;

        return $response;
    }

    /**
     * @param int $post_id
     * @return MailChimp_WooCommerce_PromoCode
     */
    public function transform($post_id)
    {
        $resource = new WC_Coupon($post_id);
        $valid = true;

        if (($exp = $resource->get_date_expires()) && current_time('timestamp', true) > $exp->getTimestamp()) {
            $valid = false;
        }

        $rule = new MailChimp_WooCommerce_PromoRule();

        $rule->setId($resource->get_id());
        $rule->setTitle($resource->get_code());
        $rule->setDescription($resource->get_description());
        $rule->setEnabled($valid);
        $rule->setAmount($resource->get_amount('edit'));

        if (!$rule->getDescription()) {
            $rule->setDescription($resource->get_code());
        }

        switch ($resource->get_discount_type()) {
            case 'fixed_product':
            // Support to Woocommerce Free Gift Coupon Plugin 
            case 'free_gift':
                $rule->setTypeFixed();
                $rule->setTargetTypePerItem();
                break;

            case 'fixed_cart':
                $rule->setTypeFixed();
                $rule->setTargetTypeTotal();
                break;

            case 'percent':
                $rule->setTypePercentage();
                $rule->setTargetTypeTotal();
                $rule->setAmount(($resource->get_amount('edit')/100));
                break;
        }

        if (($exp = $resource->get_date_expires())) {
            $rule->setEndsAt($exp);
        }

        $code = new MailChimp_WooCommerce_PromoCode();

        $code->setId($resource->get_id());
        $code->setCode($resource->get_code());
        $code->setEnabled($valid);
        $code->setRedemptionURL(get_home_url());
        $code->setUsageCount($resource->get_usage_count());

        // attach the rule for use.
        $code->attachPromoRule($rule);

        return $code;
    }

    /**
     * @param int $page
     * @param int $posts
     * @return array|bool
     */
    public function getCouponPosts($page = 1, $posts = 5)
    {
        $coupons = get_posts(array(
            'post_type' => array_merge(array_keys(wc_get_product_types()), array('shop_coupon')),
            'posts_per_page' => $posts,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));

        if (empty($coupons)) {

            sleep(2);

            $coupons = get_posts(array(
                'post_type' => array_merge(array_keys(wc_get_product_types()), array('shop_coupon')),
                'posts_per_page' => $posts,
                'paged' => $page,
                'orderby' => 'ID',
                'order' => 'ASC',
            ));

            if (empty($coupons)) {
                return false;
            }
        }

        return $coupons;
    }
}

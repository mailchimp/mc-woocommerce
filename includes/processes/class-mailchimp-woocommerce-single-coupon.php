<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 10/6/17
 * Time: 11:14 AM
 */
class MailChimp_WooCommerce_SingleCoupon extends WP_Job
{
    public $coupon_data;
    public $post_id;

    /**
     * MailChimp_WooCommerce_Coupon_Sync constructor.
     * @param $post_id
     */
    public function __construct($post_id = null)
    {
        $this->post_id = $post_id;
    }

    /**
     * @return null
     */
    public function handle()
    {
        try {

            if (!mailchimp_is_configured()) {
                mailchimp_debug(get_called_class(), 'mailchimp is not configured properly');
                return false;
            }

            if (empty($this->post_id)) {
                mailchimp_error('promo_code.failure', "could not process coupon {$this->post_id}");
                return;
            }

            $api = mailchimp_get_api();
            $store_id = mailchimp_get_store_id();

            $transformer = new MailChimp_WooCommerce_Transform_Coupons();
            $code = $transformer->transform($this->post_id);

            $api->addPromoRule($store_id, $code->getAttachedPromoRule(), true);
            $api->addPromoCodeForRule($store_id, $code->getAttachedPromoRule(), $code, true);

            mailchimp_log('promo_code.update', "updated promo code {$code->getCode()}");
        } catch (\Exception $e) {
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->post_id}";
            mailchimp_error('promo_code.error', mailchimp_error_trace($e, "error updating promo {$promo_code}"));
        }
    }
}

<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 10/6/17
 * Time: 11:14 AM
 */
class MailChimp_WooCommerce_SingleCoupon extends Mailchimp_Woocommerce_Job
{
    public $coupon_data;
    public $id;

    /**
     * MailChimp_WooCommerce_Coupon_Sync constructor.
     * @param $id
     */
    public function __construct($id = null)
    {
        $this->setId($id);
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_SingleCoupon
     */
    public function setId($id)
    {
        if (!empty($id)) {
            $this->id = $id instanceof WP_Post ? $id->ID : $id;
        }
    }
    
    /**
     * @return null
     */
    public function handle()
    {
        try {

            if (!mailchimp_is_configured()) {
                mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
                return false;
            }

            if (empty($this->id)) {
                mailchimp_error('promo_code.failure', "could not process coupon {$this->id}");
                return;
            }

            $api = mailchimp_get_api();
            $store_id = mailchimp_get_store_id();

            $transformer = new MailChimp_WooCommerce_Transform_Coupons();
            $code = $transformer->transform($this->id);

            $api->addPromoRule($store_id, $code->getAttachedPromoRule(), true);
            $api->addPromoCodeForRule($store_id, $code->getAttachedPromoRule(), $code, true);

            mailchimp_log('promo_code.update', "updated promo code {$code->getCode()}");
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            $this->release();
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code.error', mailchimp_error_trace($e, "RateLimited :: #{$promo_code}"));
        } catch (\Exception $e) {
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code.error', mailchimp_error_trace($e, "error updating promo {$promo_code}"));
        }
    }
}

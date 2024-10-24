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
        return $this;
    }

	/**
	 * @return false|void
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function handle()
    {
        try {

            if (!mailchimp_is_configured()) {
                mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
                return false;
            }

            if (empty($this->id)) {
                mailchimp_error('promo_code_submit.failure', "could not process coupon {$this->id}");
                return;
            }

            $api = mailchimp_get_api();
            $store_id = mailchimp_get_store_id();

            $transformer = new MailChimp_WooCommerce_Transform_Coupons();
            $code = $transformer->transform($this->id);

            $api->addPromoRule($store_id, $code->getAttachedPromoRule());
            $api->addPromoCodeForRule($store_id, $code->getAttachedPromoRule(), $code);

            mailchimp_register_synced_resource('coupons');

            mailchimp_log('promo_code_submit.success', "#{$this->id} :: code: {$code->getCode()}");
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code_submit.error', mailchimp_error_trace($e, "RateLimited :: #{$promo_code}"));
            $this->applyRateLimitedScenario();
            throw $e;
        } catch (MailChimp_WooCommerce_ServerError $e) {
	        $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code_submit.error', mailchimp_error_trace($e, "error updating promo rule #{$this->id} :: {$promo_code}"));
            throw $e;
        } catch (MailChimp_WooCommerce_Error $e) {
	        $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code_submit.error', mailchimp_error_trace($e, "error updating promo rule #{$this->id} :: {$promo_code}"));
            throw $e;
        } catch (Exception $e) {
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$this->id}";
            mailchimp_error('promo_code_submit.exception', mailchimp_error_trace($e, "error updating promo rule :: {$promo_code}"));
            throw $e;
        }
        return;
    }
}

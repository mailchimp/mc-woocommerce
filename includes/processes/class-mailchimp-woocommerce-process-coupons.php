<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 10/06/17
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Coupons extends MailChimp_WooCommerce_Abstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_coupons';

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'coupons';
    }

    /**
     * @param WC_Coupon $item
     *
     * @return mixed
     */
    protected function iterate($item)
    {
        if ($item instanceof MailChimp_WooCommerce_PromoCode) {

            mailchimp_debug('promo_code_sync', "#{$item->getId()}", $item->toArray());

            try {
                $this->mailchimp()->addPromoRule($this->store_id, $item->getAttachedPromoRule(), true);
                $response = $this->mailchimp()->addPromoCodeForRule($this->store_id, $item->getAttachedPromoRule(), $item, true);
                mailchimp_log('coupon_sync.success', "update promo rule :: #{$item->getCode()}");
                return $response;
            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_error('coupons.error', mailchimp_error_trace($e, "update promo rule :: {$item->getCode()}"));
                return false;
            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_error('coupons.error', mailchimp_error_trace($e, "update promo rule :: {$item->getCode()}"));
                return false;
            } catch (Exception $e) {
                mailchimp_error('coupons.error', mailchimp_error_trace($e, "update promo rule :: {$item->getCode()}"));
                return false;
            }
        }

        mailchimp_debug('coupon_sync', 'no coupon found', $item);

        return false;
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('coupon_sync.completed', 'Done with the coupon sync.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();
    }
}

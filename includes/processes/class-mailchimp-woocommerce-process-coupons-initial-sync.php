<?php

/**
 * Class MailChimp_WooCommerce_Process_Coupons_Then_Products
 */
class MailChimp_WooCommerce_Process_Coupons_Initial_Sync extends MailChimp_WooCommerce_Process_Coupons
{
    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_debug('coupon_sync.completed', 'Done with the coupon queueing');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();
    }
}

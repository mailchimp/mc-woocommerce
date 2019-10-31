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
        mailchimp_log('coupon_sync.completed', 'Done with the coupon sync, queuing up products.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();

        $product_sync = new MailChimp_WooCommerce_Process_Products();
        mailchimp_handle_or_queue($product_sync, 0);
    }
}

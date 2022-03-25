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
     * Resync the products
     */
    public static function push()
    {
        $service = MailChimp_Service::instance();
        $service->removePointers(true, false);
        $coupons_sync = new MailChimp_WooCommerce_Process_Coupons();
        $coupons_sync->createSyncManagers();
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'coupons';
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('coupon_sync.completed', 'Done with the coupon queueing.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();
    }
}

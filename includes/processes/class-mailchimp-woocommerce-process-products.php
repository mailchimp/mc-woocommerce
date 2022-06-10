<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Products extends MailChimp_WooCommerce_Abstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_products';

    /**
     * Resync the products
     */
    public static function push()
    {
        $service = MailChimp_Service::instance();
        $service->removePointers(true, false);
        $sync = new MailChimp_WooCommerce_Process_Products();
        $sync->createSyncManagers();
    }


    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'products';
    }

    /**
     * Called after all the products have been iterated and processed into MailChimp
     */
    protected function complete()
    {
        mailchimp_log('product_sync.completed', 'Done with the product queuing');

        // add a timestamp for the product sync completion
        $this->setResourceCompleteTime();
    }
}

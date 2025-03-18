<?php

class MailChimp_WooCommerce_Process_Product_Categories extends MailChimp_WooCommerce_Abstract_Sync
{

    protected $action = 'mailchimp_wooCommerce_process_product_categories';

    public function push()
    {
        $service = MailChimp_Service::instance();
        $service->removePointers(true, false);
        $coupons_sync = new MailChimp_WooCommerce_Process_Product_Categories();
        $coupons_sync->createSyncManagers();
    }

    public function getResourceType()
    {
        return 'product_categories';
    }

    protected function complete()
    {
        mailchimp_log('product_categories_sync.completed', 'Done with the product categories queuing');

        // add a timestamp for the product sync completion
        $this->setResourceCompleteQueueingTime();
    }
}
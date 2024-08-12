<?php

class MailChimp_WooCommerce_Process_Customers extends MailChimp_WooCommerce_Abstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_customers';

    /**
     * Resync the customers
     */
    public static function push()
    {
        $service = MailChimp_Service::instance();
        $service->removePointers(true, false);
        $customer_sync = new MailChimp_WooCommerce_Process_Customers();
        $customer_sync->createSyncManagers();
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'customers';
    }

    /**
     * @return void
     */
    protected function complete()
    {
        mailchimp_log('customers_sync.completed', 'Done with the customers queueing.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();
    }
}
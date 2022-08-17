<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Orders extends MailChimp_WooCommerce_Abstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_orders';
    public $items = array();

    /**
     * Resync just the orders
     */
    public static function push()
    {
        $service = MailChimp_Service::instance();
        $service->removePointers(false);
        $sync = new MailChimp_WooCommerce_Process_Orders();
        $sync->createSyncManagers();
        $service->setData('sync.config.resync', true);
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'orders';
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('order_sync.completed', 'Done with the order queueing.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();
    }

}

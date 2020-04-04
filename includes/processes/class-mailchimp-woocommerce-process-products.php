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

    public static function push()
    {
        $job = new MailChimp_WooCommerce_Process_Products();
        $job->flagStartSync();
        mailchimp_handle_or_queue($job, 0);
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
        mailchimp_log('product_sync.completed', 'Done with the product sync :: queuing up the orders next!');

        // add a timestamp for the product sync completion
        $this->setResourceCompleteTime();

        $prevent_order_sync = get_option('mailchimp-woocommerce-sync.orders.prevent', false);

        // only do this if we're not strictly syncing products ( which is the default ).
        if (!$prevent_order_sync) {
            // since the products are all good, let's sync up the orders now.
            $order_sync = new MailChimp_WooCommerce_Process_Orders(1, array('wc-completed'));
            // queue first job
            mailchimp_handle_or_queue($order_sync);
            //trigger subsequent jobs creation
            $order_sync->createSyncManagers();
        }

        // since we skipped the orders feed we can delete this option.
        delete_option('mailchimp-woocommerce-sync.orders.prevent');
    }
}

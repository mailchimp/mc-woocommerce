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
        mailchimp_handle_or_queue($job, 0, true);
    }


    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'products';
    }

    /**
     * @param MailChimp_WooCommerce_Product $item
     *
     * @return mixed
     */
    protected function iterate($item) {

        if ($item instanceof MailChimp_WooCommerce_Product) {

            mailchimp_debug('product_sync', "#{$item->getId()}", $item->toArray());

            try {
                // pull the product from Mailchimp first to see what method we need to call next.
                $mailchimp_product = $this->mailchimp()->getStoreProduct($this->store_id, $item->getId());
            } catch (\Exception $e) {
                $mailchimp_product = false;
            }

            // depending on if it's existing or not - we change the method call
            $method = $mailchimp_product ? 'updateStoreProduct' : 'addStoreProduct';

            // need to run the delete option on this before submitting because the API does not support PATCH yet.
            try {
                // make the call
                $response = $this->mailchimp()->{$method}($this->store_id, $item, false);
                mailchimp_log('product_sync.success', "{$method} :: #{$response->getId()}");
                return $response;
            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_error('product_sync.error', mailchimp_error_trace($e, "{$method} :: {$item->getId()}"));
            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_error('product_sync.error', mailchimp_error_trace($e, "{$method} :: {$item->getId()}"));
            } catch (Exception $e) {
                mailchimp_error('product_sync.error', mailchimp_error_trace($e, "{$method} :: {$item->getId()}"));
            }
        }

        return false;
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
            mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Orders());
        }

        // since we skipped the orders feed we can delete this option.
        delete_option('mailchimp-woocommerce-sync.orders.prevent');
    }
}

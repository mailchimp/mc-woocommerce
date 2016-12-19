<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Products extends MailChimp_WooCommerce_Abtstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_products';


    public static function push()
    {
        $job = new MailChimp_WooCommerce_Process_Products();
        $job->flagStartSync();
        wp_queue($job);
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

            // need to run the delete option on this before submitting because the API does not support PATCH yet.
            $this->mailchimp()->deleteStoreProduct($this->store_id, $item->getId());

            // add the product.
            try {
                mailchimp_log('sync.products.submitting', "addStoreProduct :: #{$item->getId()}");

                // make the call
                $response = $this->mailchimp()->addStoreProduct($this->store_id, $item);

                mailchimp_log('sync.products.success', "addStoreProduct :: #{$item->getId()}");

                return $response;

            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_log('sync.products.error', "addStoreProduct :: MailChimp_WooCommerce_Error :: {$e->getMessage()}");
            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_log('sync.products.error', "addStoreProduct :: MailChimp_WooCommerce_ServerError :: {$e->getMessage()}");
            } catch (Exception $e) {
                mailchimp_log('sync.products.error', "addStoreProduct :: Uncaught Exception :: {$e->getMessage()}");
            }
        }

        return false;
    }

    /**
     * Called after all the products have been iterated and processed into MailChimp
     */
    protected function complete()
    {
        mailchimp_log('sync.products.completed', 'Done with the product sync :: queuing up the orders next!');

        // add a timestamp for the product sync completion
        $this->setResourceCompleteTime();

        $prevent_order_sync = get_option('mailchimp-woocommerce-sync.orders.prevent', false);

        // only do this if we're not strictly syncing products ( which is the default ).
        if (!$prevent_order_sync) {
            // since the products are all good, let's sync up the orders now.
            wp_queue(new MailChimp_WooCommerce_Process_Orders());
        }

        // since we skipped the orders feed we can delete this option.
        delete_option('mailchimp-woocommerce-sync.orders.prevent');
    }
}

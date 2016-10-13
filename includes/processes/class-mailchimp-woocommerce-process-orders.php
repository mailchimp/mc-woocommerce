<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Orders extends MailChimp_WooCommerce_Abtstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_orders';
    public $items = array();

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'orders';
    }

    /**
     * @param MailChimp_WooCommerce_Order $item
     *
     * @return mixed
     */
    protected function iterate($item)
    {
        if ($item instanceof MailChimp_WooCommerce_Order) {

            // since we're syncing the customer for the first time, this is where we need to add the override
            // for subscriber status. We don't get the checkbox until this plugin is actually installed and working!

            if ((bool) $this->getOption('mailchimp_auto_subscribe', true)) {
                $item->getCustomer()->setOptInStatus(true);
            }

            $type = $this->mailchimp()->getStoreOrder($this->store_id, $item->getId()) ? 'update' : 'create';
            $call = $type === 'create' ? 'addStoreOrder' : 'updateStoreOrder';

            try {

                $log = "$call :: #{$item->getId()} :: email: {$item->getCustomer()->getEmailAddress()}";

                mailchimp_log('sync.orders.submitting', $log);

                // make the call
                $response = $this->mailchimp()->$call($this->store_id, $item, false);

                if (empty($response)) {
                    return $response;
                }

                mailchimp_log('sync.orders.success', $log);

                $this->items[] = array('response' => $response, 'item' => $item);

                return $response;

            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_log('sync.orders.error', "$call :: MailChimp_WooCommerce_Error :: {$e->getMessage()}");
            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_log('sync.orders.error', "$call :: MailChimp_WooCommerce_ServerError :: {$e->getMessage()}");
            } catch (Exception $e) {
                mailchimp_log('sync.orders.error', "$call :: Uncaught Exception :: {$e->getMessage()}");
            }
        }

        return false;
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('sync.orders.completed', 'Done with the order sync.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();

        // this is the last thing we're doing so it's complete as of now.
        $this->flagStopSync();
    }
}

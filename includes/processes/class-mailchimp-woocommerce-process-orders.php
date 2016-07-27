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
     * @param MailChimp_Order $item
     *
     * @return mixed
     */
    protected function iterate($item)
    {
        if ($item instanceof MailChimp_Order) {
            $type = $this->mailchimp()->getStoreOrder($this->store_id, $item->getId()) ? 'update' : 'create';
            $call = $type === 'create' ? 'addStoreOrder' : 'updateStoreOrder';
            try {
                $response = $this->mailchimp()->$call($this->store_id, $item);
                $this->items[] = array('response' => $response, 'item' => $item);
            } catch (\Exception $e) {
                error_log('MailChimp@ProcessOrders :: '.$call.' :: '.$e->getMessage());
            }
        }
        return false;
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();

        // this is the last thing we're doing so it's complete as of now.
        $this->flagStopSync();
    }
}

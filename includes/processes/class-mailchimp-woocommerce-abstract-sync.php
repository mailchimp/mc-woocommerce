<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/14/16
 * Time: 11:54 AM
 */
abstract class MailChimp_WooCommerce_Abstract_Sync extends Mailchimp_Woocommerce_Job
{
    /**
     * @var MailChimp_WooCommerce_Api
     */
    private $api;

    /**
     * @var MailChimp_WooCommerce_MailChimpApi
     */
    private $mc;

    /**
     * @var string
     */
    private $plugin_name = 'mailchimp-woocommerce';

    /**
     * @var string
     */
    protected $store_id = '';

     /**
     * @var int
     */
    public $current_page = null;

    /**
     * @var int
     */
    public $items_per_page = 100;

    /**
     * MailChimp_WooCommerce_Abstract_Sync constructor.
     * @param int $current_page
     */
    public function __construct($current_page = 1)
    {
        $this->setCurrentPage($current_page);
    }

    /**
     * @return mixed
     */
    abstract public function getResourceType();

    /**
     * @return mixed
     */
    abstract protected function complete();

    /**
     * @return void
     */
    public function createSyncManagers()
    {
        switch ($this->getResourceType()) {
            case 'coupons':
                $post_count = mailchimp_get_coupons_count();
               break;
            case 'products':
                $post_count = mailchimp_get_product_count();
               break;
            case 'orders':
                $post_count = mailchimp_get_order_count();
               break;
           default:
                mailchimp_log('sync.error', $this->getResourceType().' is not a valid resource.');
               break;
        }
        
        $this->setData('sync.'.$this->getResourceType().'.started_at', time());

        $page = $this->getCurrentPage();

        while ($page - 1 <= ceil((int)$post_count / $this->items_per_page)) {
            $next = new static($page);
            mailchimp_handle_or_queue($next);
            $this->setResourcePagePointer(($page), $this->getResourceType());
            $page++;
        }
    }

	/**
	 * @param $current_page
	 */
    public function setCurrentPage($current_page)
    {
        $this->current_page = $current_page;
    }

     /**
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->current_page;
    }

	/**
	 * @return mixed|string
	 */
    public function getStoreID()
    {
        return mailchimp_get_store_id();
    }

	/**
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function handle()
    {
        if (!mailchimp_is_configured()) {
            mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
            return false;
        }

        /// if we set something for an emergency stop through our admin, honor this
        if (mailchimp_get_data('emergency_stop')) {
            mailchimp_log('tower', 'Emergency stop sync has been issued. Please contact support to re-enable', array(
                'class' => get_called_class(),
            ));
            return false;
        }

        if (!($this->store_id = $this->getStoreID())) {
            mailchimp_debug(get_called_class().'@handle', 'store id not loaded');
            return false;
        }

        // set the last loop timestamp
        mailchimp_set_data( 'sync.last_loop_at', time() );

        // if we're being rate limited - we need to pause here.
        if ($this->isBeingRateLimited()) {
            // wait a few seconds
            sleep(3);
            // check this again
            if ($this->isBeingRateLimited()) {
                // ok - hold off for a few - let's re-queue the job.
                mailchimp_debug(get_called_class().'@handle', 'being rate limited - pausing for a few seconds...');
                $this->retry();
                return false;
            }
        }

        $page = $this->getResources();
        
        if (empty($page)) {
            mailchimp_debug(get_called_class().'@handle', 'could not find any more '.$this->getResourceType().' records ending on page '.$this->getResourcePagePointer());
            // call the completed event to process further
            $this->resourceComplete($this->getResourceType());
            $this->complete();

            return false;
        }


        // if we've got a 0 count, that means we're done.
        if ($page->count <= 0) {

            mailchimp_debug(get_called_class().'@handle', $this->getResourceType().' :: completing now!');

            // reset the resource page back to 1
            $this->resourceComplete($this->getResourceType());

            // call the completed event to process further
            $this->complete();

            return false;
        }

        // iterate through the items and send each one through the pipeline based on this class.
        foreach ($page->items as $resource) {
           switch ($this->getResourceType()) {
                case 'coupons':
                    mailchimp_handle_or_queue(new MailChimp_WooCommerce_SingleCoupon($resource));
                   break;
                case 'products':
                    mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($resource));
                   break;
                case 'orders':
                    $order = new MailChimp_WooCommerce_Single_Order($resource);
                    $order->set_full_sync(true);
                    mailchimp_handle_or_queue($order);
                   break;
               default:
                    mailchimp_log('sync.error', $this->getResourceType().' is not a valid resource.');
                   break;
           }
        }

        return false;
    }

    /**
     * @return bool|object|stdClass
     */
    public function getResources()
    {
        $current_page = $this->getCurrentPage();
        if ($current_page === 'complete') {
            if (!$this->getData('sync.config.resync', false)) {
                return false;
            }

            $current_page = 1;
            $this->setResourcePagePointer($current_page);
            $this->setData('sync.config.resync', false);
        }

        return $this->api()->paginate($this->getResourceType(), $current_page, $this->items_per_page);
    }

    /**
     * @param null|string $resource
     * @return null
     */
    public function getResourcePagePointer($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        return $this->getData('sync.'.$resource.'.current_page', 1);
    }

    /**
     * @param $page
     * @param null $resource
     * @return MailChimp_WooCommerce_Abstract_Sync
     */
    public function setResourcePagePointer($page, $resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        return $this->setData('sync.'.$resource.'.current_page', $page);
    }

    /**
     * @param null|string $resource
     * @return $this
     */
    protected function resourceComplete($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        $this->setData('sync.'.$resource.'.current_page', 'complete');

        return $this;
    }

    /**
     * @param null $resource
     * @return MailChimp_WooCommerce_Abstract_Sync
     */
    protected function setResourceCompleteTime($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        return $this->setData('sync.'.$resource.'.completed_at', time());
    }

    /**
     * @param null $resource
     * @return bool|DateTime
     */
    protected function getResourceCompleteTime($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        $time = $this->getData('sync.'.$resource.'.completed_at', false);

        if ($time > 0) {
            try {
                $date = new DateTime();
                $date->setTimestamp($time);
                return $date;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     */
    public function getOption($key, $default = null)
    {
        $options = $this->getOptions();
        if (isset($options[$key])) {
            return $options[$key];
        }
        return $default;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOption($key, $value)
    {
        $options = $this->getOptions();
        $options[$key] = $value;
        update_option($this->plugin_name, $options);
        return $this;
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function hasOption($key, $default = false)
    {
        return (bool) $this->getOption($key, $default);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $options = get_option($this->plugin_name);
        return is_array($options) ? $options : array();
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setData($key, $value)
    {
        update_option($this->plugin_name.'-'.$key, $value, 'yes');
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|void
     */
    public function getData($key, $default = null)
    {
        return get_option($this->plugin_name.'-'.$key, $default);
    }

    /**
     * @param $key
     * @return bool
     */
    public function removeData($key)
    {
        return delete_option($this->plugin_name.'-'.$key);
    }

    /**
     * @return MailChimp_WooCommerce_Api
     */
    protected function api()
    {
        if (empty($this->api)) {
            $this->api = new MailChimp_WooCommerce_Api();
        }
        return $this->api;
    }

    /**
     * @return MailChimp_WooCommerce_MailChimpApi
     */
    protected function mailchimp()
    {
        if (empty($this->mc)) {
            $this->mc = new MailChimp_WooCommerce_MailChimpApi($this->getOption('mailchimp_api_key'));
        }
        return $this->mc;
    }

    /**
     * @return bool
     */
    protected function isBeingRateLimited()
    {
        return (bool) mailchimp_get_transient('api-rate-limited', false);
    }
}

<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/14/16
 * Time: 11:54 AM
 */
abstract class MailChimp_WooCommerce_Abtstract_Sync extends WP_Job
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
     * @return mixed
     */
    abstract public function getResourceType();

    /**
     * @param $item
     * @return mixed
     */
    abstract protected function iterate($item);

    /**
     * @return mixed
     */
    abstract protected function complete();

    /**
     * @return mixed
     */
    public function go()
    {
        return $this->handle();
    }

    /**
     * @return string
     */
    public function getStoreID()
    {
        return md5(get_option('siteurl'));
    }

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    public function handle() {

        if (!($page = $this->getResources()) || !($this->store_id = $this->getStoreID())) {
            return false;
        }

        $this->setResourcePagePointer($page->page + 1, $this->getResourceType());

        // if we've got a 0 count, that means we're done.
        if ($page->count <= 0) {

            // reset the resource page back to 1
            $this->resourceComplete($this->getResourceType());

            // call the completed event to process further
            $this->complete();

            return false;
        }

        // iterate through the items and send each one through the pipeline based on this class.
        foreach ($page->items as $resource) {
            $this->iterate($resource);
        }

        // this will paginate through all records for the resource type until they return no records.
        wp_queue(new static());

        return false;
    }

    /**
     * @return $this
     */
    public function flagStartSync()
    {
        mailchimp_log('sync.started', "Starting Sync :: ".date('D, M j, Y g:i A'));

        // this is the last thing we're doing so it's complete as of now.
        $this->setData('sync.syncing', true);
        $this->setData('sync.started_at', time());

        $this->removeData('sync.products.current_page');
        $this->removeData('sync.orders.current_page');

        // flag the store as syncing
        mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), true);

        return $this;
    }

    /**
     * @return $this
     */
    public function flagStopSync()
    {
        mailchimp_log('sync.completed', "Finished Sync :: ".date('D, M j, Y g:i A'));

        // this is the last thing we're doing so it's complete as of now.
        $this->setData('sync.syncing', false);
        $this->setData('sync.completed_at', time());

        // flag the store as sync_finished
        mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), false);

        return $this;
    }

    /**
     * @return bool|object|stdClass
     */
    public function getResources()
    {
        $current_page = $this->getResourcePagePointer($this->getResourceType());

        if ($current_page === 'complete') {
            return false;
        }

        return $this->api()->paginate($this->getResourceType(), $current_page, 10);
    }

    /**
     * @param null|string $resource
     * @return $this
     */
    protected function resetResourcePagePointer($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        $this->setData('sync.'.$resource.'.current_page', 1);

        return $this;
    }

    /**
     * @param null|string $resource
     * @return null
     */
    protected function getResourcePagePointer($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        return $this->getData('sync.'.$resource.'.current_page', 1);
    }

    /**
     * @param $page
     * @param null $resource
     * @return MailChimp_WooCommerce_Abtstract_Sync
     */
    protected function setResourcePagePointer($page, $resource = null)
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
     * @return null
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
            return new \DateTime($time);
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
        update_option($this->plugin_name.'-'.$key, $value);
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
}

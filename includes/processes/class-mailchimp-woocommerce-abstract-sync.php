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
     * @var bool
     */
    protected $has_applied_pagination = false;

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
        return mailchimp_get_store_id();
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
    public function handle()
    {
        if (!mailchimp_is_configured()) {
            mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
            return false;
        }

        if (!($this->store_id = $this->getStoreID())) {
            mailchimp_debug(get_called_class().'@handle', 'store id not loaded');
            return false;
        }

        // if we're being rate limited - we need to pause here.
        if ($this->isBeingRateLimited()) {
            // wait a few seconds
            sleep(3);
            // check this again
            if ($this->isBeingRateLimited()) {
                // ok - hold off for a few - let's re-queue the job.
                mailchimp_debug(get_called_class().'@handle', 'being rate limited - pausing for a few seconds...');
                $this->next();
                return false;
            }
        }

        // don't let recursion happen.
        if ($this->getResourceType() === 'orders' && $this->getResourceCompleteTime()) {
            mailchimp_log('sync.stop', "halting the sync for :: {$this->getResourceType()}");
            return false;
        }

        $page = $this->getResources();

        if (empty($page)) {
            mailchimp_debug(get_called_class().'@handle', 'could not find any more '.$this->getResourceType().' records ending on page '.$this->getResourcePagePointer());
            // call the completed event to process further
            $this->resourceComplete($this->getResourceType());
            $this->complete();

            return false;
        }

        $this->setResourcePagePointer(($page->page + 1), $this->getResourceType());

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
            try {
                $this->iterateCurrentResource($resource);
            } catch (MailChimp_WooCommerce_RateLimitError $e) {
                $this->applyRateLimitedScenario();
                return false;
            }
        }

        $this->next();

        return false;
    }

    /**
     * @param $resource
     * @return bool
     * @throws MailChimp_WooCommerce_RateLimitError
     */
    protected function iterateCurrentResource($resource)
    {
        $attempts = 1;
        while ($attempts <= 4) {
            $attempts++;
            try {
                return $this->iterate($resource);
            } catch (MailChimp_WooCommerce_RateLimitError $e) {
                if ($attempts === 4) {
                    throw $e;
                }
                sleep(3);
            }
        }
    }

    /**
     * @return $this
     */
    public function flagStartSync()
    {
        $job = new MailChimp_Service();

        $job->removeSyncPointers();

        $this->setData('sync.config.resync', false);
        $this->setData('sync.orders.current_page', 1);
        $this->setData('sync.products.current_page', 1);
        $this->setData('sync.coupons.current_page', 1);
        $this->setData('sync.syncing', true);
        $this->setData('sync.started_at', time());

        if (! $this->getData('sync.completed_at')) {
            $this->setData('sync.initial_sync', 1);
        } else $this->removeData('sync.initial_sync');

        global $wpdb;
        try {
            $wpdb->show_errors(false);
            mailchimp_delete_as_jobs();
            $wpdb->show_errors(true);
        } catch (\Exception $e) {}

        mailchimp_log('sync.started', "Starting Sync :: ".date('D, M j, Y g:i A'));

        // flag the store as syncing
        mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), true);

        return $this;
    }

    /**
     * @return $this
     */
    public function flagStopSync()
    {
        // this is the last thing we're doing so it's complete as of now.
        $this->setData('sync.syncing', false);
        $this->setData('sync.completed_at', time());

        // set the current sync pages back to 1 if the user hits resync.
        $this->setData('sync.orders.current_page', 1);
        $this->setData('sync.products.current_page', 1);
        $this->setData('sync.coupons.current_page', 1);

        mailchimp_log('sync.completed', "Finished Sync :: ".date('D, M j, Y g:i A'));

        // flag the store as sync_finished
        mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), false);
        
        mailchimp_update_communication_status();

        return $this;
    }

    /**
     * @return bool|object|stdClass
     */
    public function getResources()
    {
        $current_page = $this->getResourcePagePointer($this->getResourceType());

        if ($current_page === 'complete') {
            if (!$this->getData('sync.config.resync', false)) {
                return false;
            }

            $current_page = 1;
            $this->setResourcePagePointer($current_page);
            $this->setData('sync.config.resync', false);
        }

        return $this->api()->paginate($this->getResourceType(), $current_page, 5);
    }

    /**
     * @param null|string $resource
     * @return $this
     */
    public function resetResourcePagePointer($resource = null)
    {
        if (empty($resource)) $resource = $this->getResourceType();

        $this->setData('sync.'.$resource.'.current_page', 1);

        return $this;
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

        // tell the file that if we catch a rate limit error that we need to revert to the current page.
        $this->has_applied_pagination = $page;

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
                $date = new \DateTime();
                $date->setTimestamp($time);
                return $date;
            } catch (\Exception $e) {
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

    /**
     * @return $this
     */
    protected function applyRateLimitedScenario()
    {
        mailchimp_set_transient('api-rate-limited', true, 60);

        if ($this->has_applied_pagination) {
            $this->setResourcePagePointer(($this->has_applied_pagination-1));
            $this->has_applied_pagination = false;
        }

        $this->next();

        return $this;
    }

    /**
     *
     */
    protected function next()
    {
        // this will paginate through all records for the resource type until they return no records.
        mailchimp_handle_or_queue(new static(), 0);
        mailchimp_debug(get_called_class().'@handle', 'queuing up the next job');
    }
}

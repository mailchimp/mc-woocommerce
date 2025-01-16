<?php

class Mailchimp_Woocommerce_Complete_Resource_Sync extends Mailchimp_Woocommerce_Job
{

    public $resource;

    public $id;

    private $plugin_name = 'mailchimp-woocommerce';

    public function __construct($resource)
    {
        $this->resource = $resource;
        $this->id = $resource;
    }

    public function handle()
    {
        mailchimp_debug('resource.complete_sync', 'Completed resource syncing', ['d'=> $this->resource]);

        return $this->setData('sync.'.$this->resource.'.completed_at', time());
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setData($key, $value)
    {
        \Mailchimp_Woocommerce_DB_Helpers::update_option($this->plugin_name.'-'.$key, $value);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|void
     */
    public function getData($key, $default = null)
    {
        return \Mailchimp_Woocommerce_DB_Helpers::get_option($this->plugin_name.'-'.$key, $default);
    }
}
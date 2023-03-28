<?php


class MailChimp_WooCommerce_Pull_Last_Campaign
{
    protected $email;
    protected $cache_for = 5;
    protected $clicked_url = false;
    protected $click_date = false;
    protected $campaign_id = false;
    protected $strict = true;
    public $member_activity = [];

    /**
     * MailChimp_WooCommerce_Pull_Last_Campaign constructor.
     * @param $email
     * @param int $cache_for
     */
    public function __construct($email, $cache_for = 10)
    {
        $this->email = $email;
        $this->cache_for = $cache_for;
        $this->member_activity = null;
    }

    /**
     * @return string|null
     */
    public function handle()
    {
	    if (!is_email($this->email)) {
		    return null;
	    }

        try {
        	$list_id = mailchimp_get_list_id();
            $md5 = md5(trim(strtolower($this->email)));
            // if we have a cached value ( anything from the last 30 days ) use this because it won.
            if ($this->cache_for > 0 && ($cached = mailchimp_get_transient("last_click.{$md5}"))) {
                return $cached;
            }
            // pull the list member's activity from Mailchimp
            $this->member_activity = mailchimp_get_api()->activity($list_id, $this->email);
            // make sure the response id valid.
            if (is_array($this->member_activity) && array_key_exists('activity',$this->member_activity) && is_array($this->member_activity['activity'])) {
                // loop through each activity item
                foreach ($this->member_activity['activity'] as $item) {
                    // try to find a "click" event
                    if ((isset($item['action']) && $item['action'] === 'click') && isset($item['url'])) {
                    	// get the number of seconds since the button click
	                    $diff = (int) abs( strtotime('now') - (int) $item['timestamp'] );
	                    // and as long as it's less than 30 days ago we use this. ( 86400 seconds in a day )
	                    if ((int) round($diff/86400) < 31) {
		                    // store it in the cache for faster use later.
		                    mailchimp_set_transient("last_click.{$md5}", $item['campaign_id'], ($this->cache_for*60));
		                    // grab the clicked URL, the date, and the campaign ID.
		                    $this->clicked_url = $item['url'];
		                    $this->click_date = new \DateTime($item['timestamp']);
		                    // return the actual campaign ID to attach to the order
		                    return $this->campaign_id = $item['campaign_id'];
	                    }
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return null;
    }

    /**
     * @return bool|\DateTime
     */
    public function getClickDate()
    {
        return $this->click_date;
    }

    /**
     * @return bool|string
     */
    public function getClickedURL()
    {
        return $this->clicked_url;
    }
}

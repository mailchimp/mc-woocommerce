<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 11/14/16
 * Time: 9:38 AM
 */
class MailChimp_WooCommerce_User_Submit extends WP_Job
{
    public $user_id;
    public $subscribed = null;
    public $updated_data = null;

    /**
     * MailChimp_WooCommerce_User_Submit constructor.
     * @param null $user_id
     * @param null $subscribed
     * @param WP_User|null $updated_data
     */
    public function __construct($user_id = null, $subscribed = null, $updated_data = null)
    {
        if (!empty($user_id)) {
            $this->user_id = $user_id;
        }

        if (is_bool($subscribed)) {
            $this->subscribed = $subscribed;
        }

        if (!empty($updated_data)) {
            $this->updated_data = $updated_data->to_array();
        }
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // load up the user.
        $user = new WP_User($this->user_id);

        // we need a valid user, a valid store id and options to continue
        if ($user->ID <= 0 || empty($store_id) || !is_array($options)) {
            return false;
        }

        $api_key = isset($options['mailchimp_api_key']) ? $options['mailchimp_api_key'] : false;
        $list_id = isset($options['mailchimp_list']) ? $options['mailchimp_list'] : false;

        // we need a valid api key and list id to continue
        if (empty($api_key) || empty($list_id)) {
            return false;
        }

        $api = new MailChimp_WooCommerce_MailChimpApi($api_key);

        $merge_vars = array(
            'FNAME' => $user->first_name,
            'LNAME' => $user->last_name,
        );

        try {

            // see if we have a member.
            $api->member($list_id, $user->user_email);

            // if we're updating a member and the email is different, we need to delete the old person
            if (is_array($this->updated_data) && isset($this->updated_data['user_email'])) {

                if ($this->updated_data['user_email'] !== $user->user_email) {

                    // delete the old
                    $api->deleteMember($list_id, $this->updated_data['user_email']);

                    // subscribe the new
                    $api->subscribe($list_id, $user->user_email, $this->subscribed, $merge_vars);

                    mailchimp_log('member.sync', 'Subscriber Swap '.$this->updated_data['user_email'].' to '.$user->user_email, $merge_vars);

                    return false;
                }
            }

            // ok let's update this member
            $api->update($list_id, $user->user_email, $this->subscribed, $merge_vars);
            mailchimp_log('member.sync', 'Updated Member '.$user->user_email, $merge_vars);
        } catch (\Exception $e) {

            // if we have a 404 not found, we can create the member
            if ($e->getCode() == 404) {
                $api->subscribe($list_id, $user->user_email, $this->subscribed, $merge_vars);
                mailchimp_log('member.sync', 'Subscribed Member '.$user->user_email, $merge_vars);
                return false;
            }

            mailchimp_log('member.sync', $e->getMessage());
        }

        return false;
    }
}

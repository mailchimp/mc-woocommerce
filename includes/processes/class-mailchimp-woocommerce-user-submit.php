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
    public static $handling_for = null;

    public $user_id;
    public $subscribed;
    public $updated_data;
    public $should_ignore = false;

    /**
     * MailChimp_WooCommerce_User_Submit constructor.
     * @param null $user_id
     * @param null $subscribed
     * @param WP_User|null $updated_data
     */
    public function __construct($user_id = null, $subscribed = null, $updated_data = null)
    {
        if (!empty($user_id)) {
            // if we're passing in another user with the same id during the same php process we need to ignore it.
            if (static::$handling_for === $user_id) {
                $this->should_ignore = true;
            }
            // set the user id and the current 'handling_for' to this user id so we don't duplicate jobs.
            static::$handling_for = $this->user_id = $user_id;
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
        if (!mailchimp_is_configured()) {
            mailchimp_debug(get_called_class(), 'mailchimp is not configured properly');
            static::$handling_for = null;
            return false;
        }

        if ($this->should_ignore) {
            mailchimp_debug(get_called_class(), "{$this->user_id} is currently in motion - skipping this one.");
            static::$handling_for = null;
            return false;
        }

        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // load up the user.
        $user = new WP_User($this->user_id);

        // we need a valid user, a valid store id and options to continue
        if ($user->ID <= 0 || empty($store_id) || !is_array($options)) {

            // seems as if the database records are not being set by the time this queue job is fired,
            // just a precautionary to make sure it's available during
            sleep(1);

            $options = get_option('mailchimp-woocommerce', array());
            $store_id = mailchimp_get_store_id();

            // load up the user.
            $user = new WP_User($this->user_id);

            if ($user->ID <= 0 || empty($store_id) || !is_array($options)) {
                mailchimp_log('member.sync', "Invalid Data For Submission :: {$user->ID}");
                static::$handling_for = null;
                return false;
            }
        }

        $email = $user->user_email;

        // make sure we don't need to skip this email
        if (!mailchimp_email_is_allowed($email)) {
            static::$handling_for = null;
            return false;
        }

        // if we have a null value, we need to grab the correct user meta for is_subscribed
        if (is_null($this->subscribed)) {
            $user_subscribed = get_user_meta($this->user_id, 'mailchimp_woocommerce_is_subscribed', true);
            if ($user_subscribed === '' || $user_subscribed === null) {
                mailchimp_log('member.sync', "Skipping sync for {$email} because no subscriber status has been set");
                static::$handling_for = null;
                return false;
            }
            $this->subscribed = (bool) $user_subscribed;
        }

        $api_key = isset($options['mailchimp_api_key']) ? $options['mailchimp_api_key'] : false;
        $list_id = isset($options['mailchimp_list']) ? $options['mailchimp_list'] : false;

        // we need a valid api key and list id to continue
        if (empty($api_key) || empty($list_id)) {
            mailchimp_log('member.sync', "Invalid Api Key or ListID :: {$email}");
            static::$handling_for = null;
            return false;
        }

        // don't let anyone be unsubscribed from the list - that should only happen on email campaigns
        // and someone clicking the unsubscribe linkage.
        if (!$this->subscribed) {
            static::$handling_for = null;
            return false;
        }

        $api = new MailChimp_WooCommerce_MailChimpApi($api_key);

        $merge_vars_system = array();

        $fn = trim($user->first_name);
        $ln = trim($user->last_name);

        if (!empty($fn)) $merge_vars_system['FNAME'] = $fn;
        if (!empty($ln)) $merge_vars_system['LNAME'] = $ln;

        // allow users to hook into the merge tag submission
        $merge_vars = apply_filters('mailchimp_sync_user_mergetags', $user, $merge_vars_system);

        // for whatever reason if this isn't an array we need to skip it.
        if (!is_array($merge_vars)) {
            mailchimp_error("custom.merge_tags", "the filter for mailchimp_sync_user_mergetags needs to return an array, we're using the default setup instead.");
            $merge_vars = $merge_vars_system;
        }

        // pull the transient key for this job.
        $transient_id = mailchimp_get_transient_email_key($email);
        $status_meta = mailchimp_get_subscriber_status_options($this->subscribed);

        try {

            // check to see if the status meta has changed when a false response is given
            if (mailchimp_check_serialized_transient_changed($transient_id, $status_meta) === false) {
                mailchimp_debug(get_called_class(), "Skipping sync for {$email} because it was just pushed less than a minute ago.");
                static::$handling_for = null;
                return false;
            }

            // see if we have a member.
            $member_data = $api->member($list_id, $email);

            // if we're updating a member and the email is different, we need to delete the old person
            if (is_array($this->updated_data) && isset($this->updated_data['user_email'])) {
                if ($this->updated_data['user_email'] !== $email) {
                    // delete the old
                    $api->deleteMember($list_id, $this->updated_data['user_email']);
                    // subscribe the new
                    $api->subscribe($list_id, $email, $status_meta['created'], $merge_vars);
                    mailchimp_tell_system_about_user_submit($email, $status_meta, 60);

                    if ($status_meta['created']) {
                        mailchimp_log('member.sync', 'Subscriber Swap '.$this->updated_data['user_email'].' to '.$email, array(
                            'status' => $status_meta['created'],
                            'merge_vars' => $merge_vars
                        ));
                    } else {
                        mailchimp_log('member.sync', 'Subscriber Swap '.$this->updated_data['user_email'].' to '.$email.' Pending Double OptIn', array(
                            'status' => $status_meta['created'],
                            'merge_vars' => $merge_vars
                        ));
                    }
                    static::$handling_for = null;
                    return false;
                }
            }

            // if the member is unsubscribed or pending, we really can't do anything here.
            if (isset($member_data['status']) && in_array($member_data['status'], array('unsubscribed', 'pending'))) {
                mailchimp_log('member.sync', "Skipped Member Sync For {$email} because the current status is {$member_data['status']}", $merge_vars);
                static::$handling_for = null;
                return false;
            }

            // if the status is not === 'transactional' we can update them to subscribed or pending now.
            if (isset($member_data['status']) && $member_data['status'] === 'transactional' || $member_data['status'] === 'cleaned') {
                // ok let's update this member
                $api->update($list_id, $email, $status_meta['updated'], $merge_vars);
                mailchimp_tell_system_about_user_submit($email, $status_meta, 60);
                mailchimp_log('member.sync', "Updated Member {$email}", array(
                    'previous_status' => $member_data['status'],
                    'status' => $status_meta['updated'],
                    'merge_vars' => $merge_vars
                ));
                static::$handling_for = null;
                return true;
            }

            if (isset($member_data['status'])) {
                // ok let's update this member
                $api->update($list_id, $email, $member_data['status'], $merge_vars);
                mailchimp_tell_system_about_user_submit($email, $status_meta, 60);
                mailchimp_log('member.sync', "Updated Member {$email} ( merge tags only )", array(
                    'merge_vars' => $merge_vars
                ));
                static::$handling_for = null;
                return true;
            }

            static::$handling_for = null;
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            $this->release();
            mailchimp_error('member.sync.error', mailchimp_error_trace($e, "RateLimited :: user #{$this->user_id}"));
        } catch (\Exception $e) {
            // if we have a 404 not found, we can create the member
            if ($e->getCode() == 404) {

                try {
                    $api->subscribe($list_id, $user->user_email, $status_meta['created'], $merge_vars);
                    mailchimp_tell_system_about_user_submit($email, $status_meta, 60);
                    if ($status_meta['created']) {
                        mailchimp_log('member.sync', "Subscribed Member {$user->user_email}", array('status_if_new' => $status_meta['created'], 'merge_vars' => $merge_vars));
                    } else {
                        mailchimp_log('member.sync', "{$user->user_email} is Pending Double OptIn");
                    }
                } catch (\Exception $e) {
                    mailchimp_log('member.sync', $e->getMessage());
                }
                static::$handling_for = null;
                return false;
            }
            mailchimp_error('member.sync', mailchimp_error_trace($e, $user->user_email));
        }

        static::$handling_for = null;

        return false;
    }
}

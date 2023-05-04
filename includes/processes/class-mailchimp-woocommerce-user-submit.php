<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 11/14/16
 * Time: 9:38 AM
 */
class MailChimp_WooCommerce_User_Submit extends Mailchimp_Woocommerce_Job
{
    public static $handling_for = null;

    public $id;
    public $subscribed;
    public $gdpr_fields;
    public $updated_data;
    public $language;
    public $should_ignore = false;
    public $submit_transactional = true;

	/**
	 * MailChimp_WooCommerce_User_Submit constructor.
	 *
	 * @param null $id
	 * @param null $subscribed
	 * @param null $updated_data
	 * @param null $language
	 * @param null $gdpr_fields
	 */
    public function __construct($id = null, $subscribed = null, $updated_data = null, $language = null, $gdpr_fields = null)
    {

        if (!empty($id)) {
            // if we're passing in another user with the same id during the same php process we need to ignore it.
            if (static::$handling_for === $id) {
                $this->should_ignore = true;
            }
            // set the user id and the current 'handling_for' to this user id so we don't duplicate jobs.
            static::$handling_for = $this->id = $id;
        }

        if ( !is_null($subscribed) ) {
            $this->subscribed = $subscribed;

            if ( is_string($subscribed) && !empty($gdpr_fields)) {
                foreach ($gdpr_fields as $id => $value) {
                    $gdpr_field['marketing_permission_id'] = $id;
                    $gdpr_field['enabled'] = (bool) $value;
                    $this->gdpr_fields[] = $gdpr_field;
                }
            }
        }



        if (!empty($updated_data)) {
            $this->updated_data = $updated_data->to_array();
        }

        if (!empty($language)) {
            $this->language = $language;
        }

        mailchimp_debug('member.sync', "construct this -> subscribed " . $this->subscribed);

    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function submittingTransactional($bool = true)
    {
        $this->submit_transactional = (bool) $bool;
        return $this;
    }

	/**
	 * @return bool
	 */
    public function handle()
    {

        mailchimp_debug('member.sync', "first this -> subscribed " . $this->subscribed);

        if (!mailchimp_is_configured()) {
            mailchimp_debug(get_called_class(), 'Mailchimp is not configured properly');
            static::$handling_for = null;
            return false;
        }

        if ($this->should_ignore) {
            mailchimp_debug(get_called_class(), "{$this->id} is currently in motion - skipping this one.");
            static::$handling_for = null;
            return false;
        }

        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // load up the user.
        $user = new WP_User($this->id);

        // we need a valid user, a valid store id and options to continue
        if ($user->ID <= 0 || empty($store_id) || !is_array($options)) {

            // seems as if the database records are not being set by the time this queue job is fired,
            // just a precautionary to make sure it's available during
            sleep(1);

            $options = get_option('mailchimp-woocommerce', array());
            $store_id = mailchimp_get_store_id();

            // load up the user.
            $user = new WP_User($this->id);

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

        $user_subscribed = get_user_meta($this->id, 'mailchimp_woocommerce_is_subscribed', true);
        $unsaved = '' === $user_subscribed || null === $user_subscribed;

        // if we have a null value, we need to grab the correct user meta for is_subscribed
        if (is_null($this->subscribed)) {
            if ( $unsaved ) {
                mailchimp_log('member.sync', "Skipping sync for {$email} because no subscriber status has been set");
                static::$handling_for = null;
                return false;
            }
            $this->subscribed = $user_subscribed;
        }

        // if the meta we've stored on the user is not equal to the value being passed to Mailchimp
	    // let's update that value here.

        if ( $unsaved || ( $this->subscribed !== '' && $user_subscribed !== $this->subscribed ) ) {
            update_user_meta(
        		$this->id,
		        'mailchimp_woocommerce_is_subscribed',
		        $this->subscribed
	        );
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
        if ($this->subscribed === '0' && !$this->submit_transactional) {
            static::$handling_for = null;
            return false;
        }

        $api = new MailChimp_WooCommerce_MailChimpApi($api_key);

        $merge_fields_system = array();

        $fn = trim($user->first_name);
        $ln = trim($user->last_name);

        if (!empty($fn)) $merge_fields_system['FNAME'] = $fn;
        if (!empty($ln)) $merge_fields_system['LNAME'] = $ln;

        // allow users to hook into the merge field submission
        $merge_fields = apply_filters('mailchimp_sync_user_mergetags', $merge_fields_system, $user);

        // for whatever reason if this isn't an array we need to skip it.
        if (!is_array($merge_fields)) {
            mailchimp_error("custom.merge_fields", "The filter for mailchimp_sync_user_mergetags needs to return an array, using the default setup instead.");
            $merge_fields = $merge_fields_system;
        }
        // language
        $language = $this->language;

        // GDPR
        $gdpr_fields = $this->gdpr_fields;

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
                    $api->subscribe($list_id, $email, $status_meta['created'], $merge_fields, null, $language, $gdpr_fields);

                    // update the member tags but fail silently just in case.
                    $api->updateMemberTags(mailchimp_get_list_id(), $email, true);

                    mailchimp_tell_system_about_user_submit($email, $status_meta);

                    if ($status_meta['created']) {
                        mailchimp_log('member.sync', 'Subscriber Swap '.$this->updated_data['user_email'].' to '.$email, array(
                            'status' => $status_meta['created'],
                            'merge_fields' => $merge_fields
                        ));
                    } else {
                        mailchimp_log('member.sync', 'Subscriber Swap '.$this->updated_data['user_email'].' to '.$email.' Pending Double OptIn', array(
                            'status' => $status_meta['created'],
                            'merge_fields' => $merge_fields
                        ));
                    }
                    static::$handling_for = null;
                    return false;
                }
            }

            // if the member is unsubscribed or pending, we really can't do anything here.
            if (isset($member_data['status']) && in_array($member_data['status'], array('unsubscribed', 'pending'))) {
                if ( ( $this->subscribed === '1' || $this->subscribed === '0' )  && $member_data['status'] !== 'pending') {
                    mailchimp_log('member.sync', "pushing {$email} status as pending because they were previously unsubscribed, and must use the double opt in to make it back on the list.");
                    $member_data['status'] = 'pending';
                } else {
                    mailchimp_log('member.sync', "Skipped Member Sync For {$email} because the current status is {$member_data['status']}", $merge_fields);
                    static::$handling_for = null;
                    return false;
                }
            }

            // if the status is not === 'transactional' we can update them to subscribed or pending now.
            if (isset($member_data['status']) && $member_data['status'] === 'transactional' || $member_data['status'] === 'cleaned') {
                // ok let's update this member
                $api->update($list_id, $email, $status_meta['updated'], $merge_fields, null, $language, $gdpr_fields);

                // update the member tags but fail silently just in case.
                $api->updateMemberTags(mailchimp_get_list_id(), $email, true);

                mailchimp_tell_system_about_user_submit($email, $status_meta);
                mailchimp_log('member.sync', "Updated Member {$email}", array(
                    'previous_status' => $member_data['status'],
                    'status' => $status_meta['updated'],
                    'language' => $language,
                    'merge_fields' => $merge_fields,
                    'gdpr_fields' => $gdpr_fields,
                ));
                static::$handling_for = null;
                return true;
            }

            if (isset($member_data['status'])) {
                if ( ($member_data['status'] === 'subscribed' || $member_data['status'] === 'unsubscribed') && $this->subscribed === '0') {
                    $member_data['status'] = 'transactional';
                } else if ( ($member_data['status'] === 'subscribed' || $member_data['status'] === 'transactional') && $this->subscribed === 'unsubscribed' ) {
                    $member_data['status'] = 'unsubscribed';
                }

                // ok let's update this member
                $api->update($list_id, $email, $member_data['status'], $merge_fields, null, $language, $gdpr_fields);

                // delete this admin transient if there was one
                mailchimp_delete_transient("updating_subscriber_status.{$this->id}" );

                // update the member tags but fail silently just in case.
                $api->updateMemberTags(mailchimp_get_list_id(), $email, true);

                mailchimp_tell_system_about_user_submit($email, $status_meta);
                mailchimp_log('member.sync', "Updated Member {$email}", array(
                    'status' => $member_data['status'],
                    'language' => $language,
                    'merge_fields' => $merge_fields,
                    'gdpr_fields' => $gdpr_fields,
                ));
                static::$handling_for = null;
                return true;
            }

            static::$handling_for = null;
        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            mailchimp_error('member.sync.error', mailchimp_error_trace($e, "RateLimited :: user #{$this->id}"));
            $this->retry();
        } catch (Exception $e) {

        	$compliance_state = mailchimp_string_contains($e->getMessage(), 'compliance state');

        	if ($compliance_state) {
        		return $this->handleComplianceState($email, $merge_fields);
	        }

            // if we have a 404 not found, we can create the member
            if ($e->getCode() == 404) {

                try {
                    $uses_doi = isset($status_meta['requires_double_optin']) && $status_meta['requires_double_optin'];
                    $status_if_new = $uses_doi && (bool) $this->subscribed ? 'pending' : $this->subscribed;

                    $api->subscribe($list_id, $user->user_email, $status_if_new, $merge_fields, null, $language, $gdpr_fields);

	                // delete this admin transient if there was one
	                mailchimp_delete_transient("updating_subscriber_status.{$this->id}" );

                    // update the member tags but fail silently just in case.
                    $api->updateMemberTags(mailchimp_get_list_id(), $email, true);

                    mailchimp_tell_system_about_user_submit($email, $status_meta);
                    if ($status_meta['created']) {
                        mailchimp_log('member.sync', "Subscribed Member {$user->user_email}", array('status_if_new' => $status_if_new, 'merge_fields' => $merge_fields));
                    } else {
                        mailchimp_log('member.sync', "{$user->user_email} is Pending Double OptIn");
                    }
                } catch (Exception $e) {
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

	/**
	 * @param $email
	 * @param array $fields
	 * @param array $interests
	 * @throws \Throwable
	 */
	protected function handleComplianceState($email, $fields = [], $interests = [])
	{
		mailchimp_log('subscriber_sync', "member {$email} is in compliance state, sending double opt in.");
		return mailchimp_get_api()->updateOrCreate(mailchimp_get_list_id(), $email, 'pending', $fields, $interests);
	}
}

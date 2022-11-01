<?php

/**
 * Class MailChimp_WooCommerce_Subscriber_Sync
 */
class MailChimp_WooCommerce_Subscriber_Sync extends Mailchimp_Woocommerce_Job
{
    public $data = array();

    /**
     * SubscriberSync constructor.
     * Pass in the raw data from the webhook from Mailchimp
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = (array) $data;
    }

    /**
     * @return bool|null
     */
    public function handle()
    {
    	mailchimp_debug('subscriber_sync', "tracing mailchimp post", array('data' => $this->data));

        try {
            // if the store is not properly connected to Mailchimp - we need to skip this.
            if (!mailchimp_is_configured()) {
                mailchimp_log('subscriber_sync', 'Mailchimp is not configured, can not process job.');
                return null;
            }
            // grab the hook type, and the new data
            list($hook_type, $data, $failed) = $this->parseInputData();
            // extract the service ids from the data we get
            list ($service_id, $email) = $this->extractServiceIDs($data);

            /// when subscriber sync hooks come in, force refresh the admin view
            $list_id = mailchimp_get_list_id();
	        $email_hash = md5( strtolower( trim( $email ) ) );
	        delete_site_transient( "mailchimp-woocommerce-subscribed.{$list_id}.{$email_hash}" );

            // ignore the empty submissions or certain events or emails
            if ($this->hasInvalidEvent($hook_type, $failed, $data)) {
                mailchimp_log('subscriber_sync', 'Webhook has invalid event', compact('email'));
                return false;
            }
            if ($this->shouldIgnoreEmail($email)) {
                mailchimp_log('subscriber_sync', 'Webhook is ignoring email', compact('email'));
                return false;
            }
            // if hook type is 'subscribe' that means we need ot subscribe them
            $subscribe = $hook_type === 'subscribe';
            // if we don't have a user by email
            if (!($user = get_user_by('email', $email))) {
                // if the user is not found and we should create new customers
                return ($subscribe && $this->shouldCreateNewCustomers()) ?
                    (bool) $this->createNewCustomer($email) :
                    false;
            }
            try {
                $handled_key = "subscriber_sync.{$service_id}.handled";
                // see if we've saved a service call in the last 30 minutes.
                $handled = mailchimp_get_transient($handled_key);
                // if we've got the subscriber sync id and it's the same as the previous submission, just skip out now.
                if ($handled === $subscribe) {
                    mailchimp_log('subscriber_sync', "didn't need to do anything");
                    return true;
                }
                // if they unsubscribed, we need to put a cache on this because it's causing issues in the
                // shopify webhooks for some reason being re-subscribed.
                if (!$subscribe) {
                    // update the cached status just in case this is causing trouble with the webhook.
                    $hashed = md5(trim(strtolower($email)));
                    // tell the webhooks that we've just synced this customer with a certain status.
                    mailchimp_set_transient("{$hashed}.subscriber_sync", array('time' => time(), 'status' => false), 90);
                }
                // update the user meta to show the proper value.
                update_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', $subscribe);
                // cache it for 90 seconds to be used above.
                mailchimp_set_transient($handled_key, $subscribe, 90);
                mailchimp_log('webhook', "Subscriber Sync :: {$hook_type} :: {$email}", array(
                    'subscribed' => $subscribe,
                    'user_id' => $user->ID,
                ));
                return true;
            } catch (Exception $e) {
                $error = $e->getMessage();
                mailchimp_error('webhook', "Updating Subscriber Status :: MC service ID {$service_id} :: {$hook_type} :: {$error}");
                return false;
            }
        } catch (Throwable $e) {
            mailchimp_error('webhook', $e->getMessage(), array(
            	'data' => isset($data) && $data ? json_encode($data) : null
            ));
        }
        return false;
    }

    /**
     * @param $data
     * @return array
     */
    private function extractServiceIDs($data)
    {
        if (is_object($data)) {
            $service_id = isset($data->web_id) ?
                $data->web_id :
                (isset($data->id) ? $data->id : null);
            $email = isset($data->email) ? $data->email : null;
            return array($service_id, $email);
        } else {
            $service_id = isset($data['web_id']) ? $data['web_id'] : false;
            if (!$service_id) {
                $service_id = isset($data['id']) ? $data['id'] : false;
            }
            $email = isset($data['email']) ? $data['email'] : false;
            return array($service_id, $email);
        }
    }

    /**
     * @return array
     */
    private function parseInputData()
    {
        $hook_type = isset($this->data['type']) ? $this->data['type'] : 'certainly_not';
        $data = isset($this->data['data']) ? $this->data['data'] : [];
        $failed = false;
        $allowed_hooks = array('subscribe' => true, 'unsubscribe' => true,);
        if (!is_string($hook_type) || !isset($allowed_hooks[$hook_type])) {
            $failed = true;
        }
        return array($hook_type, $data, $failed);
    }

	/**
	 * @param $email
	 *
	 * @return int|WP_Error
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    private function createNewCustomer($email)
    {
        $member = mailchimp_get_api()->member(mailchimp_get_list_id(), $email);
        $first_name = !empty($member['merge_fields']['FNAME']) ? $member['merge_fields']['FNAME'] : 'Guest';
        $last_name = !empty($member['merge_fields']['LNAME']) ? $member['merge_fields']['LNAME'] : 'Customer';
        if (empty($first_name)) $first_name = null;
        if (empty($last_name)) $last_name = null;
        // TODO maybe use the registration method and keep a record for when the user is verified later
        $user = wp_create_user(strtolower($email), wp_generate_password(), strtolower($email));
        // subscribe them because this function only runs for subscribers.
        update_user_meta($user, 'mailchimp_woocommerce_is_subscribed', true);
        // if we have a first and last name from the MC account, just use that.
        if ($first_name && $last_name) {
            wp_update_user(array(
                'ID' => $user,
                'first_name' => $first_name,
                'last_name' => $last_name
            ));
        }
        mailchimp_log('webhook', "CREATED CUSTOMER :: {$email} :: {$first_name} {$last_name}");
        return $user;
    }

    /**
     * @return false
     */
    private function shouldCreateNewCustomers()
    {
        // maybe we add a setting for this in the UI and use this here.
        return false;
    }

    /**
     * @param $email
     * @return bool
     */
    private function shouldIgnoreEmail($email)
    {
        return mailchimp_string_contains($email, array(
            'forgotten.mailchimp.com'
        ));
    }

    /**
     * @param $hook_type
     * @param $failed
     * @param $data
     * @return bool
     */
    private function hasInvalidEvent($hook_type, $failed, $data)
    {
        if (empty($hook_type) || empty($data)) {
            return true;
        }
        // if the flag is failed, or deleted, don't do anything.
        if ($failed || $hook_type === 'deleted' || $hook_type === 'delete') {
            return true;
        }
        return false;
    }
}

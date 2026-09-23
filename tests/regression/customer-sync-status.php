<?php
/** Run with: php tests/regression/customer-sync-status.php */
// "Sync as non-subscribed" says what a contact should START as. The initial sync must send it
// as status_if_new so Mailchimp only applies it when it creates the contact - sending
// status: transactional would knock existing subscribers back down to transactional.
error_reporting(E_ALL & ~E_DEPRECATED);

abstract class Mailchimp_Woocommerce_Job {
    public function retry() { $GLOBALS['retried'] = true; }
}
class MailChimp_WooCommerce_RateLimitError extends Exception {}
class MailChimp_WooCommerce_Error extends Exception {}
class MailChimp_WooCommerce_Address {
    public function setCity($v) {} public function setCountry($v) {}
    public function setProvince($v) {} public function setPostalCode($v) {}
}
class MailChimp_WooCommerce_Customer {
    public $opt_in_status;
    private $address;
    public function __construct() { $this->address = new MailChimp_WooCommerce_Address(); }
    public function setOptInStatus($status) { $this->opt_in_status = $status; return $this; }
    public function fromArray($data) { $this->data = $data; return $this; }
    public function getAddress() { return $this->address; }
    public function getId() { return 'customer-id'; }
    public function setSmsOptInStatus($v) {} public function setPhoneNumber($v) {}
}
class Mailchimp_Woocommerce_DB_Helpers {
    public static function get_option($key, $default = null) {
        return array('mailchimp_auto_subscribe' => $GLOBALS['auto_subscribe_setting']);
    }
}

// the member Mailchimp already has, and what the API was asked to do
class FakeApi {
    public $calls = array();
    public function setIsSyncing($bool) { return $this; }
    public function member($list_id, $email) { return array('status' => $GLOBALS['existing_status']); }
    public function update($list_id, $email, $subscribed = '1', $merge_fields = array(), $interests = array(), $language = null, $gdpr = null, $only_if_new = false) {
        $this->calls[] = array('status' => $subscribed, 'only_if_new' => $only_if_new);
        // Mailchimp answers with the member's real status - status_if_new leaves it alone
        return array('status' => $only_if_new ? $GLOBALS['existing_status'] : ($subscribed === 'transactional' ? 'transactional' : 'subscribed'));
    }
    public function updateMemberTags($list_id, $email, $silent = false) {}
    public function updateCustomer($store_id, $customer) { return true; }
    public function subscribe(...$args) { $this->calls[] = array('subscribe' => $args[2]); return array('status' => $args[2]); }
}

function mailchimp_is_configured() { return true; }
function mailchimp_get_api() { return $GLOBALS['api']; }
function mailchimp_email_is_allowed($email) { return true; }
function mailchimp_get_store_id() { return 'store'; }
function mailchimp_get_list_id() { return 'list'; }
function mailchimp_get_subscriber_status_options($subscribed) {
    return array('requires_double_optin' => false, 'created' => $subscribed, 'updated' => $subscribed);
}
function mailchimp_tell_system_about_user_submit(...$args) {}
function mailchimp_register_synced_resource($type) {}
function mailchimp_log(...$args) {}
function mailchimp_debug(...$args) {}
function mailchimp_error(...$args) {}
function mailchimp_string_contains($haystack, $needle) { return strpos($haystack, $needle) !== false; }
function get_user_by($field, $value) { return false; }
function get_user_meta($id, $key, $single = false) { return ''; }
function update_user_meta($id, $key, $value) { $GLOBALS['user_meta'][$key] = $value; }
function get_locale() { return 'en_US'; }
function apply_filters($tag, $value, ...$args) { return $value; }

require dirname(__DIR__, 2) . '/includes/processes/class-mailchimp-woocommerce-single-customer.php';

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

function sync($setting, $existing_status) {
    $GLOBALS['auto_subscribe_setting'] = $setting;
    $GLOBALS['existing_status'] = $existing_status;
    $GLOBALS['api'] = new FakeApi();
    $GLOBALS['user_meta'] = array();
    $lookup = (object) array(
        'customer_id' => 1, 'user_id' => 9, 'email' => 'shopper@example.com',
        'first_name' => 'A', 'last_name' => 'B', 'city' => '', 'country' => '',
        'state' => '', 'postcode' => '',
    );
    $job = new MailChimp_Woocommerce_Single_Customer($lookup);
    $job->set_full_sync(true);
    $job->process();
    return $GLOBALS['api']->calls[count($GLOBALS['api']->calls) - 1];
}

// "sync as non-subscribed" (0): status_if_new only, so a subscriber stays a subscriber
$call = sync('0', 'subscribed');
check($call['only_if_new'] === true, 'non-subscribed setting must send status_if_new');
check($call['status'] === 'transactional', 'new contacts still start as transactional');
check($GLOBALS['user_meta']['mailchimp_woocommerce_is_subscribed'] === '1', 'an existing subscriber keeps their status locally too');

// the same setting on someone Mailchimp does not have yet
$call = sync('0', 'transactional');
check($call['only_if_new'] === true && $call['status'] === 'transactional', 'brand new contacts are created transactional');

// auto-subscribe (1) is a deliberate status change, so it keeps sending status
$call = sync('1', 'transactional');
check($call['only_if_new'] === false && $call['status'] === 'subscribed', 'auto subscribe still sets the status');

// existing-members-only (2) mirrors whatever Mailchimp already has
$call = sync('2', 'subscribed');
check($call['only_if_new'] === false && $call['status'] === '1', 'existing-only mirrors the member status');

/* ---- the API payload itself: only_if_new swaps status for status_if_new ---- */

define('DISABLE_MAILCHIMP_NAUGHTY_LIST', true);
require dirname(__DIR__, 2) . '/includes/api/class-mailchimp-api.php';

class PayloadApi extends MailChimp_WooCommerce_MailChimpApi {
    public $sent;
    protected function put($url, $body) { $this->sent = array('method' => 'PUT', 'url' => $url, 'body' => $body); return array('status' => 'subscribed'); }
    protected function patch($url, $body) { $this->sent = array('method' => 'PATCH', 'url' => $url, 'body' => $body); return array('status' => 'subscribed'); }
}

$api = new PayloadApi('key-us2');
$api->update('list', 'shopper@example.com', 'transactional', array(), array(), null, null, true);
check(!isset($api->sent['body']['status']), 'only_if_new must not send status');
check($api->sent['body']['status_if_new'] === 'transactional', 'only_if_new sends status_if_new');
check($api->sent['method'] === 'PUT', 'status_if_new is a PUT thing - PATCH ignores it');

$api = new PayloadApi('key-us2');
$api->update('list', 'shopper@example.com', 'transactional');
check($api->sent['body']['status'] === 'transactional', 'the normal path still sends status');
check(!isset($api->sent['body']['status_if_new']), 'the normal path sends no status_if_new');

echo "Customer sync status regression checks passed.\n";

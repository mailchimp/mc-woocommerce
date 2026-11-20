<?php
/** Run with php tests/regression/full-sync-member-status.php. No network or WordPress. (GitHub issue #1475) */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL & ~E_DEPRECATED);
$checks = 0;
function check($condition, $message) { global $checks; ++$checks; if (!$condition) { throw new RuntimeException($message); } }

$GLOBALS['errors'] = array();
$GLOBALS['transients'] = array();
function mailchimp_debug() {}
function mailchimp_log() {}
function mailchimp_error($action, $message) { $GLOBALS['errors'][] = array($action, $message); }
function mailchimp_get_transient($key) { return isset($GLOBALS['transients'][$key]) ? $GLOBALS['transients'][$key] : false; }
function mailchimp_set_transient($key, $value) { $GLOBALS['transients'][$key] = $value; }
function mailchimp_get_list_id() { return 'audience-a'; }
function mailchimp_list_has_double_optin() { return false; }
function mailchimp_get_subscriber_status($email) { return $GLOBALS['cart_status']; }
function mailchimp_string_contains($haystack, $needle) { return strpos((string) $haystack, $needle) !== false; }
function apply_filters($hook, $value) { return $value; }
function get_user_by() { return false; }
function mailchimp_get_api() { return $GLOBALS['api']; }

$root = dirname(__DIR__, 2);
require $root . '/includes/api/errors/class-mailchimp-error.php';
require $root . '/includes/api/errors/class-mailchimp-rate-limit-error.php';
require $root . '/includes/api/errors/class-mailchimp-server-error.php';
require $root . '/includes/api/class-mailchimp-api.php';

// The real update() builds the payload; only the HTTP calls are replaced.
class MemberStatusFixtureApi extends MailChimp_WooCommerce_MailChimpApi {
    public $sent = array();
    public function __construct() {}
    public function validateNaughtyListEmail($email) { return true; }
    protected function put($url, $body) { $this->sent[] = array('put', $body); return array('status' => 'unsubscribed'); }
    protected function patch($url, $body) { $this->sent[] = array('patch', $body); return array('status' => 'unsubscribed'); }
    protected function post($url, $body) { $this->sent[] = array('post', $body); return array('status' => 'transactional'); }
}

$bootstrap = file_get_contents($root . '/bootstrap.php');
$start = strpos($bootstrap, 'function mailchimp_member_data_update(');
check($start !== false, 'Member update helper exists');
eval(substr($bootstrap, $start, strpos($bootstrap, "\n}\n", $start) + 2 - $start));

$send = function () {
    $GLOBALS['api'] = new MemberStatusFixtureApi();
    $GLOBALS['errors'] = array();
    $GLOBALS['transients'] = array();
    call_user_func_array('mailchimp_member_data_update', func_get_args());
    check(count($GLOBALS['api']->sent) === 1, 'Exactly one member request is sent');
    check(!$GLOBALS['errors'], 'No member sync error is logged');
    return $GLOBALS['api']->sent[0][1];
};
$GLOBALS['cart_status'] = null;

// The initial sync follow-up: a status name, and only for a contact Mailchimp has to create.
foreach (array('subscribed', 'transactional') as $status) {
    $body = $send('a@example.test', 'en', 'order', $status, null, null, false, true);
    check(!array_key_exists('status', $body), "Initial sync ({$status}) never sends a status for an existing contact");
    check($body['status_if_new'] === $status, "Initial sync sends {$status} as status_if_new");
}

// A boolean from any caller can no longer reach Mailchimp, and can no longer change an existing contact.
foreach (array(array(true, 'subscribed'), array(false, 'transactional'), array(null, 'transactional'), array(1, 'transactional')) as $case) {
    $body = $send('a@example.test', 'en', 'order', $case[0], null, null, false);
    check(!array_key_exists('status', $body), 'A non-string status is never sent as status');
    check($body['status_if_new'] === $case[1], 'A non-string status becomes a status name, as status_if_new only');
}

// Live orders are unchanged: they still set the status the customer chose at checkout.
$body = $send('a@example.test', 'en', 'order', 'subscribed', null, null, true);
check($body['status'] === 'subscribed' && !array_key_exists('status_if_new', $body), 'Live order still sends status');
$body = $send('a@example.test', 'en', 'order', 'transactional', null, null, true);
check($body['status'] === 'transactional' && !array_key_exists('status_if_new', $body), 'Live transactional order still sends status');

// Carts are unchanged: status_if_new only.
$body = $send('a@example.test', 'en', 'cart', 'transactional');
check($body['status_if_new'] === 'transactional' && !array_key_exists('status', $body), 'Cart still sends status_if_new only');

// The order job passes a status name and asks for status_if_new on the initial sync.
$job = file_get_contents($root . '/includes/processes/class-mailchimp-woocommerce-single-order.php');
$block = substr($job, strpos($job, 'if ($new_order && $this->is_full_sync) {'));
$block = substr($block, 0, strpos($block, 'return $api_response;'));
check(strpos($block, "\$status_if_new = \$wants_subscribed ? 'subscribed' : 'transactional';") !== false, 'Initial sync builds a status name');
check(strpos($block, "mailchimp_member_data_update(\$email, \$this->user_language, 'order', \$status_if_new, \$order, \$this->gdpr_fields, false, true);") !== false, 'Initial sync asks for status_if_new only');
check(preg_match('/\$status_if_new\s*=[^;?]*\|\|[^;?]*;/', $job) === 0, 'No status is built from a bare boolean expression');

echo "full-sync-member-status: ok ({$checks} checks)\n";

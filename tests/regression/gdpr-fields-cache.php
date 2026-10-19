<?php
/** Run with php tests/regression/gdpr-fields-cache.php. No network or WordPress. */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL & ~E_DEPRECATED);
$checks = 0;
function check($condition, $message) { global $checks; ++$checks; if (!$condition) { throw new RuntimeException($message); } }
$GLOBALS['transients'] = $GLOBALS['options'] = $GLOBALS['queue'] = array();
$GLOBALS['list'] = 'audience-a';
$GLOBALS['configured'] = true;
$GLOBALS['minutes'] = 5;
function get_transient($key) { return $GLOBALS['transients'][$key][0] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = array($value, $ttl); }
function delete_transient($key) { unset($GLOBALS['transients'][$key]); }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload) { check($autoload === false, 'Durable definitions must not autoload'); $GLOBALS['options'][$key] = $value; }
function apply_filters($hook, $value) { return $GLOBALS['minutes']; }
if (!getenv('MC_GDPR_TEST_WP_CRON')) {
    function as_has_scheduled_action($hook, $args, $group) { return isset($GLOBALS['queue'][serialize($args)]); }
    function as_enqueue_async_action($hook, $args, $group, $unique) { if (!empty($GLOBALS['schedule_error'])) { throw new RuntimeException('Scheduler unavailable'); } check($unique, 'Scheduler uniqueness requested'); $GLOBALS['queue'][serialize($args)] = $args; }
}
function wp_next_scheduled($hook, $args) { return isset($GLOBALS['queue'][serialize($args)]); }
function wp_schedule_single_event($time, $hook, $args) { if (!empty($GLOBALS['schedule_error'])) { throw new RuntimeException('Scheduler unavailable'); } $GLOBALS['queue'][serialize($args)] = $args; }
function mailchimp_get_list_id() { return $GLOBALS['list']; }
function mailchimp_is_configured() { return $GLOBALS['configured']; }
function mailchimp_get_api() { return $GLOBALS['api']; }
function mailchimp_error(...$args) {}
require dirname(__DIR__, 2) . '/includes/api/class-mailchimp-api.php';
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-gdpr-fields.php';
class GDPRFixtureApi extends MailChimp_WooCommerce_MailChimpApi {
    public $calls = 0;
    public $response;
    public function get($path, $params = array()) {
        ++$this->calls;
        if ($this->response instanceof Exception) { throw $this->response; }
        return $this->response;
    }
}
$GLOBALS['api'] = $api = new GDPRFixtureApi();
$key = 'mailchimp-woocommerce-gdpr-fields.audience-a';
$raw = array(array('marketing_permission_id' => '123', 'text' => 'Email', 'enabled' => true));
$expected = $raw; $expected[0]['enabled'] = false;
$api->response = array('members' => array(array('marketing_permissions' => $raw)));
$cold = $api->getCachedGDPRFields('audience-a');
check($api->calls === 0, 'Cold cache must not call Mailchimp synchronously');
check($cold === null, 'Cold cache is unknown');
check($api->getCachedGDPRFields('audience-a') === null && count($GLOBALS['queue']) === 1, 'Repeated cold reads queue once');
check($api->calls === 0, 'Checkout never calls the API on cache miss');
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check($api->calls === 1 && $api->getCachedGDPRFields('audience-a') === $expected, 'Worker fetches definitions without sampled consent');
check($GLOBALS['transients'][$key][1] === 300, 'Default freshness is five minutes');
$GLOBALS['queue'] = array(); delete_transient($key);
$api->response = new RuntimeException('Simulated outage');
check($api->getCachedGDPRFields('audience-a') === $expected && $api->calls === 1, 'Expired reads use last-good fields without network');
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
$GLOBALS['queue'] = array();
check($api->getCachedGDPRFields('audience-a') === $expected && !$GLOBALS['queue'], 'Failure retains fields and backs off');
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check($api->calls === 2, 'Worker respects failure backoff');
delete_transient($key . '.retry');
$api->response = array('invalid' => true);
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check(get_option($key . '.last-good') === $expected, 'Malformed response does not erase last-good');
delete_transient($key . '.retry');
$api->response = array('members' => array(array('marketing_permissions' => array(array('text' => 'Broken')))));
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check(get_option($key . '.last-good') === $expected, 'Malformed definitions do not erase last-good');
delete_transient($key . '.retry');
$api->response = array('members' => array()); $GLOBALS['minutes'] = 17;
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check($api->getCachedGDPRFields('audience-a') === array(), 'Successful empty response is known empty');
check($GLOBALS['transients'][$key][1] === 1020, 'Refresh honors existing TTL filter');
delete_transient($key);
$api->response = array('members' => array(array()));
$GLOBALS['minutes'] = 0;
MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a', 3);
check($api->getCachedGDPRFields('audience-a') === array(), 'Audiences without permissions are known empty');
check($GLOBALS['transients'][$key][1] === 180, 'Invalid TTL filter retains the caller default');
$before = $api->calls; $GLOBALS['list'] = 'audience-b';
check($api->getCachedGDPRFields('audience-b') === null, 'New audience cannot reuse old definitions');
delete_transient($key); MailChimp_WooCommerce_GDPR_Fields::refresh('audience-a');
check($api->calls === $before, 'Worker ignores former audience');
$GLOBALS['configured'] = false; MailChimp_WooCommerce_GDPR_Fields::refresh('audience-b');
check($api->calls === $before, 'Worker ignores disconnected stores');
$GLOBALS['configured'] = true;
set_transient('mailchimp-woocommerce-gdpr-fields.audience-b', $raw, 300);
check($api->getCachedGDPRFields('audience-b') === $expected, 'Legacy warm cache does not copy member consent');
delete_transient('mailchimp-woocommerce-gdpr-fields.audience-b');
check($api->getCachedGDPRFields('audience-b') === $expected, 'Legacy warm definitions survive later expiry');
check($api->getCachedGDPRFields(null) === null, 'Missing audience is unknown');
$GLOBALS['queue'] = array(); $GLOBALS['schedule_error'] = true;
check($api->getCachedGDPRFields('audience-b') === $expected, 'Scheduler failure still serves stale fields');
check($api->getCachedGDPRFields('audience-c') === null, 'Scheduler failure on a cold cache remains unknown');
echo "GDPR cache regression passed ($checks checks).\n";

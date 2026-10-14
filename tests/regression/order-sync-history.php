<?php
/** Run with: php tests/regression/order-sync-history.php. No WordPress or network. */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL & ~E_DEPRECATED);
$GLOBALS['checks'] = 0;
function check($condition, $message) { ++$GLOBALS['checks']; if (!$condition) { throw new RuntimeException($message); } }
function __($text, $domain = null) { return $text; }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html__($text, $domain = null) { return esc_html($text); }
function sanitize_key($text) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($text)); }
function get_option($key) { return $key === 'date_format' ? 'Y-m-d' : 'H:i:s'; }
function wp_date($format, $time) { return gmdate($format, $time); }
function wp_timezone_string() { return 'UTC'; }
function wc_get_order_status_name($status) { return $status; }
function current_user_can($cap, $id = null) { return $GLOBALS['allowed'] && ($id === null || $id === 42); }
function wc_get_page_screen_id($type) { return 'woocommerce_page_wc-orders'; }
function add_meta_box($id, $title, $callback, $screen, $context, $priority) { $GLOBALS['screen'] = $screen; }
class HistoryMeta { private $data; public function __construct($data) { $this->data = $data; } public function get_data() { return $this->data; } }
class WC_Order {
    private $metadata = array();
    private $deleted = array();
    public function get_id() { return 42; }
    public function get_status() { return $GLOBALS['cfg']['status']; }
    public function read_meta_data($force = false) { $this->metadata = $GLOBALS['metadata']; }
    public function get_meta_data() { if (!$this->metadata) { $this->read_meta_data(); } return array_map(function($row) { return new HistoryMeta($row); }, $this->metadata); }
    public function add_meta_data($key, $value, $unique = false) { $this->metadata[] = array('id' => 0, 'key' => $key, 'value' => $value); }
    public function delete_meta_data_by_mid($id) { $this->deleted[] = $id; }
    public function save_meta_data() {
        if ($GLOBALS['cfg']['storage_error']) { throw new RuntimeException('Fixture storage error'); }
        $GLOBALS['metadata'] = array_values(array_filter($GLOBALS['metadata'], function($row) { return !in_array($row['id'], $this->deleted, true); }));
        foreach ($this->metadata as $row) { if (!$row['id']) { $row['id'] = ++$GLOBALS['next_meta_id']; $GLOBALS['metadata'][] = $row; } }
    }
    public function get_order_number() { return 42; }
    public function get_type() { return $GLOBALS['cfg']['type']; }
    public function get_user_id() { return $GLOBALS['cfg']['role'] ? 7 : 0; }
    public function get_billing_email() { return $GLOBALS['cfg']['email']; }
    public function get_meta($key) { return null; }
}
function wc_get_order($id) { if ($GLOBALS['cfg']['read_error']) { throw new RuntimeException('Fixture read error'); } return (int) $id === 42 ? new WC_Order() : false; }
class MailChimp_WooCommerce_HPOS { public static function get_order($id) { return wc_get_order($id); } public static function enabled() { return $GLOBALS['hpos']; } }
class Mailchimp_Woocommerce_DB_Helpers { public static function get_option($key) { return array('mailchimp_auto_subscribe' => $GLOBALS['cfg']['subscribe_setting']); } }
function get_user_by($key, $id) { return (object) array('roles' => array($GLOBALS['cfg']['role'])); }
function apply_filters($name, $value) { return $name === 'mailchimp_campaign_user_roles' ? array() : $value; }
function mailchimp_is_configured() { return $GLOBALS['cfg']['configured']; }
function mailchimp_get_api() { return $GLOBALS['api']; }
function mailchimp_get_store_id() { return 'store'; }
function mailchimp_get_list_id() { return 'list'; }
function mailchimp_debug(...$args) {}
function mailchimp_log(...$args) {}
function mailchimp_error(...$args) {}
function mailchimp_error_trace(...$args) { return 'Fixture error'; }
function mailchimp_string_contains($haystack, $needle) { return strpos($haystack, $needle) !== false; }
function mailchimp_as_push($job, $delay) { ++$GLOBALS['retries']; }
function mailchimp_set_transient(...$args) {}
function mailchimp_list_has_double_optin() { return $GLOBALS['cfg']['doi']; }
function mailchimp_sms_consent_enabled() { return $GLOBALS['cfg']['sms']; }
function mailchimp_submit_subscribed_only() { return $GLOBALS['cfg']['subscribers_only']; }
function is_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function mailchimp_email_is_amazon($email) { return strpos($email, '@amazon.example') !== false; }
function mailchimp_email_is_privacy_protected($email) { return strpos($email, '@privacy.example') !== false; }
function mailchimp_member_data_update(...$args) { if ($GLOBALS['cfg']['follow_up_error'] && $GLOBALS['api']->submitted) { throw new RuntimeException('Follow-up secret@example.test'); } }
function mailchimp_member_sms_update(...$args) {}
function mailchimp_register_synced_resource(...$args) {}
class FixtureCustomer {
    public function getEmailAddress() { return $GLOBALS['cfg']['email']; }
    public function getOptInStatus() { return $GLOBALS['cfg']['opted_in']; }
    public function getId() { return 'fixture-customer'; }
}
class FixtureOrder {
    public function getOriginalWooStatus() { return $GLOBALS['cfg']['status']; }
    public function shouldIgnoreIfNotInMailchimp() { return $GLOBALS['cfg']['ignore']; }
    public function getCustomer() { return new FixtureCustomer(); }
    public function isFlaggedAsAmazonOrder() { return $GLOBALS['cfg']['amazon_flag']; }
    public function isFlaggedAsPrivacyProtected() { return $GLOBALS['cfg']['privacy_flag']; }
    public function getFinancialStatus() { return $GLOBALS['cfg']['financial']; }
    public function getId() { return 42; }
    public function setFinancialStatus($status) {}
    public function confirmAndPay($value) {}
    public function items() { return array('fixture-line'); }
    public function toArray() { return array(); }
}
class MailChimp_WooCommerce_Transform_Orders { public function setSyncing($value) {} public function transform($order) { return new FixtureOrder(); } }
class FixtureApi {
    public $submitted = false;
    public $calls = 0;
    public function setIsSyncing($value) {}
    public function getStoreOrder(...$args) { if ($GLOBALS['cfg']['lookup_limit']) { throw new MailChimp_WooCommerce_RateLimitError('Fixture rate limit'); } return $GLOBALS['cfg']['remote_exists']; }
    public function member(...$args) { if ($GLOBALS['cfg']['member_error']) { throw new RuntimeException('Lookup error shopper@example.test'); } return array('status' => $GLOBALS['cfg']['member_status']); }
    public function useAutoDoi($value) {}
    public function deleteCartByID(...$args) { return false; }
    public function deleteCustomer(...$args) {}
    public function handleProductsMissingFromAPI($order) {}
    public function addStoreOrder(...$args) {
        ++$this->calls;
        if ($GLOBALS['cfg']['api_error'] && (!$GLOBALS['cfg']['recover'] || $this->calls === 1)) { throw $GLOBALS['cfg']['api_error']; }
        $this->submitted = !$GLOBALS['cfg']['empty'];
        return $this->submitted ? array('id' => 42) : null;
    }
    public function updateStoreOrder(...$args) { return $this->addStoreOrder(...$args); }
}
require dirname(__DIR__, 2) . '/includes/processes/class-mailchimp-woocommerce-job.php';
require dirname(__DIR__, 2) . '/includes/api/errors/class-mailchimp-error.php';
require dirname(__DIR__, 2) . '/includes/api/errors/class-mailchimp-rate-limit-error.php';
require dirname(__DIR__, 2) . '/includes/api/errors/class-mailchimp-server-error.php';
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-order-sync-history.php';
require dirname(__DIR__, 2) . '/includes/processes/class-mailchimp-woocommerce-single-order.php';
function reset_history($cfg = array()) {
    $GLOBALS['cfg'] = array_merge(array('status' => 'processing', 'financial' => 'paid', 'type' => 'shop_order', 'role' => null, 'email' => 'shopper@example.test', 'configured' => true, 'opted_in' => true, 'doi' => false, 'sms' => false, 'ignore' => false, 'subscribers_only' => false, 'remote_exists' => false, 'subscribe_setting' => '0', 'member_status' => 'subscribed', 'member_error' => false, 'lookup_limit' => false, 'amazon_flag' => false, 'privacy_flag' => false, 'api_error' => null, 'empty' => false, 'recover' => false, 'follow_up_error' => false, 'storage_error' => false, 'read_error' => false), $cfg);
    $GLOBALS['api'] = new FixtureApi(); $GLOBALS['metadata'] = array(); $GLOBALS['next_meta_id'] = 0; $GLOBALS['retries'] = 0; $GLOBALS['allowed'] = true; $GLOBALS['hpos'] = false; $GLOBALS['screen'] = null;
}
function events() { return array_map(function($row) { return $row['value']; }, $GLOBALS['metadata']); }
function last_event() { $events = events(); return end($events); }
function run_case($cfg, $reason, $full_sync = false, $update = false) {
    reset_history($cfg);
    $job = (new MailChimp_WooCommerce_Single_Order(42))->set_full_sync($full_sync);
    $job->is_update = $update;
    try { $result = $job->process(); } catch (Exception $e) { $result = $e; }
    check(last_event()['reason'] === $reason, 'Expected recorded reason: ' . $reason);
    check(last_event()['mode'] === ($full_sync ? 'initial' : 'live'), 'Capture original sync mode');
    check(strpos(json_encode(events()), 'shopper@example.test') === false, 'History excludes customer emails');
    return $result;
}
run_case(array('configured' => false), 'not_configured');
run_case(array('type' => 'custom_order'), 'order_type_filtered');
run_case(array('role' => 'administrator'), 'user_role_filtered');
run_case(array('subscribe_setting' => '2', 'member_status' => 'unsubscribed'), 'existing_member_status', true);
run_case(array('subscribe_setting' => '2', 'member_error' => true), 'audience_lookup_failed', true);
run_case(array(), 'update_without_remote_order', false, true);
run_case(array('status' => 'checkout-draft'), 'draft');
run_case(array('ignore' => true), 'new_status_ignored');
run_case(array('email' => 'bad'), 'bad_email');
run_case(array('email' => 'test@amazon.example'), 'amazon_order');
run_case(array('email' => 'test@privacy.example'), 'privacy_restricted');
run_case(array('amazon_flag' => true), 'amazon_order');
run_case(array('privacy_flag' => true), 'privacy_restricted');
run_case(array('opted_in' => false, 'subscribers_only' => true), 'subscribers_only');
run_case(array('status' => 'pending', 'financial' => 'pending'), 'new_financial_status');
run_case(array('financial' => 'failed'), 'new_financial_status');
run_case(array('financial' => 'refunded'), 'initial_financial_status', true);
run_case(array('empty' => true), 'empty_response');
check(run_case(array(), 'order_created') === array('id' => 42), 'Sync return value is unchanged');
run_case(array('remote_exists' => true), 'order_updated');
run_case(array('doi' => true), 'order_created'); // DOI alone is not an order skip rule.
run_case(array('lookup_limit' => true), 'order_created');
check(events()[0]['reason'] === 'rate_limit_lookup' && $GLOBALS['retries'] === 1, 'Preserve existing lookup retry and continued submission behavior');
foreach (array('rate_limited' => new MailChimp_WooCommerce_RateLimitError('secret@example.test'), 'server_error' => new MailChimp_WooCommerce_ServerError('secret@example.test'), 'api_error' => new MailChimp_WooCommerce_Error('secret@example.test'), 'unexpected_error' => new RuntimeException('secret@example.test')) as $reason => $exception) {
    $result = run_case(array('api_error' => $exception), $reason);
    check($result === $exception, 'Original exception is still thrown');
    check(strpos(json_encode(events()), 'secret@example.test') === false, 'Raw exceptions must not be saved');
}
run_case(array('api_error' => new RuntimeException('Customer email can not be changed'), 'recover' => true), 'customer_recovered');
check($GLOBALS['api']->calls === 2, 'Existing recovery still retries the API');
run_case(array('follow_up_error' => true), 'follow_up_error', true);
check(events()[0]['outcome'] === 'synced', 'A follow-up failure must not erase accepted order evidence');
reset_history(array('storage_error' => true));
check((new MailChimp_WooCommerce_Single_Order(42))->process() === array('id' => 42), 'Storage failure must not break sync');
check(!events(), 'Failed persistence must not invent recorded success');
reset_history();
for ($i = 0; $i < 25; ++$i) { check(MailChimp_WooCommerce_Order_Sync_History::record(42, 'skipped', 'draft'), 'Record persisted'); }
check(count(events()) === 20, 'Retain only 20 recent records');
check($GLOBALS['metadata'][0]['id'] === 6, 'Drop oldest IDs, preserve newest');
check(!MailChimp_WooCommerce_Order_Sync_History::record(999, 'skipped', 'draft'), 'Missing orders are a no-op');
check(!MailChimp_WooCommerce_Order_Sync_History::record(null, 'skipped', 'draft'), 'Missing IDs must not fall back to a global WordPress order');
check(!MailChimp_WooCommerce_Order_Sync_History::record(42, array(), 'draft'), 'Malformed outcomes are a no-op');
check(!MailChimp_WooCommerce_Order_Sync_History::record(42, 'skipped', 'raw exception'), 'Reject arbitrary reason text');
$GLOBALS['metadata'][] = array('id' => 1000, 'key' => '_unrelated', 'value' => 'preserve');
MailChimp_WooCommerce_Order_Sync_History::record(42, 'skipped', 'draft', false, 'completed');
check(end($GLOBALS['metadata'])['value']['order_status'] === 'completed', 'Keep the status captured by the attempt, not a later order status');
check(count(array_filter($GLOBALS['metadata'], function($row) { return $row['key'] === '_unrelated'; })) === 1, 'Pruning preserves unrelated metadata');
$widget = new MailChimp_WooCommerce_Order_Sync_History();
foreach (array(false => 'shop_order', true => 'woocommerce_page_wc-orders') as $hpos => $screen) { $GLOBALS['hpos'] = (bool) $hpos; $widget->register_meta_box(); check($GLOBALS['screen'] === $screen, 'Correct order editor screen'); }
ob_start(); $widget->render((object) array('ID' => 42)); $html = ob_get_clean();
check(strpos($html, 'Checkout-draft') !== false && strpos($html, 'UTC') !== false, 'Legacy callback renders history and timezone');
$GLOBALS['allowed'] = false; $GLOBALS['screen'] = null; $widget->register_meta_box();
ob_start(); $widget->render(new WC_Order()); $html = ob_get_clean();
check($html === '' && $GLOBALS['screen'] === null, 'Users without order permissions see no widget or history');
reset_history(); ob_start(); $widget->render(new WC_Order()); $html = ob_get_clean();
check(strpos($html, 'may already exist') !== false, 'Empty history must not claim the order never synced');
reset_history(array('read_error' => true)); ob_start(); $widget->render(new WC_Order()); $html = ob_get_clean();
check(strpos($html, 'role="alert"') !== false, 'Read failures show an operational error, not an empty state');
reset_history(); $GLOBALS['metadata'][] = array('id' => 1, 'key' => MailChimp_WooCommerce_Order_Sync_History::META_KEY, 'value' => array('timestamp' => time(), 'outcome' => 'failed', 'reason' => '<script>secret</script>', 'mode' => 'live', 'order_status' => '<img onerror=alert(1)>'));
ob_start(); $widget->render(new WC_Order()); $html = ob_get_clean();
check(strpos($html, 'could not be displayed') !== false && strpos($html, '<script>') === false, 'Corrupt history is rejected safely');
$GLOBALS['metadata'][0]['value']['reason'] = 'draft';
ob_start(); $widget->render(new WC_Order()); $html = ob_get_clean();
check(strpos($html, '<img') === false && strpos($html, '&lt;img') !== false, 'Escaping protects valid entries with a tampered status');
echo 'Order sync history regression checks passed (' . $GLOBALS['checks'] . " checks).\n";

<?php
/** Run with PHP 7.4+ and ext-curl: php tests/regression/live-hook-eligibility.php */
$root = dirname(__DIR__, 2);
require $root . '/includes/processes/class-mailchimp-woocommerce-job.php';
require $root . '/includes/api/class-mailchimp-api.php';
require $root . '/includes/processes/class-mailchimp-woocommerce-single-product.php';
require $root . '/includes/processes/class-mailchimp-woocommerce-single-product-category.php';
class MailChimp_WooCommerce_Options {}
require $root . '/includes/class-mailchimp-woocommerce-service.php';
$bootstrap = file_get_contents($root . '/bootstrap.php');
foreach (array('mailchimp_as_push', 'mailchimp_handle_or_queue', 'mailchimp_call_live_hook') as $function) {
    $start = strpos($bootstrap, 'function ' . $function . '(');
    $end = strpos($bootstrap, '/**', $start);
    eval(substr($bootstrap, $start, $end - $start));
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function mailchimp_environment_variables() {
    return (object) array('version' => 'test', 'php_version' => PHP_VERSION, 'wp_version' => 'test', 'wc_version' => 'test');
}
function mailchimp_get_outbound_ip() { return false; }
function mailchimp_debug() {}
function mailchimp_log() {}
function maybe_serialize($value) { return serialize($value); }
function current_action() { return 'EligibilityJob'; }
function apply_filters($name, $value) { return isset($GLOBALS['filters'][$name]) ? $GLOBALS['filters'][$name] : $value; }
class Mailchimp_Woocommerce_DB_Helpers {
    public static function get_option($key) { return 100; }
}
class ActionScheduler_Store { const STATUS_PENDING = 'pending'; }
function as_get_scheduled_actions($args) { return $GLOBALS['pending'] ? array(1) : array(); }
function as_unschedule_action() { $GLOBALS['pending'] = false; }
function as_schedule_single_action($time, $hook, $args, $group) {
    $GLOBALS['scheduled'][] = array($time, $hook, $args, $group);
    return count($GLOBALS['scheduled']);
}
class EligibilityDatabase {
    public $prefix = 'wp_';
    public $last_error = '';
    public $rows = array();
    public function insert($table, $row) { $row['id'] = count($this->rows) + 1; $this->rows[] = (object) $row; return 1; }
    public function prepare($sql, ...$args) { return array($sql, $args); }
    public function query($query) {
        list($sql, $args) = $query;
        if (strpos($sql, 'UPDATE') === 0) {
            $this->rows[count($this->rows)-1]->job = $args[0];
        } else {
            $this->rows = array_values(array_filter($this->rows, function ($row) use ($args) { return $row->id !== $args[0][0]; }));
        }
        return 1;
    }
    public function get_row($query) { return $this->rows[0]; }
}
class EligibilityApi extends MailChimp_WooCommerce_MailChimpApi {
    public $requests = array();
    public function send($method) { return $this->{strtolower($method)}('ecommerce/stores/test/products/test', array('id' => 'test')); }
    protected function applyCurlOptions($method, $url, $params = array(), $headers = array()) {
        $options = parent::applyCurlOptions($method, $url, $params, $headers);
        $this->requests[] = $options;
        return $options;
    }
    protected function processCurlResponse($curl, $request_data = null) { return true; }
}
class EligibilityJob extends Mailchimp_Woocommerce_Job {
    public $id = 123;
    public $is_full_sync = false;
    public $fail = false;
    public $retry_delay = null;
    public function handle() {
        foreach (array('GET', 'POST', 'PUT', 'PATCH', 'DELETE') as $method) { $GLOBALS['api']->send($method); }
        if ($this->retry_delay !== null) { $this->retry($this->retry_delay); }
        if ($this->fail) { throw new RuntimeException('controlled failure'); }
        return 'handled';
    }
}
function verify_headers($expected) {
    foreach ($GLOBALS['api']->requests as $request) {
        $headers = $request[CURLOPT_HTTPHEADER];
        $found = array_values(array_filter($headers, function ($header) { return strpos($header, 'X-Object-Notified-At:') === 0; }));
        $want = $request[CURLOPT_CUSTOMREQUEST] === 'GET' || $expected === null ? array() : array('X-Object-Notified-At: ' . $expected);
        check($found === $want, 'Wrong header for ' . $request[CURLOPT_CUSTOMREQUEST]);
        check(in_array('content-type: application/json', $headers, true), 'Existing headers lost');
        check($request[CURLOPT_USERPWD] === 'mailchimp:test', 'Auth changed');
    }
    $GLOBALS['api']->requests = array();
}
$GLOBALS['wpdb'] = new EligibilityDatabase();
$GLOBALS['api'] = new EligibilityApi('test-us1');
$GLOBALS['scheduled'] = array();
$GLOBALS['pending'] = false;
$GLOBALS['filters'] = array();

foreach (array(0, 90, 301, 1801) as $delay) {
    $job = new EligibilityJob();
    $job->prepend_to_queue = true;
    $before = time();
    mailchimp_as_push($job, $delay, true);
    check($job->get_eligible_at() >= $before + $delay && $job->get_eligible_at() <= time() + $delay, 'Use first eligibility, excluding intentional delay');
    check(end($GLOBALS['scheduled'])[0] === 100, 'Priority backdating changed');
    $stored = unserialize(end($GLOBALS['wpdb']->rows)->job);
    check($stored->get_eligible_at() === $job->get_eligible_at(), 'Metadata missing from DB serialization');
    $stored->handle_with_context();
    verify_headers($job->get_eligible_at());
    $first = $stored->get_eligible_at();
    $stored->retry(1801);
    $retried = unserialize(end($GLOBALS['wpdb']->rows)->job);
    check($retried->get_eligible_at() === $first, 'Retry replaced original eligibility');
    check($retried->get_attempts() === 1, 'Retry attempt behavior changed');
    $GLOBALS['pending'] = true;
    mailchimp_as_push($retried, 4000, true);
    check(unserialize(end($GLOBALS['wpdb']->rows)->job)->get_eligible_at() === $first, 'Pending reschedule replaced timestamp');
}
// A genuinely new event may replace a coalesced payload with its own eligibility.
$GLOBALS['pending'] = true;
$fresh = new EligibilityJob();
mailchimp_as_push($fresh, 777, true);
check(unserialize(end($GLOBALS['wpdb']->rows)->job)->get_eligible_at() === $fresh->get_eligible_at(), 'Fresh event timestamp not persisted');

foreach (array(null, '', '123', '1e3', true, false, 0, -1, 1.5, array(123), new stdClass()) as $bad) {
    $job = new EligibilityJob();
    $job->eligible_at = $bad;
    $job->retry(30);
    check($job->get_eligible_at() === null, 'Malformed/missing retry metadata was fabricated');
    $job->handle_with_context();
    verify_headers(null);
}
$old = unserialize('O:14:"EligibilityJob":1:{s:2:"id";i:123;}');
check($old->get_eligible_at() === null, 'Old serialized job must omit header');
$old->handle_with_context();
verify_headers(null);
foreach (array(false, true) as $full_sync) {
    $manual = new EligibilityJob();
    $manual->is_full_sync = $full_sync;
    mailchimp_handle_or_queue($manual);
    check($manual->get_eligible_at() === null, 'Manual/full sync must omit header');
    if ($full_sync) { $manual->eligible_at = 123; }
    $manual->handle_with_context();
    verify_headers(null);
}
// Delay filters are applied before the first timestamp is stored.
$GLOBALS['filters']['mailchimp_handle_or_queue_product_delay'] = 600;
$product = new MailChimp_WooCommerce_Single_Product(123);
mailchimp_handle_or_queue($product, 5, true);
check($product->get_eligible_at() >= time() + 599, 'Configured delay ignored');
$GLOBALS['filters'] = array();

// Existing mutation and historical-mode headers remain intact.
$GLOBALS['api']->useAutoDoi(true);
Mailchimp_Woocommerce_Job::with_eligible_at(456, array($GLOBALS['api'], 'send'), array('POST'));
$headers = $GLOBALS['api']->requests[0][CURLOPT_HTTPHEADER];
check(in_array('X-Status-Resolution-Method: auto-doi', $headers, true), 'DOI header lost');
check(in_array('Content-Length: 13', $headers, true), 'Content length changed');
verify_headers(456);
$GLOBALS['api']->useAutoDoi(false)->setIsSyncing(true);
$GLOBALS['api']->send('POST');
check(in_array('X-Data-Mode: historical', $GLOBALS['api']->requests[0][CURLOPT_HTTPHEADER], true), 'Historical header lost');
verify_headers(null);
$GLOBALS['api']->setIsSyncing(false);

// Run the actual persisted-job dispatcher, including cleanup and a caught exception.
foreach (array(false, true) as $fail) {
    $GLOBALS['wpdb']->rows = array();
    $job = new EligibilityJob();
    $job->fail = $fail;
    $job->set_eligible_at(time() - 1900);
    mailchimp_as_push($job);
    $result = (new MailChimp_Service())->mailchimp_process_single_job(123);
    check($result === !$fail, 'Dispatcher return changed');
    verify_headers($job->get_eligible_at());
    check(count($GLOBALS['wpdb']->rows) === ($fail ? 1 : 0), 'Dispatcher cleanup changed');
    check(Mailchimp_Woocommerce_Job::active_eligible_at() === null, 'Context leaked from dispatcher');
}
Mailchimp_Woocommerce_Job::with_eligible_at(101, function () {
    $manual = new EligibilityJob();
    $manual->handle_with_context();
    verify_headers(null);
    check(Mailchimp_Woocommerce_Job::active_eligible_at() === 101, 'Nested context not restored');
    try {
        Mailchimp_Woocommerce_Job::with_eligible_at(202, function () { throw new Error('controlled error'); });
    } catch (Error $e) {}
    check(Mailchimp_Woocommerce_Job::active_eligible_at() === 101, 'Error leaked context');
});
mailchimp_call_live_hook(array($GLOBALS['api'], 'send'), array('DELETE'));
$direct = $GLOBALS['api']->requests[0][CURLOPT_HTTPHEADER];
check(count(array_filter($direct, function ($header) { return strpos($header, 'X-Object-Notified-At:') === 0; })) === 1, 'Immediate live DELETE omitted');
$GLOBALS['api']->requests = array();
$GLOBALS['api']->send('DELETE');
verify_headers(null);

foreach (array(null, 123) as $timestamp) {
    $GLOBALS['wpdb']->rows = array();
    $category = new Mailchimp_WooCommerce_Single_Product_Category(42);
    $category->set_eligible_at($timestamp);
    $category->handleFailedProductsSync(array(11, 12));
    check(count($GLOBALS['wpdb']->rows) === 3, 'Category child behavior changed');
    foreach ($GLOBALS['wpdb']->rows as $row) { check(unserialize($row->job)->get_eligible_at() === $timestamp, 'Category child lost original eligibility'); }
}
echo 'Live hook eligibility regression checks passed on PHP ' . PHP_VERSION . ".\n";

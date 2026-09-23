<?php
/** Run with: php tests/regression/object-notified-at.php */
// Load only the queue helpers, avoiding WordPress bootstrap side effects.
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
foreach (array('function mailchimp_as_push(', 'function mailchimp_handle_or_queue_live(') as $needle) {
    $start = strpos($bootstrap, $needle);
    $end = strpos($bootstrap, "\n/**", $start);
    eval(substr($bootstrap, $start, $end - $start));
}
error_reporting(E_ALL & ~E_DEPRECATED);
class MailChimp_WooCommerce_Options {}
require dirname(__DIR__, 2) . '/includes/processes/class-mailchimp-woocommerce-job.php';
require dirname(__DIR__, 2) . '/includes/api/class-mailchimp-api.php';
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-service.php';

class ActionScheduler_Store { const STATUS_PENDING = 'pending'; }
function mailchimp_log(...$args) {}
function mailchimp_debug(...$args) {}
function mailchimp_error(...$args) {}
function maybe_serialize($data) { return serialize($data); }
function maybe_unserialize($data) { return is_string($data) ? unserialize($data) : $data; }
function current_action() { return 'NotifiedJob'; }
function mailchimp_handle_or_queue($job, $delay = 0) { mailchimp_as_push($job, $delay); }
function mailchimp_environment_variables() { return (object) array('version' => 1, 'php_version' => 1, 'wp_version' => 1, 'wc_version' => 1); }
function mailchimp_get_outbound_ip() { return null; }
function mailchimp_common_loopback_ips() { return array(); }
function as_get_scheduled_actions($query) { return $GLOBALS['pending'] ? array(1) : array(); }
function as_unschedule_action(...$args) {}
function as_schedule_single_action(...$args) { return 1; }

class QueueDatabase {
    public $prefix = 'wp_';
    public $jobs = array();
    public function prepare($sql, ...$args) { return array($sql, is_array($args[0]) ? $args[0] : $args); }
    public function insert($table, $args) { $this->jobs[$args['obj_id']] = $args['job']; return true; }
    public function get_row($query) { return (object) array('id' => 1, 'job' => $this->jobs[$query[1][0]]); }
    public function query($query) {
        list($sql, $args) = $query;
        if (strpos($sql, 'UPDATE') === 0) { $this->jobs[$args[2]] = $args[0]; }
        return 1;
    }
}
class NotifiedJob extends Mailchimp_Woocommerce_Job {
    public $id;
    public $throws = false;
    public function __construct($id) { $this->id = $id; }
    public function handle() {
        $GLOBALS['headers'] = (new HeaderApi())->headers();
        if ($this->throws) { throw new Exception('rate limited'); }
    }
}
class HeaderApi extends MailChimp_WooCommerce_MailChimpApi {
    public function headers() { return $this->applyCurlOptions('GET', 'ping')[CURLOPT_HTTPHEADER]; }
}
class RunnerService extends MailChimp_Service { public function __construct() {} }
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function notified_header() {
    foreach ($GLOBALS['headers'] as $header) {
        if (strpos($header, 'X-Object-Notified-At: ') === 0) { return (int) substr($header, 22); }
    }
    return null;
}
function run_job($obj_id) {
    $GLOBALS['headers'] = array();
    (new RunnerService())->mailchimp_process_single_job($obj_id);
    return notified_header();
}

$GLOBALS['wpdb'] = $wpdb = new QueueDatabase();
$GLOBALS['pending'] = false;

// sync jobs never carry the header
mailchimp_handle_or_queue(new NotifiedJob('sync'));
check(run_job('sync') === null, 'sync job must not send X-Object-Notified-At');

check(unserialize($wpdb->jobs['sync'])->get_notified_at() !== null, 'sync job is still stamped');

// live jobs send the time they were scheduled to run (queued + delay)
$before = time();
mailchimp_handle_or_queue_live(new NotifiedJob('live'), 90);
$sent = run_job('live');
check($sent >= $before + 90 && $sent <= time() + 90, 'live job must send queued time plus delay');

// a rate-limit retry keeps the original timestamp
$job = unserialize($wpdb->jobs['live']);
$job->notified_at = 1000;
$job->retry();
check(run_job('live') === 1000, 'retry must keep the original notified_at');

// set_notified_at never overwrites an existing stamp
check((new NotifiedJob('x'))->set_notified_at(5)->set_notified_at(9)->get_notified_at() === 5, 'stamp must not be overwritten');

// header state is cleared after a failing job, so the next sync job is clean
$failing = new NotifiedJob('fail');
$failing->throws = true;
mailchimp_handle_or_queue_live($failing);
check(run_job('fail') !== null, 'failing live job must still send the header');
$GLOBALS['headers'] = (new HeaderApi())->headers();
check(notified_header() === null, 'header must not leak past the job');

echo "Object notified-at regression checks passed.\n";

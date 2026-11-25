<?php
/** Run with: php tests/regression/object-notified-at.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
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
function as_schedule_single_action($fire_at, ...$args) { $GLOBALS['scheduled_at'] = $fire_at; return 1; }
class Mailchimp_Woocommerce_DB_Helpers {
    public static function get_option($name) { return 100; }
}

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
    public $method = 'POST';
    public function __construct($id) { $this->id = $id; }
    public function handle() {
        $GLOBALS['headers'] = (new HeaderApi())->headers($this->method);
        if ($this->throws) { throw new Exception('rate limited'); }
    }
}
class HeaderApi extends MailChimp_WooCommerce_MailChimpApi {
    public function headers($method = 'POST') { return $this->applyCurlOptions($method, 'ping')[CURLOPT_HTTPHEADER]; }
}
class RunnerService extends MailChimp_Service { public function __construct() {} }
function mailchimp_get_api() { return $GLOBALS['runner_api']; }
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
$GLOBALS['runner_api'] = new HeaderApi();
$GLOBALS['pending'] = false;

// sync jobs never carry the header
mailchimp_handle_or_queue(new NotifiedJob('sync'));
check(run_job('sync') === null, 'sync job must not send X-Object-Notified-At');

check(unserialize($wpdb->jobs['sync'])->get_notified_at() !== null, 'sync job is still stamped');

// live jobs send the time they were scheduled to run (queued + delay)
$before = time();
$live = new NotifiedJob('live');
$live->prepend_to_queue = true;
mailchimp_handle_or_queue_live($live, 90);
check($GLOBALS['scheduled_at'] === 100, 'priority backdating must remain unchanged');
$sent = run_job('live');
check($sent >= $before + 90 && $sent <= time() + 90, 'live job must send queued time plus delay');
check(unserialize($wpdb->jobs['live'])->get_notified_at() === $sent, 'persisted eligibility must match sent timestamp');
$get = unserialize($wpdb->jobs['live']);
$get->method = 'GET';
mailchimp_as_push($get);
check(run_job('live') === null, 'GET must omit timestamp inside live job');

// a rate-limit retry keeps the original timestamp
$job = unserialize($wpdb->jobs['live']);
$job->method = 'POST';
$job->notified_at = 1000;
$job->retry();
check(run_job('live') === 1000, 'retry must keep the original notified_at');

// set_notified_at never overwrites an existing stamp
check((new NotifiedJob('x'))->set_notified_at(5)->set_notified_at(9)->get_notified_at() === 5, 'stamp must not be overwritten');
foreach (array('abc', '123', '-1', 0, -5, 1.5, true, array(123)) as $bad) {
    $job = new NotifiedJob('invalid');
    $job->is_live_event = true;
    $job->notified_at = $bad;
    mailchimp_as_push($job);
    check(run_job('invalid') === null, 'malformed persisted timestamp must omit header');
}
$old = unserialize('O:11:"NotifiedJob":1:{s:2:"id";s:3:"old";}');
mailchimp_as_push($old);
check(run_job('old') === null, 'old serialized job must omit header');

// header state is cleared after a failing job, so the next sync job is clean
$failing = new NotifiedJob('fail');
$failing->throws = true;
mailchimp_handle_or_queue_live($failing);
check(run_job('fail') !== null, 'failing live job must still send the header');
$GLOBALS['headers'] = (new HeaderApi())->headers();
check(notified_header() === null, 'header must not leak past the job');

// Omitted argument creates immediate context; an explicit timestamp (even null) is job context.
check(!method_exists(MailChimp_WooCommerce_MailChimpApi::class, 'withJobNotifiedAt'), 'obsolete job wrapper remains');
check(!method_exists(MailChimp_WooCommerce_MailChimpApi::class, 'executeCallbackWithoutJobMeta'), 'obsolete immediate wrapper remains');
$contract = new ReflectionMethod(MailChimp_WooCommerce_MailChimpApi::class, 'executeCallback');
check(!$contract->isStatic() && $contract->isPublic() && $contract->getNumberOfParameters() === 2 && $contract->getParameters()[1]->getName() === 'use_job_meta_timestamp', 'callback signature must be the requested instance method');
$api = new HeaderApi();
foreach (array(array(), array(123), array(null)) as $args) {
    $result = $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) use ($api) {
        check($callback_api === $api, 'callback must receive the exact API instance');
        return 'same instance';
    }, ...$args);
    check($result === 'same instance', 'callback result lost');
}
// Immediate live callbacks carry event time for mutations and restore outer context.
foreach (array('POST', 'PUT', 'PATCH', 'DELETE') as $method) {
    $before = time();
    $headers = (new HeaderApi())->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $api) use ($method) {
        return $api->headers($method);
    });
    $GLOBALS['headers'] = $headers;
    check(notified_header() >= $before && notified_header() <= time(), 'immediate mutation timestamp missing');
    $GLOBALS['headers'] = (new HeaderApi())->headers($method);
    check(notified_header() === null, 'immediate context leaked');
}
$GLOBALS['headers'] = (new HeaderApi())->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $api) {
    return $api->headers('GET');
});
check(notified_header() === null, 'immediate GET must omit timestamp');
$api = new HeaderApi();
$job_meta = new ReflectionProperty(MailChimp_WooCommerce_MailChimpApi::class, 'job_notified_at');
$job_meta->setAccessible(true);
$api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $job_api) use ($api, $job_meta) {
    $result = $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) use ($api, $job_meta) {
        check($callback_api === $api, 'callback must receive the API instance');
        check($job_meta->getValue() === null, 'inherited job metadata must be cleared inside the callback');
        $GLOBALS['headers'] = $callback_api->headers('DELETE');
        check(notified_header() !== 123, 'immediate mutation must not inherit the job timestamp');
        $callback_api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $nested_api) use ($callback_api) {
            check($nested_api === $callback_api, 'nested historical callback identity lost');
            $GLOBALS['headers'] = $nested_api->headers('POST');
            check(notified_header() === null, 'nested historical job must suppress immediate timestamp');
        }, null);
        $GLOBALS['headers'] = $callback_api->headers('POST');
        check(notified_header() !== null, 'immediate context must resume after nested historical job');
        return 'callback result';
    });
    check($result === 'callback result', 'callback return value lost');
    $GLOBALS['headers'] = $api->headers();
    check(notified_header() === 123, 'callback must restore exact outer job metadata');
    try {
        $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) use ($api, $job_meta) {
            check($callback_api === $api && $job_meta->getValue() === null, 'nested callback must clear job metadata');
            $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) {
                $GLOBALS['headers'] = $inner->headers('DELETE');
                check(notified_header() !== null, 'nested immediate callback must carry its own timestamp');
                throw new RuntimeException('nested failure');
            });
        });
        throw new RuntimeException('exception was swallowed');
    } catch (RuntimeException $e) {
        check($e->getMessage() === 'nested failure', 'original exception must propagate');
    }
    check($job_meta->getValue() === 123, 'nested failure must restore exact outer job metadata');
    $GLOBALS['headers'] = $api->headers();
    check(notified_header() === 123, 'nested failure must restore outer timestamp');
}, 123);
try {
    $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) {
        return $callback_api->getHeadersForPostOrPut('{}');
    });
    throw new RuntimeException('protected method was accessible');
} catch (Error $e) {
    check(strpos($e->getMessage(), 'protected method') !== false, 'external closure must retain its own visibility scope');
}
$api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $job_api) {
    try {
        (new HeaderApi())->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) { throw new RuntimeException('nested failure'); });
    } catch (RuntimeException $e) {}
    $GLOBALS['headers'] = (new HeaderApi())->headers();
    check(notified_header() === 123, 'nested failure must restore outer timestamp');
}, 123);
$GLOBALS['headers'] = (new HeaderApi())->headers();
check(notified_header() === null, 'outer timestamp leaked after nesting');
$job_meta->setValue('legacy-invalid');
$api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) use ($job_meta) {
    check($job_meta->getValue() === null, 'invalid inherited metadata must be cleared in callback');
    $GLOBALS['headers'] = $callback_api->headers('POST');
    check(notified_header() !== null, 'invalid inherited metadata must not block the immediate mutation');
});
check($job_meta->getValue() === 'legacy-invalid', 'callback must restore even invalid prior metadata exactly');
$job_meta->setValue(null);
$api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $job_api) {
    $api = new HeaderApi();
    $api->setIsSyncing(true);
    $GLOBALS['headers'] = $api->headers('DELETE');
    check(notified_header() === null, 'historical mutation must omit timestamp');
    check(in_array('X-Data-Mode: historical', $GLOBALS['headers'], true), 'historical header lost');
    run_job('sync');
    $GLOBALS['headers'] = (new HeaderApi())->headers();
    check(notified_header() === 456, 'nested historical job must restore outer context');
}, 456);
$GLOBALS['headers'] = (new HeaderApi())->headers();
check(notified_header() === null, 'nested historical context leaked');

// Explicit metadata is a timestamp value, not a truthy flag or a substitute event time.
try {
    $api->executeCallback(array(new NotifiedJob('array-callback'), 'handle'), 123);
    throw new RuntimeException('callback accepted method-name dispatch');
} catch (TypeError $e) {
    // Only Closure callbacks are allowed.
}
$api = new HeaderApi();
$result = $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $outer) use ($api, $job_meta) {
    check($outer === $api && $job_meta->getValue() === 111, 'outer job context or identity missing');
    $GLOBALS['headers'] = $outer->headers();
    check(notified_header() === 111, 'outer job timestamp missing');
    $nested = $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) use ($api) {
        check($inner === $api, 'nested job callback identity lost');
        $GLOBALS['headers'] = $inner->headers();
        check(notified_header() === 222, 'nested job timestamp missing');
        return 'nested result';
    }, 222);
    check($nested === 'nested result', 'nested job callback result lost');
    $GLOBALS['headers'] = $api->headers();
    check(notified_header() === 111, 'nested job context did not restore outer context');
    foreach (array(null, false, true, '123', 0, -1, 1.5, array(123)) as $invalid) {
        $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) use ($api) {
            check($inner === $api, 'invalid metadata callback identity lost');
            $GLOBALS['headers'] = $inner->headers();
            check(notified_header() === null, 'invalid metadata must not inherit or create a timestamp');
        }, $invalid);
        $GLOBALS['headers'] = $api->headers();
        check(notified_header() === 111, 'invalid metadata did not restore outer job timestamp');
    }
    try {
        $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) {
            throw new RuntimeException('job callback failure');
        }, 333);
        throw new RuntimeException('job callback exception was swallowed');
    } catch (RuntimeException $e) {
        check($e->getMessage() === 'job callback failure', 'job callback original exception lost');
    }
    $GLOBALS['headers'] = $api->headers();
    check(notified_header() === 111 && $job_meta->getValue() === 111, 'failed nested job context did not restore outer context');
    return 'outer result';
}, 111);
check($result === 'outer result', 'job callback result lost');
$GLOBALS['headers'] = $api->headers();
check(notified_header() === null, 'job context leaked after callback return');

// A job nested inside an immediate callback suppresses that callback's live time.
$api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $outer) use ($api) {
    $GLOBALS['headers'] = $outer->headers();
    $immediate = notified_header();
    check($immediate !== null, 'outer immediate timestamp missing');
    run_job('sync');
    check(notified_header() === null, 'historical job inside immediate context must omit timestamp');
    $api->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $inner) use ($api) {
        $GLOBALS['headers'] = $inner->headers();
        check(notified_header() === 321, 'job must override enclosing immediate timestamp');
    }, 321);
    $GLOBALS['headers'] = $outer->headers();
    check(notified_header() === $immediate, 'job did not restore exact outer immediate timestamp');
});
$historical = new HeaderApi();
$historical->setIsSyncing(true);
$historical->executeCallback(function (MailChimp_WooCommerce_MailChimpApi $callback_api) use ($historical) {
    check($callback_api === $historical, 'historical callback identity lost');
    $GLOBALS['headers'] = $callback_api->headers('DELETE');
    check(notified_header() === null, 'immediate historical mutation must omit timestamp');
});

echo "Object notified-at regression checks passed.\n";

<?php
/**
 * Run with wp eval-file in a disposable local WP/Woo installation with this plugin active.
 * Start eligibility-receiver.php on 127.0.0.1:18062 first. No Mailchimp account is used.
 */
if (!defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('This test requires a disposable local WP-CLI installation.');
}
function eligibility_check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
class EligibilityLoopbackApi extends MailChimp_WooCommerce_MailChimpApi {
    protected function url($extra = '', $params = null) { return 'http://127.0.0.1:18062/'; }
    public function send($method) { return $this->{strtolower($method)}('test', array('id' => 'test')); }
    public static function install($api) { self::$instance = $api; }
}
class EligibilityIntegrationJob extends Mailchimp_Woocommerce_Job {
    public $id;
    public $is_full_sync = false;
    public $retry_once = false;
    public function handle() {
        foreach (array('POST', 'PUT', 'PATCH', 'DELETE', 'GET') as $method) {
            $GLOBALS['eligibility_received'][] = $GLOBALS['eligibility_api']->send($method);
        }
        if ($this->retry_once && $this->get_attempts() === 0) { $this->retry(1901); }
    }
}
function eligibility_received($timestamp) {
    eligibility_check(count($GLOBALS['eligibility_received']) === 5, 'Expected five loopback requests');
    foreach ($GLOBALS['eligibility_received'] as $response) {
        $value = isset($response['headers']['x-object-notified-at']) ? $response['headers']['x-object-notified-at'] : null;
        $expected = $response['method'] === 'GET' || $timestamp === null ? null : (string) $timestamp;
        eligibility_check($value === $expected, 'Wrong received header for ' . $response['method']);
        eligibility_check($response['headers']['authorization'] === 'Basic ' . base64_encode('mailchimp:localtest'), 'Authentication changed');
        if (in_array($response['method'], array('POST', 'PUT', 'PATCH'), true)) {
            eligibility_check(json_decode($response['body'], true) === array('id' => 'test'), 'Payload changed');
        }
    }
    $GLOBALS['eligibility_received'] = array();
}
function eligibility_pending($id) {
    return as_get_scheduled_actions(array('hook' => 'EligibilityIntegrationJob', 'args' => array('obj_id' => $id), 'group' => 'mc-woocommerce', 'status' => ActionScheduler_Store::STATUS_PENDING), 'ids');
}
function eligibility_stored($id, $class = 'EligibilityIntegrationJob') {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mailchimp_jobs WHERE obj_id = %s AND job LIKE %s ORDER BY id DESC LIMIT 1", $id, '%' . $wpdb->esc_like($class) . '%'));
}
$GLOBALS['eligibility_received'] = array();
$GLOBALS['eligibility_api'] = new EligibilityLoopbackApi('localtest-us1');
EligibilityLoopbackApi::install($GLOBALS['eligibility_api']);
$service = MailChimp_Service::instance();
add_action('EligibilityIntegrationJob', array($service, 'mailchimp_process_single_job'));
install_mailchimp_queue();
$old_options = get_option('mailchimp-woocommerce', array());
$old_started = get_option('mailchimp-woocommerce-sync.started_at', false);
$ids = array();
try {
    Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce', array('mailchimp_api_key' => 'localtest-us1', 'mailchimp_list' => 'localtest'));
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce');
    Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-sync.started_at', 100);

    // Real WooCommerce hook -> real service callback -> serialized job -> real AS schedule.
    $coupon_id = 520620001;
    $ids[] = array($coupon_id, 'MailChimp_WooCommerce_SingleCoupon');
    do_action('woocommerce_coupon_options_save', $coupon_id, new WC_Coupon());
    $coupon = eligibility_stored($coupon_id, 'MailChimp_WooCommerce_SingleCoupon');
    eligibility_check($coupon && unserialize($coupon->job)->get_eligible_at() >= time() - 3, 'Live coupon hook did not mark its job');
    mailchimp_delete_job_by_id($coupon_id, 'MailChimp_WooCommerce_SingleCoupon');

    foreach (array(0, 301, 1801) as $delay) {
        $job = new EligibilityIntegrationJob();
        $job->id = 520621000 + $delay;
        $ids[] = array($job->id, get_class($job));
        $job->prepend_to_queue = true;
        $before = time();
        $action_id = mailchimp_as_push($job, $delay, true);
        $first = $job->get_eligible_at();
        eligibility_check($first >= $before + $delay && $first <= time() + $delay, 'Initial eligibility ignored delay');
        eligibility_check(ActionScheduler::store()->fetch_action($action_id)->get_schedule()->get_date()->getTimestamp() === 100, 'Priority scheduling changed');
        eligibility_check(unserialize(eligibility_stored($job->id)->job)->get_eligible_at() === $first, 'SQL round-trip lost metadata');
        ActionScheduler::runner()->process_action($action_id, 'eligibility-test');
        eligibility_received($first);
        eligibility_check(ActionScheduler::store()->get_status($action_id) === ActionScheduler_Store::STATUS_COMPLETE, 'AS completion failed');
        eligibility_check(eligibility_stored($job->id) === null, 'Processed row not deleted');
    }

    // Simulate jobs whose first eligibility was over 5/30 minutes ago; retain it across real retries.
    foreach (array(301, 1801) as $age) {
        $job = new EligibilityIntegrationJob();
        $job->id = 520624000 + $age;
        $ids[] = array($job->id, get_class($job));
        $job->set_eligible_at(time() - $age);
        $job->retry_once = true;
        $first = $job->get_eligible_at();
        $action_id = mailchimp_as_push($job);
        ActionScheduler::runner()->process_action($action_id, 'eligibility-test');
        eligibility_received($first);
        $pending = eligibility_pending($job->id);
        eligibility_check(count($pending) === 1, 'Retry action missing or duplicated');
        $stored = unserialize(eligibility_stored($job->id)->job);
        eligibility_check($stored->get_eligible_at() === $first && $stored->get_attempts() === 1, 'Retry replaced metadata/attempt count');
        // Reschedule that pending action through the existing update path.
        mailchimp_as_push($stored, 2000);
        $pending = eligibility_pending($job->id);
        eligibility_check(count($pending) === 1, 'Reschedule changed pending action count');
        ActionScheduler::runner()->process_action(reset($pending), 'eligibility-test');
        eligibility_received($first);
        eligibility_check(eligibility_stored($job->id) === null, 'Retry payload not cleaned up');
    }

    foreach (array('manual', 'historical', 'missing', 'malformed') as $index => $kind) {
        $job = new EligibilityIntegrationJob();
        $job->id = 520628000 + $index;
        $ids[] = array($job->id, get_class($job));
        $job->is_full_sync = $kind === 'historical';
        if ($kind === 'historical') { $job->set_eligible_at(123); }
        if ($kind === 'malformed') { $job->eligible_at = '123'; }
        $action_id = mailchimp_as_push($job);
        if ($kind === 'missing') {
            global $wpdb;
            $legacy = 'O:25:"EligibilityIntegrationJob":1:{s:2:"id";i:' . $job->id . ';}';
            $wpdb->update($wpdb->prefix . 'mailchimp_jobs', array('job' => $legacy), array('id' => eligibility_stored($job->id)->id));
        }
        ActionScheduler::runner()->process_action($action_id, 'eligibility-test');
        eligibility_received(null);
        eligibility_check(Mailchimp_Woocommerce_Job::active_eligible_at() === null, 'Worker context leaked');
    }
    $direct = mailchimp_call_live_hook(array($GLOBALS['eligibility_api'], 'send'), array('DELETE'));
    eligibility_check(isset($direct['headers']['x-object-notified-at']), 'Direct live DELETE missing timestamp');
    $manual = $GLOBALS['eligibility_api']->send('DELETE');
    eligibility_check(!isset($manual['headers']['x-object-notified-at']), 'Direct live metadata leaked');
    global $wp_version;
    echo 'Integration passed: PHP ' . PHP_VERSION . ', WordPress ' . $wp_version . ', WooCommerce ' . WC_VERSION . ".\n";
} finally {
    foreach ($ids as $entry) { mailchimp_delete_job_by_id($entry[0], $entry[1]); }
    Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce', $old_options);
    Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-sync.started_at', $old_started);
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce');
}

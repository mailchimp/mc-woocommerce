<?php
/** Run with: php tests/regression/cart-queue-cleanup.php */
// Load only the queue helper, avoiding WordPress bootstrap side effects.
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
$start = strpos($bootstrap, 'function mailchimp_delete_job_by_id(');
$end = strpos($bootstrap, '/**', $start);
eval(substr($bootstrap, $start, $end - $start));
class MailChimp_WooCommerce_Options {}
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-service.php';
function mailchimp_log(...$args) {}
function mailchimp_carts_disabled() { return false; }
function mailchimp_is_configured() { return true; }
function mailchimp_email_is_privacy_protected($email) { return false; }
function mailchimp_hash_trim_lower($email) { return md5(strtolower(trim($email))); }
function doing_action($action) { return false; }
function wp_get_current_user() { return (object) array('ID' => 0, 'user_email' => ''); }
function as_unschedule_all_actions($hook, $args, $group) {
    if ($GLOBALS['cancel_throws']) { throw new RuntimeException('scheduler unavailable'); }
    $GLOBALS['actions'] = array_filter($GLOBALS['actions'], function ($action) use ($hook, $args, $group) {
        return $action !== array($hook, $args, $group);
    });
}
class QueueDatabase {
    public $prefix = 'wp_';
    public $jobs = array();
    public $fail = false;
    public function esc_like($text) { return addcslashes($text, '_%\\'); }
    public function prepare($sql, ...$args) { return array($sql, $args); }
    public function query($query) {
        if ($this->fail) { return false; }
        list($sql, $args) = $query;
        if (strpos($sql, 'mailchimp_jobs') === false) { return 1; }
        list($id, $pattern) = $args;
        $prefix = stripcslashes(substr($pattern, 0, -1));
        $before = count($this->jobs);
        $this->jobs = array_filter($this->jobs, function ($job) use ($id, $prefix) {
            return $job[0] !== $id || strpos($job[1], $prefix) !== 0;
        });
        return $before - count($this->jobs);
    }
}
class CartCleanupService extends MailChimp_Service {
    public $is_admin = false;
    public $has_cart = true;
    public $remote_calls = 0;
    public $remote_throws = false;
    public function __construct() { $this->validated_cart_db = true; $this->cart_token = str_repeat('a', 32); }
    public function getCurrentUserEmail() { return 'test@example.test'; }
    public function getUniqueStoreID() { return 'store'; }
    protected function getCart($uid) { return $this->has_cart ? (object) array('id' => $uid, 'token' => str_repeat('a', 32)) : false; }
    public function api() { return $this; }
    public function deleteCartByID($store, $id) {
        ++$this->remote_calls;
        if ($this->remote_throws) { throw new RuntimeException('remote unavailable'); }
        return true;
    }
    public function deleteForOrder($uid) { return $this->deleteCart($uid); }
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function seed() {
    $GLOBALS['cancel_throws'] = false;
    $GLOBALS['wpdb'] = new QueueDatabase();
    $id = mailchimp_hash_trim_lower('test@example.test');
    $class = 'MailChimp_WooCommerce_Cart_Update';
    $payload = 'O:' . strlen($class) . ':"' . $class . '":0:{}';
    $GLOBALS['wpdb']->jobs = array(array($id, $payload), array($id, $payload), array('other', $payload), array($id, 'O:8:"stdClass":1:{' . $class . '}'));
    $action = array($class, array('obj_id' => $id), 'mc-woocommerce');
    $GLOBALS['actions'] = array($action, $action, array($class, array('obj_id' => 'other'), 'mc-woocommerce'), array($class, array('obj_id' => $id), 'other-group'));
    return $id;
}
$id = seed();
check(mailchimp_delete_job_by_id($id, 'MailChimp_WooCommerce_Cart_Update'), 'Cleanup failed');
check(count($GLOBALS['actions']) === 2, 'Cancel all matching actions, preserve other object/group');
check(count($GLOBALS['wpdb']->jobs) === 2, 'Delete all matching payloads, preserve other object/class');
check(mailchimp_delete_job_by_id($id, 'MailChimp_WooCommerce_Cart_Update'), 'Repeated cleanup must be harmless');
check(!mailchimp_delete_job_by_id(null, null), 'Invalid keys must not delete anything');
$id = seed();
$GLOBALS['cancel_throws'] = true;
check(!mailchimp_delete_job_by_id($id, 'MailChimp_WooCommerce_Cart_Update'), 'Report scheduler failure');
check(count($GLOBALS['wpdb']->jobs) === 2, 'Persisted payload cleanup must survive scheduler failure');
$id = seed();
$GLOBALS['wpdb']->fail = true;
check(!mailchimp_delete_job_by_id($id, 'MailChimp_WooCommerce_Cart_Update'), 'Report database failure');
foreach (array(true, false) as $has_cart) {
    seed();
    $service = new CartCleanupService();
    $service->has_cart = $has_cart;
    $service->handleCartEmptied();
    check(count($GLOBALS['wpdb']->jobs) === 2, 'Empty cart must clear jobs even without a local cart');
    check($service->remote_calls === ($has_cart ? 1 : 0), 'Missing cart must not introduce remote calls');
}
seed();
$service = new CartCleanupService();
$service->remote_throws = true;
try { $service->handleCartEmptied(); } catch (RuntimeException $e) {}
check(count($GLOBALS['wpdb']->jobs) === 2, 'Remote failure must not preserve queued snapshots');
seed();
$service = new CartCleanupService();
$service->handleCartEmptied(false);
check(count($GLOBALS['wpdb']->jobs) === 4, 'Session teardown must preserve queued carts');
$id = seed();
$service = new CartCleanupService();
$service->deleteForOrder($id);
check(count($GLOBALS['wpdb']->jobs) === 2, 'Order cleanup must discard queued carts');
echo "Cart queue cleanup regression checks passed.\n";

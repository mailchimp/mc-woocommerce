<?php
/** Run with: php tests/regression/cart-consent.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// CVE-2026-102921: the cart tracker's `subscribed` flag arrives with an anonymous request naming any
// address. Claiming an unsaved cart for that address is allowed (guest checkout works the same way), but
// a cart carries no consent at all: subscribed status is captured when the order is placed, or by an
// admin. The cart job never sends anything but transactional, whatever the request or the shopper says.
error_reporting(E_ALL & ~E_DEPRECATED);
define('DOING_AJAX', true);
class MailChimp_WooCommerce_Options { protected function doingAjax() { return defined('DOING_AJAX') && DOING_AJAX; } }
class MailChimp_WooCommerce_Cart_Update {
    public $prefix = 'wp_';
    public $prepend_to_queue = false;
    public $token;
    public function __construct(...$args) { $this->args = $args; }
    public function setCartToken($token) { $this->token = $token; return $this; }
    public function __call($method, $args) { throw new RuntimeException("the cart job must not be told a status ({$method})"); }
}
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-service.php';

function mailchimp_hash_trim_lower($str) { return md5(trim(strtolower($str))); }
function mailchimp_log(...$args) {}
function mailchimp_debug(...$args) {}
function mailchimp_carts_disabled() { return false; }
function mailchimp_is_configured() { return true; }
function mailchimp_email_is_privacy_protected($email) { return false; }
function mailchimp_carts_subscribers_only() { return false; }
function mailchimp_submit_subscribed_only() { return false; }
function mailchimp_allowed_to_use_cookie($cookie) { return true; }
function mailchimp_delete_job_by_id($id, $class) { return true; }
function mailchimp_handle_or_queue_live($job, $delay = 0) { $GLOBALS['queued'][] = $job; }
function mailchimp_set_cookie($name, $value, ...$args) { $GLOBALS['cookies'][$name] = $value; }
function wp_get_current_user() { return $GLOBALS['current_user']; }
function get_current_user_id() { return $GLOBALS['current_user']->ID; }
function wp_generate_password($length, $special) { return str_repeat('n', $length); }
function doing_action($action) { return false; }
function get_transient($key) { return false; }
function set_transient(...$args) { return true; }
function delete_transient($key) { return true; }
function maybe_serialize($data) { return serialize($data); }
function get_locale() { return 'en_US'; }
function sanitize_text_field($value) { return $value; }
function wp_unslash($value) { return $value; }
function WC() { return $GLOBALS['woo']; }

class FakeSession {
    public $data = array('cart' => array('item' => array('product_id' => 7, 'quantity' => 1)));
    public function get($key, $default = null) { return $this->data[$key] ?? $default; }
    public function set($key, $value) { $this->data[$key] = $value; }
    public function get_customer_id() { return "t_session"; }
}
class FakeCartsTable {
    public $prefix = 'wp_';
    public $rows = array();
    public function suppress_errors() {}
    public function prepare($sql, ...$args) { return array($sql, is_array($args[0]) ? $args[0] : $args); }
    public function get_row($query) { return isset($this->rows[$query[1][0]]) ? (object) $this->rows[$query[1][0]] : null; }
    public function insert($table, $row) { $this->rows[$row['id']] = $row; return 1; }
    public function query($query) {
        list($sql, $args) = $query;
        if (strpos($sql, 'DELETE') === 0) { unset($this->rows[$args[0]]); }
        if (strpos($sql, 'UPDATE') === 0) {
            list($cart, $email, $user_id, $token, $id) = $args;
            $this->rows[$id] = array_merge($this->rows[$id], compact('cart', 'email', 'user_id', 'token'));
        }
        return 1;
    }
}
// respondJSON() exits the request; surface the response instead so the script can keep going.
class Responded extends Exception { public $data; public function __construct($data) { $this->data = $data; parent::__construct(); } }
class ConsentService extends MailChimp_Service {
    public function __construct() { $this->validated_cart_db = true; $this->is_admin = false; }
    public function api() { return $this; }
    public function getUniqueStoreID() { return "store"; }
    public function deleteCartByID($store, $id) { return true; }
    protected function respondJSON($data) { throw new Responded($data); }
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

const TARGET = 'target@example.com';

// the attacker's curl: an admin-ajax POST naming the address, with whatever cookies the browser holds.
function set_user_by_email(array $post, $user = null, $token_cookie = null) {
    $GLOBALS['wpdb'] = $GLOBALS['wpdb'] ?? new FakeCartsTable();
    $GLOBALS['woo'] = (object) array('session' => new FakeSession(), 'cart' => null);
    $GLOBALS['current_user'] = $user ?: (object) array('ID' => 0, 'user_email' => '');
    $GLOBALS['cookies'] = array();
    $GLOBALS['queued'] = array();
    $_COOKIE = array_filter(array('mailchimp_cart_token' => $token_cookie));
    $_POST = $post;
    try {
        (new ConsentService())->set_user_by_email();
    } catch (Responded $r) {
        return $r->data;
    }
    throw new RuntimeException('set_user_by_email must respond');
}
function queued_once() {
    check(count($GLOBALS['queued']) === 1, 'exactly one cart update must be queued');
    return $GLOBALS['queued'][0];
}
function logged_in_as($email) { return (object) array('ID' => 5, 'user_email' => $email); }

// --- the report: an address with no saved cart, an anonymous request, subscribed=1 ------------------
$GLOBALS['wpdb'] = new FakeCartsTable();
$response = set_user_by_email(array('email' => TARGET, 'subscribed' => '1'));
check($response['success'] === true, 'claiming an unsaved address is still accepted, like a guest checkout');
check(isset($GLOBALS['wpdb']->rows[md5(TARGET)]), 'the first owner still gets a saved cart row');
queued_once(); // reaching here means the job was queued without a status - __call() throws otherwise

// the same browser owns that cart now (it holds the token) - cart ownership is still not consent.
$token = $GLOBALS['cookies']['mailchimp_cart_token'];
set_user_by_email(array('email' => TARGET, 'subscribed' => '1'), null, $token);
queued_once();

// not even the shopper logged in to that address: consent is captured at the time of the order.
set_user_by_email(array('email' => TARGET, 'subscribed' => '1'), logged_in_as(TARGET), $token);
queued_once();

// --- the job itself can never say subscribed, so stale queued jobs can't either ---------------------
$job = file_get_contents(dirname(__DIR__, 2) . '/includes/processes/class-mailchimp-woocommerce-cart-update.php');
check(strpos($job, 'function setStatus') === false, 'the cart job must not accept a status');
check(strpos($job, '->setOptInStatus(false)') !== false, 'the cart customer must be posted with opt_in_status false');
check(strpos($job, "mailchimp_member_data_update(\$this->email, \$this->user_language, 'cart', 'transactional')") !== false, 'the cart member update must be transactional');
check(strpos($job, '$this->status') === false, 'nothing in the cart job may read a status');
check(strpos($job, "->update(\$list_id, \$email, 'transactional', [], [], null, null, true)") !== false, 'the not-found retry must add the contact with status_if_new only');
check(preg_match('/->update\([^;]*\btrue\)\s*;/', $job) === 1 && preg_match_all('/->update\(/', $job) === 1, 'every member write in the cart job must be status_if_new');
// the member write for the cart caller carries only status_if_new too
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
$fn = substr($bootstrap, strpos($bootstrap, 'function mailchimp_member_data_update('));
$fn = substr($fn, 0, strpos($fn, "\n}\n"));
check(preg_match('/->update\(\s*\$list_id,\s*\$user_email,\s*\$status_if_new,[^;]*\$caller === \'cart\'\s*\)/s', $fn) === 1, 'mailchimp_member_data_update must send status_if_new for the cart caller');

// --- and the service no longer reads the flag at all ------------------------------------------------
$service = file_get_contents(dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-service.php');
check(strpos($service, "_POST['subscribed']") === false, 'the cart tracker must not read a subscribed flag');
check(strpos($service, 'cart_subscribe') === false, 'no cart subscribe state may remain');

echo "Cart consent regression checks passed.\n";

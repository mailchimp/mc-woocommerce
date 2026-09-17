<?php
/** Run with: php tests/regression/cart-ownership.php */
// Saved carts are keyed on md5(email). The email alone must never let a request read, overwrite,
// or delete someone else's cart - only the cart's token (or being logged in as that email) can.
error_reporting(E_ALL & ~E_DEPRECATED);
class MailChimp_WooCommerce_Options {}
class MailChimp_WooCommerce_Cart_Update {
    public $prepend_to_queue = false;
    public $token;
    public function __construct(...$args) { $this->args = $args; }
    public function setStatus($status) { return $this; }
    public function setCartToken($token) { $this->token = $token; return $this; }
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
function mailchimp_delete_job_by_id($id, $class) { $GLOBALS['deleted_jobs'][] = $id; return true; }
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
class OwnershipService extends MailChimp_Service {
    public $remote_deletes = array();
    public function __construct() { $this->validated_cart_db = true; $this->is_admin = false; }
    public function api() { return $this; }
    public function getUniqueStoreID() { return "store"; }
    public function setLandingSiteCookie() { return $this; }
    public function deleteCartByID($store, $id) { $this->remote_deletes[] = $id; return true; }
    public function update($updated = null) { return $this->handleCartUpdated($updated); }
    public function emptied() { return $this->handleCartEmptied(); }
    public function orderPlaced() { $this->clearCartData(); }
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

const VICTIM = 'victim@example.com';
const VICTIM_TOKEN = 'VictimToken0000000000000000000ab';
const ATTACKER_TOKEN = 'AttackerToken00000000000000000cd';

// a fresh request: the victim already has a saved cart owned by VICTIM_TOKEN.
function request($email_cookie, $token_cookie = null, $user = null, array $get = array()) {
    $GLOBALS['wpdb'] = $GLOBALS['wpdb'] ?? new FakeCartsTable();
    $GLOBALS['woo'] = (object) array('session' => new FakeSession(), 'cart' => null);
    $GLOBALS['current_user'] = $user ?: (object) array('ID' => 0, 'user_email' => '');
    $GLOBALS['cookies'] = array();
    $GLOBALS['queued'] = array();
    $GLOBALS['deleted_jobs'] = array();
    $_COOKIE = array_filter(array('mailchimp_user_email' => $email_cookie, 'mailchimp_cart_token' => $token_cookie));
    $_GET = $get;
    return new OwnershipService();
}
function seed_victim($token = VICTIM_TOKEN) {
    $GLOBALS['wpdb'] = new FakeCartsTable();
    $row = array('id' => md5(VICTIM), 'email' => VICTIM, 'user_id' => 0, 'cart' => serialize(array('secret' => array('product_id' => 99))), 'created_at' => 'x');
    if ($token) { $row['token'] = $token; }
    $GLOBALS['wpdb']->rows[md5(VICTIM)] = $row;
}
function victim_row() { return $GLOBALS['wpdb']->rows[md5(VICTIM)] ?? null; }

// --- write side: an attacker naming the victim's email changes nothing -------------------------
seed_victim();
$service = request(VICTIM, ATTACKER_TOKEN);
$service->update();
check(victim_row()['cart'] === serialize(array('secret' => array('product_id' => 99))), 'attacker must not overwrite the victim cart');
check(empty($service->remote_deletes) && empty($GLOBALS['queued']), 'attacker must not touch the Mailchimp cart or queue a job');

$service = request(VICTIM);
$service->emptied();
check(victim_row() !== null && empty($service->remote_deletes), 'emptying an attacker cart must not delete the victim cart');

$service = request(VICTIM);
$service->orderPlaced();
check(victim_row() !== null, 'an order under a spoofed cookie must not delete the victim cart');

// the previous-email cookie is attacker controlled too
$service = request('attacker@example.com', ATTACKER_TOKEN);
$_COOKIE['mailchimp_user_previous_email'] = VICTIM;
$service->update();
check(victim_row() !== null && !in_array(md5(VICTIM), $service->remote_deletes, true), 'previous-email swap must not delete the victim cart');
check(isset($GLOBALS['wpdb']->rows[md5('attacker@example.com')]), 'the attacker can still track their own cart');

// legacy rows saved before the token existed can't be claimed by a guest
seed_victim(null);
$service = request(VICTIM, ATTACKER_TOKEN);
$service->update();
check(!isset(victim_row()['token']) && empty($GLOBALS['queued']), 'legacy row must not be claimed by a guest');

// --- the owner keeps working, on any device that holds the token ------------------------------
seed_victim();
$service = request(VICTIM, VICTIM_TOKEN);
$service->update();
check(victim_row()['cart'] !== serialize(array('secret' => array('product_id' => 99))), 'owner can update their cart');
check($GLOBALS['queued'][0]->token === VICTIM_TOKEN, 'the recovery link carries the existing token');

seed_victim();
$service = request(VICTIM, VICTIM_TOKEN);
$service->emptied();
check(victim_row() === null && $service->remote_deletes === array(md5(VICTIM)), 'owner can empty their cart');

// logged in as the account that owns the email - no token needed
seed_victim(null);
$service = request(null, null, (object) array('ID' => 5, 'user_email' => VICTIM));
$service->update();
check(victim_row()['token'] === str_repeat('n', 32) && $GLOBALS['cookies']['mailchimp_cart_token'] === str_repeat('n', 32), 'logged-in owner claims a legacy row and gets a token');

// an unclaimed address is claimed by whoever saves it first, with a fresh token
$GLOBALS['wpdb'] = new FakeCartsTable();
$service = request('new@example.com');
$service->update();
$row = $GLOBALS['wpdb']->rows[md5('new@example.com')];
check($row['token'] === str_repeat('n', 32) && $GLOBALS['cookies']['mailchimp_cart_token'] === $row['token'], 'first save mints and cookies a token');

// --- read side: the recovery link only works with the right token -----------------------------
foreach (array('no token' => array('mc_cart_id' => md5(VICTIM)), 'wrong token' => array('mc_cart_id' => md5(VICTIM), 'mc_cart_token' => ATTACKER_TOKEN)) as $case => $get) {
    seed_victim();
    $service = request(null, null, null, $get);
    $service->handleCampaignTracking();
    check(!isset($GLOBALS['cookies']['mailchimp_user_email']), "$case: must not reveal the email");
    check(!isset($GLOBALS['woo']->session->data['cart']['secret']), "$case: must not load the victim cart");
}

seed_victim(null);
$service = request(null, null, null, array('mc_cart_id' => md5(VICTIM), 'mc_cart_token' => ''));
$service->handleCampaignTracking();
check(!isset($GLOBALS['cookies']['mailchimp_user_email']), 'legacy row with an empty token must not match an empty token');

seed_victim();
$service = request(null, null, null, array('mc_cart_id' => md5(VICTIM), 'mc_cart_token' => VICTIM_TOKEN));
$service->handleCampaignTracking();
check($GLOBALS['cookies']['mailchimp_user_email'] === VICTIM, 'valid link identifies the shopper');
check($GLOBALS['cookies']['mailchimp_cart_token'] === VICTIM_TOKEN, 'valid link hands this device the token');
check(isset($GLOBALS['woo']->session->data['cart']['secret']), 'valid link restores the cart on another device');

// saved carts never unserialize objects
seed_victim();
$GLOBALS['wpdb']->rows[md5(VICTIM)]['cart'] = 'a:1:{s:1:"x";O:8:"stdClass":0:{}}';
$service = request(null, null, null, array('mc_cart_id' => md5(VICTIM), 'mc_cart_token' => VICTIM_TOKEN));
$service->handleCampaignTracking();
check($GLOBALS['woo']->session->data['cart']['x'] instanceof __PHP_Incomplete_Class, 'saved cart objects must not be instantiated');

// --- the hash lookup endpoint is gone ----------------------------------------------------------
check(!method_exists('MailChimp_Service', 'get_user_by_hash'), 'get_user_by_hash must be removed');
check(strpos(file_get_contents(dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce.php'), 'get_user_by_hash') === false, 'hash lookup must not be registered');

echo "Cart ownership regression checks passed.\n";

<?php
/** Product-repair retries must retain the parent cart/order eligibility. */
$root = dirname(__DIR__, 2);
require $root . '/includes/processes/class-mailchimp-woocommerce-job.php';
require $root . '/includes/processes/class-mailchimp-woocommerce-cart-update.php';
require $root . '/includes/api/class-mailchimp-api.php';
function mailchimp_as_push($job, $delay = 0) { $GLOBALS['retried'][] = $job; }
function mailchimp_is_configured() { return true; }
function mailchimp_get_api() { return $GLOBALS['api']; }
function mailchimp_get_store_id() { return 'test'; }
function wc_get_checkout_url() { return 'https://example.test/checkout'; }
function mailchimp_log() {}
function mailchimp_debug() {}
function mailchimp_error() {}
function mailchimp_member_data_update() {}
function mailchimp_set_transient() {}
function mailchimp_string_contains($haystack, $needles) {
    foreach ((array) $needles as $needle) { if (strpos($haystack, $needle) !== false) { return true; } }
    return false;
}
class MailChimp_WooCommerce_Customer {
    public function setId($id) {}
    public function setEmailAddress($email) {}
    public function setOptInStatus($status) {}
    public function getEmailAddress() { return 'test@example.test'; }
}
class MailChimp_WooCommerce_Cart {
    public function setId($id) {}
    public function setSessionId($id) {}
    public function setCheckoutUrl($url) {}
    public function setCurrencyCode() {}
    public function setCustomer($customer) {}
    public function addItem($line) {}
    public function setOrderTotal($total) {}
}
class FallbackLine {
    public function getPrice() { return 10; }
    public function getProductId() { return 123; }
    public function getFallbackTitle() { return 'Test'; }
    public function getId() { return 'line'; }
}
class MailChimp_WooCommerce_Order {
    public function items() { return array(new FallbackLine()); }
}
class MailChimp_WooCommerce_Single_Product extends Mailchimp_Woocommerce_Job {
    public function __construct($id, $title = null) {}
    public function createModeOnly() { return $this; }
    public function fromOrderItem($item) { return $this; }
    public function api() { return $GLOBALS['api']; }
    public function handle() { $this->retry(); }
}
class FallbackApi {
    private $cart_calls = 0;
    public function deleteCartByID($store, $id) { return true; }
    public function getStoreProduct($store, $id) { return false; }
    public function addCart($store, $cart, $flag) {
        if (++$this->cart_calls === 1) { throw new RuntimeException('product not found'); }
        return true;
    }
}
class FallbackCart extends MailChimp_WooCommerce_Cart_Update {
    protected function transformLineItem($hash, $item) { return new FallbackLine(); }
}
foreach (array(null, 123) as $timestamp) {
    $GLOBALS['retried'] = array();
    $GLOBALS['api'] = new FallbackApi();
    $cart = new FallbackCart('cart', 'test@example.test', array(array('quantity' => 1)));
    $cart->set_eligible_at($timestamp);
    $cart->handle_with_context();
    $api = new MailChimp_WooCommerce_MailChimpApi();
    Mailchimp_Woocommerce_Job::with_eligible_at($timestamp, array($api, 'handleProductsMissingFromAPI'), array(new MailChimp_WooCommerce_Order()));
    if (count($GLOBALS['retried']) !== 2) { throw new RuntimeException('Cart/order repair retries were not exercised'); }
    foreach ($GLOBALS['retried'] as $job) {
        if ($job->get_eligible_at() !== $timestamp) { throw new RuntimeException('Product repair lost parent eligibility'); }
    }
    if (Mailchimp_Woocommerce_Job::active_eligible_at() !== null) { throw new RuntimeException('Repair context leaked'); }
}
echo "Inline product repair eligibility checks passed.\n";

<?php
/** Run with: php tests/regression/campaign-query-guards.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
class MailChimp_WooCommerce_Options {}
function mailchimp_allowed_to_use_cookie($key) { return true; }
function mailchimp_set_cookie($key, $value, $duration, $path) { $GLOBALS['cookies'][$key] = $value; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return is_string($value) ? trim($value) : ''; }
function WC() { return $GLOBALS['woo']; }
function trailingslashit($value) { return rtrim($value, '/') . '/'; }
function rest_url() { return 'https://example.test/wp-json/'; }
function add_query_arg($args) { return $GLOBALS['current_url']; }
function wp_parse_url($url) { return parse_url($url); }
class WP_Rewrite {}
require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-service.php';
class CampaignQueryService extends MailChimp_Service {
    public function setLandingSiteCookie() { return $this; }
    public function getCookieDuration($time = 'thirty_days') { return 1; }
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$service = new CampaignQueryService();
$GLOBALS['woo'] = (object) array('session' => null);
$GLOBALS['current_url'] = 'https://example.test/shop/';
foreach (array(array('1'), array('nested' => array('1')), null, 123) as $value) {
    $_GET = array('mc_eid' => $value, 'rest_route' => $value);
    $GLOBALS['cookies'] = array();
    $service->handleCampaignTracking();
    check(!isset($GLOBALS['cookies']['mailchimp_email_id']), 'Malformed email ID must be ignored');
    check($service->is_rest() === false, 'Malformed route must not identify a normal page as REST');
}
$_GET = array('mc_eid' => ' abc123 ', 'mc_cid' => 'campaign123');
$service->handleCampaignTracking();
check($GLOBALS['cookies']['mailchimp_email_id'] === 'abc123', 'Valid email ID must retain trimming');
check($GLOBALS['cookies']['mailchimp_campaign_id'] === 'campaign123', 'Cold-session attribution cookie must survive');
$GLOBALS['woo']->session = new class {
    public $values = array('cart' => 'existing cart');
    public function set($key, $value) { $this->values[$key] = $value; }
};
$service->handleCampaignTracking();
check($GLOBALS['woo']->session->values['mc_cid'] === 'campaign123', 'Warm session must retain attribution');
check($GLOBALS['woo']->session->values['cart'] === 'existing cart', 'Warm cart must survive');
$_GET = array('rest_route' => '/wp/v2/posts');
check($service->is_rest() === true, 'Valid query route must be detected');
$_GET = array('rest_route' => array('bad'));
$GLOBALS['current_url'] = 'https://example.test/wp-json/wc/store/v1/cart';
check($service->is_rest() === true, 'Malformed query must not disable REST path detection');
define('REST_REQUEST', true);
check($service->is_rest() === true, 'REST_REQUEST must retain precedence');
echo "Campaign query regression checks passed.\n";

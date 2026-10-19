<?php
/** MC_WOO_GDPR_LOCAL_QA=1 wp eval-file tests/integration/gdpr-fields-checkout.php */
if (PHP_SAPI !== 'cli') { exit; }
if (getenv('MC_WOO_GDPR_LOCAL_QA') !== '1' ||
    !in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true) ||
    mailchimp_is_configured() || wp_using_ext_object_cache()) {
    throw new RuntimeException('Requires an unconfigured disposable localhost site without external object cache.');
}

// Only the remote API response is substituted. Render the real classic and
// Blocks integrations, and run the real Action Scheduler callback against SQL.
class GDPR_Checkout_Local_Api extends MailChimp_WooCommerce_MailChimpApi {
    public $calls = 0;
    public $response;
    public static function install() { return static::$instance = new self(); }
    public static function reset() { static::$instance = null; }
    public function get($path, $params = array()) {
        if (strpos($path, '/members?fields=members.marketing_permissions&count=1') === false) {
            throw new RuntimeException('Unexpected API request in local GDPR test');
        }
        ++$this->calls;
        usleep(1500000); // A slow response must affect only the worker.
        if ($this->response instanceof Exception) { throw $this->response; }
        return $this->response;
    }
    protected function url($extra = '', $params = null) { throw new RuntimeException('Network disabled'); }
}
class GDPR_Checkout_Local_Admin extends MailChimp_WooCommerce_Admin {
    public function prime($id) { $this->updateGDPRFields($id); }
}
require_once dirname(__DIR__, 2) . '/blocks/woocommerce-blocks-integration.php';
$checks = 0;
$check = function($condition, $message) use (&$checks) { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$hook = MailChimp_WooCommerce_GDPR_Fields::HOOK;
$list = 'local-gdpr-' . wp_generate_password(8, false);
$key = "mailchimp-woocommerce-gdpr-fields.{$list}";
$options = function($list_id, $default = 'check') {
    wp_cache_set('mailchimp-woocommerce-options', array('mailchimp_api_key' => 'local-fixture', 'mailchimp_list' => $list_id,
        'mailchimp_checkbox_defaults' => $default), 'mailchimp-woocommerce');
    MailChimp_Newsletter::instance()->resetOptions();
};
$pending = function($id) use ($hook) {
    return as_get_scheduled_actions(array('hook' => $hook, 'args' => array($id, 5), 'group' => 'mc-woocommerce',
        'status' => ActionScheduler_Store::STATUS_PENDING, 'per_page' => -1), 'ids');
};
$render = function() {
    ob_start(); MailChimp_Newsletter::instance()->applyNewsletterField(null); $html = ob_get_clean();
    return array($html, (new Mailchimp_Woocommerce_Newsletter_Blocks_Integration())->get_script_data());
};
$run = function() use ($pending, $list, $check) {
    $ids = $pending($list); $check(count($ids) === 1, 'Exactly one pending refresh');
    ActionScheduler_QueueRunner::instance()->process_action($ids[0], 'GDPR checkout integration');
    $check(ActionScheduler::store()->get_status($ids[0]) === ActionScheduler_Store::STATUS_COMPLETE, 'Scheduler completes the refresh');
};
$no_http = function() { return new WP_Error('local_qa', 'External HTTP disabled'); };
$no_mail = function() { return false; };
add_filter('pre_http_request', $no_http); add_filter('pre_wp_mail', $no_mail);
$api = GDPR_Checkout_Local_Api::install();
$raw = array(array('marketing_permission_id' => '123', 'text' => 'Email', 'enabled' => true));
$expected = $raw; $expected[0]['enabled'] = false;
$api->response = array('members' => array(array('marketing_permissions' => $raw)));
try {
    $options($list, 'hide');
    list($html, $data) = $render();
    $check($html === '' && $data['gdprStatus'] === 'hide' && !$pending($list), 'Hidden fields do not queue refreshes');
    $options($list);
    $start = microtime(true);
    for ($i = 0; $i < 3; ++$i) { list($html, $data) = $render(); }
    $check($api->calls === 0 && microtime(true) - $start < 1.5, 'Cold classic and Blocks reads do not wait for slow API');
    $check(strpos($html, 'checked="checked"') === false && $data['gdprStatus'] === 'uncheck', 'Unknown fields never default the shopper into marketing');
    $check(strpos($html, 'mailchimp_woocommerce_gdpr[') === false && empty($data['gdprFields']), 'Cold checkout invents no permissions');
    (new GDPR_Checkout_Local_Admin())->prime($list);
    $check(count($pending($list)) === 1 && $api->calls === 0, 'Admin priming shares the deduplicated background refresh');
    $run();
    $check($api->calls === 1 && get_option($key . '.last-good') === $expected, 'Real worker saves safe field definitions');
    list($html, $data) = $render();
    $check($data['gdprFields'] === $expected && $data['gdprStatus'] === 'check', 'Known fields restore configured newsletter default');
    $check(strpos($html, "value='1' checked") === false, 'Classic permission checkbox never copies the sampled member consent');
    $check(strpos($html, "value='0' name='mailchimp_woocommerce_gdpr[123]'") !== false, 'Classic unchecked permissions submit an explicit zero');
    $check(!$pending($list) && $api->calls === 1, 'Fresh reads schedule no work');

    delete_transient($key); $api->response = new RuntimeException('Simulated slow outage');
    $start = microtime(true); list($html, $data) = $render();
    $check($api->calls === 1 && microtime(true) - $start < 1.5 && $data['gdprFields'] === $expected, 'Expired checkout uses stale fields immediately');
    $run();
    $check(get_option($key . '.last-good') === $expected && get_transient($key) === false, 'Failed refresh preserves last-good without inventing an empty success');
    $render(); $check(!$pending($list), 'Failed refresh backs off before retrying');

    delete_transient($key . '.retry'); $api->response = array('members' => array());
    $render(); $run();
    list($html, $data) = $render();
    $check(empty($data['gdprFields']) && $data['gdprStatus'] === 'check', 'Known empty response is distinct from unavailable fields');

    $options($list . '-new'); $render();
    $check(count($pending($list . '-new')) === 1, 'New audience primes its own fields');
    $check($api->getCachedGDPRFields($list . '-new') === null, 'New audience does not inherit previous audience fields');
    $calls = $api->calls; delete_transient($key);
    do_action($hook, $list, 5);
    $check($api->calls === $calls, 'Obsolete audience jobs never contact the API');
    $options(''); do_action($hook, $list . '-new', 5);
    $check($api->calls === $calls, 'Disconnected stores never refresh');
    echo json_encode(array('checks' => $checks, 'wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION, 'php' => PHP_VERSION, 'result' => 'passed')) . "\n";
} finally {
    foreach (array($list, $list . '-new') as $id) {
        $cache_key = "mailchimp-woocommerce-gdpr-fields.{$id}";
        delete_transient($cache_key); delete_transient($cache_key . '.retry'); delete_option($cache_key . '.last-good');
        foreach (as_get_scheduled_actions(array('hook' => $hook, 'args' => array($id, 5), 'per_page' => -1), 'ids') as $action) {
            ActionScheduler::store()->delete_action($action);
        }
    }
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce');
    MailChimp_Newsletter::instance()->resetOptions(); GDPR_Checkout_Local_Api::reset();
    remove_filter('pre_http_request', $no_http); remove_filter('pre_wp_mail', $no_mail);
}

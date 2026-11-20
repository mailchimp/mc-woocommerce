<?php
/** MC_WOO_QIT_LOCAL_QA=1 wp eval-file tests/integration/tower-woo-key-permissions.php */
if (PHP_SAPI !== 'cli') { exit; }
if (getenv('MC_WOO_QIT_LOCAL_QA') !== '1' ||
    !in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true) ||
    mailchimp_is_configured() || wp_using_ext_object_cache()) {
    throw new RuntimeException('Requires an unconfigured disposable localhost site without external object cache.');
}
require_once ABSPATH . 'wp-admin/includes/user.php';
class QIT_Local_Count_Api extends MailChimp_WooCommerce_MailChimpApi {
    public $calls = 0;
    public static function install() { return static::$instance = new self(); }
    public static function reset() { static::$instance = null; }
    protected function get($path, $params = null) {
        if ($path !== 'lists/qit-local-audience/members?status=subscribed&count=1') {
            throw new RuntimeException('Unexpected API request in count test');
        }
        ++$this->calls;
        return array('total_items' => 7);
    }
    protected function url($extra = '', $params = null) { throw new RuntimeException('Network disabled'); }
}
$checks = 0;
$check = function($condition, $message) use (&$checks) { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$original = array();
foreach (array('store_id', 'tower.opt', 'tower.token', 'tower.support_token') as $key) {
    $original[$key] = get_option('mailchimp-woocommerce-' . $key);
}
$verified = get_transient('mailchimp_woocommerce_store_id_verified');
$original_user = get_current_user_id();
$users = array(); $key_ids = array(); $http_calls = 0;
$http = function($pre, $args, $url) use (&$http_calls) {
    if (strpos($url, 'https://tower.vextras.com/admin-api/woocommerce/') === 0) {
        ++$http_calls;
        return array('response' => array('code' => 200), 'body' => '{"success":true}', 'headers' => array());
    }
    return new WP_Error('local_qa', 'External HTTP disabled');
};
$mail = function() { return false; };
add_filter('pre_http_request', $http, 10, 3); add_filter('pre_wp_mail', $mail);
$make_key = function($owner, $permission) use (&$key_ids, $check) {
    global $wpdb;
    $key = 'ck_' . bin2hex(random_bytes(20)); $secret = 'cs_' . bin2hex(random_bytes(20));
    $ok = $wpdb->insert($wpdb->prefix . 'woocommerce_api_keys', array('user_id' => $owner,
        'description' => 'Disposable QIT regression', 'permissions' => $permission,
        'consumer_key' => wc_api_hash($key), 'consumer_secret' => $secret,
        'truncated_key' => substr($key, -7)));
    $check($ok !== false, 'Create real WooCommerce API-key fixture');
    $key_ids[] = $wpdb->insert_id;
    return $key . ':' . $secret;
};
$dispatch = function($route, $method, $token = null, $query = false, $body = array()) {
    $request = new WP_REST_Request($method, '/mailchimp-for-woocommerce/v1/tower/' . $route);
    if ($token !== null) {
        if ($query) { $request->set_query_params(array('auth' => $token)); }
        else { $request->set_header('Authorization', 'Bearer ' . $token); }
    }
    if ($method === 'POST') { $request->set_header('Content-Type', 'application/json'); $request->set_body(wp_json_encode($body)); }
    return rest_do_request($request);
};
try {
    foreach (array('administrator', 'shop_manager', 'customer') as $role) {
        $id = wp_insert_user(array('user_login' => 'qit-' . $role . '-' . wp_generate_password(8, false),
            'user_pass' => wp_generate_password(32), 'role' => $role, 'user_email' => 'qit-' . wp_generate_password(8, false) . '@example.test'));
        $check(!is_wp_error($id), 'Create role fixture'); $users[$role] = $id;
    }
    wp_set_current_user(0);
    mailchimp_set_data('store_id', 'qit-local-store'); set_transient('mailchimp_woocommerce_store_id_verified', 1, 600);
    mailchimp_set_data('tower.opt', 1); mailchimp_set_data('tower.token', 'local-original-token');
    $read = $make_key($users['administrator'], 'read');
    $write = $make_key($users['administrator'], 'write');
    $both = $make_key($users['administrator'], 'read_write');
    $response = $dispatch('toggle_remote_support', 'POST', $read, false, array('toggle' => false));
    $check($response->get_status() === 403, 'Read-only key must not toggle remote support (received ' . $response->get_status() . ', outbound calls ' . $http_calls . ')');
    $check($http_calls === 0 && (int) mailchimp_get_data('tower.opt') === 1 && mailchimp_get_data('tower.token') === 'local-original-token', 'Denied write leaves local and remote support state untouched');

    foreach (array($write, $both) as $token) {
        foreach (array(false, true) as $toggle) {
            $before = $http_calls;
            $response = $dispatch('toggle_remote_support', 'POST', $token, false, array('toggle' => $toggle));
            $check($response->get_status() === 200 && $response->get_data()['success'] === true, 'Write-capable administrator key can toggle support');
            $check($http_calls === $before + 1 && (bool) mailchimp_get_data('tower.opt') === $toggle, 'Allowed write reaches the real handler and updates state');
        }
    }
    foreach (array(array('get_store_id', 'GET'), array('subscriber_stats', 'POST')) as $endpoint) {
        foreach (array($read, $both) as $token) {
            $response = $dispatch($endpoint[0], $endpoint[1], $token);
            $check($response->get_status() === 200, 'Read-capable key can use read-only endpoint, including POST stats');
        }
        $check($dispatch($endpoint[0], $endpoint[1], $write)->get_status() === 403, 'Write-only key cannot read store data');
    }
    // Read authorization must precede both remote counts and the local SQL query.
    $api = QIT_Local_Count_Api::install();
    wp_cache_set('mailchimp-woocommerce-options', array('mailchimp_api_key' => 'local-fixture', 'mailchimp_list' => 'qit-local-audience'), 'mailchimp-woocommerce');
    update_user_meta($users['customer'], 'mailchimp_woocommerce_is_subscribed', '1');
    $response = $dispatch('subscriber_stats', 'POST', $read);
    $check($response->get_data()['mailchimp'] === 7 && $response->get_data()['platform'] >= 1 && $api->calls === 1, 'Read key reaches real local counts with a fixture Mailchimp response');
    $check($dispatch('subscriber_stats', 'POST', $write)->get_status() === 403 && $api->calls === 1, 'Denied stats read does not call Mailchimp');
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce'); QIT_Local_Count_Api::reset();

    $customer = $make_key($users['customer'], 'read_write');
    $manager = $make_key($users['shop_manager'], 'read_write');
    foreach (array(array('toggle_remote_support', 'POST'), array('get_store_id', 'GET'), array('subscriber_stats', 'POST')) as $endpoint) {
        $before = $http_calls;
        foreach (array(null, 'invalid', 'ck_invalid:cs_invalid', array('unexpected'), str_replace('cs_', 'CS_', $both)) as $bad) {
            $response = $dispatch($endpoint[0], $endpoint[1], $bad, true, array('toggle' => false));
            $check($response->get_status() === 401, 'Missing, malformed and wrong-case credentials are unauthorized');
        }
        $check($dispatch($endpoint[0], $endpoint[1], $customer, false, array('toggle' => false))->get_status() === 403, 'Customer-owned key cannot access privileged plugin routes');
        $check($dispatch($endpoint[0], $endpoint[1], $manager, false, array('toggle' => false))->get_status() === 403, 'Default admin-only setting also applies to key owners');
        $check($http_calls === $before, 'Rejected requests never contact Tower');
    }
    wp_cache_set('mailchimp-woocommerce-options', array('mailchimp_permission_cap' => 'manage_woocommerce'), 'mailchimp-woocommerce');
    $check($dispatch('get_store_id', 'GET', $manager, true)->get_status() === 200, 'Configured shop-manager access works without a logged-in WP user');
    $check($dispatch('toggle_remote_support', 'POST', $manager, true, array('toggle' => false))->get_status() === 200, 'Query-token transport remains compatible for allowed writes');
    $check(get_current_user_id() === 0, 'Key authorization never impersonates or replaces the current user');

    wp_set_current_user($users['administrator']);
    $check($dispatch('get_store_id', 'GET', $customer)->get_status() === 403, 'Logged-in administrator cannot elevate a customer-owned API key');
    $check($dispatch('get_store_id', 'GET')->get_status() === 401, 'A login alone does not replace the route API key');
    wp_set_current_user(0);
    $read_only_manager = $make_key($users['shop_manager'], 'read');
    $check($dispatch('toggle_remote_support', 'POST', $read_only_manager, true, array('toggle' => true))->get_status() === 403, 'Shop-manager capability does not override a read-only key scope');
    (new WP_User($users['shop_manager']))->set_role('customer');
    $check($dispatch('get_store_id', 'GET', $manager)->get_status() === 403, 'Demoting the key owner takes effect immediately');
    $filter = function() { return 'read'; };
    add_filter('mailchimp_allowed_capability', $filter);
    $check($dispatch('get_store_id', 'GET', $customer)->get_status() === 200, 'Explicit custom plugin capability is respected');
    remove_filter('mailchimp_allowed_capability', $filter);

    $unknown_scope = $make_key($users['administrator'], 'invalid');
    $check($dispatch('get_store_id', 'GET', $unknown_scope)->get_status() === 403, 'Unknown key scope fails closed');
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', array('key_id' => $key_ids[0]));
    $check($dispatch('get_store_id', 'GET', $read)->get_status() === 401, 'Revoked keys stop working');
    $wpdb->update($wpdb->prefix . 'woocommerce_api_keys', array('user_id' => 0), array('key_id' => $key_ids[2]));
    $check($dispatch('get_store_id', 'GET', $both)->get_status() === 403, 'Orphaned key owner cannot authorize access');
    echo wp_json_encode(array('checks' => $checks, 'wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION,
        'php' => PHP_VERSION, 'result' => 'passed')) . "\n";
} finally {
    global $wpdb;
    foreach ($key_ids as $id) { $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', array('key_id' => $id)); }
    foreach ($users as $id) { wp_delete_user($id); }
    foreach ($original as $key => $value) {
        if ($value === false) { Mailchimp_Woocommerce_DB_Helpers::delete_option('mailchimp-woocommerce-' . $key); }
        else { mailchimp_set_data($key, $value); }
    }
    delete_transient('mailchimp_woocommerce_store_id_verified');
    if ($verified !== false) { set_transient('mailchimp_woocommerce_store_id_verified', $verified, 600); }
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce'); wp_set_current_user($original_user);
    remove_filter('pre_http_request', $http); remove_filter('pre_wp_mail', $mail); QIT_Local_Count_Api::reset();
}

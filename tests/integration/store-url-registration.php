<?php
/** MC_WOO_STORE_URL_LOCAL_QA=1 wp eval-file tests/integration/store-url-registration.php */
if (PHP_SAPI !== 'cli') { exit; }
if (getenv('MC_WOO_STORE_URL_LOCAL_QA') !== '1' ||
    !in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true) ||
    mailchimp_is_configured() || wp_using_ext_object_cache()) {
    throw new RuntimeException('Requires an unconfigured disposable localhost site without external object cache.');
}

// Substitute only remote responses. Exercise real WordPress URLs/options,
// WooCommerce country data, syncStore(), store serialization and API dispatch.
class Store_URL_Local_Api extends MailChimp_WooCommerce_MailChimpApi {
    public $records = array();
    public $writes = array();
    public $conflict = false;
    public $fail_write = false;
    public static function install() { return static::$instance = new self(); }
    public static function reset() { static::$instance = null; }
    protected function get($path, $params = null) {
        if ($path === 'ecommerce/stores') { return array('stores' => array_values($this->records)); }
        if (strpos($path, 'ecommerce/stores/') === 0) {
            $id = substr($path, strlen('ecommerce/stores/'));
            return isset($this->records[$id]) ? $this->records[$id] : array();
        }
        if (strpos($path, 'connected-sites/') === 0) { return array(); }
        throw new RuntimeException('Unexpected GET: ' . $path);
    }
    protected function post($path, $body) {
        if ($path !== 'ecommerce/stores') { throw new RuntimeException('Unexpected POST: ' . $path); }
        $this->writes[] = array('POST', $path, $body);
        if ($this->conflict) { throw new MailChimp_WooCommerce_Error('woocommerce already exists in the account'); }
        if ($this->fail_write) { throw new MailChimp_WooCommerce_Error('Simulated unavailable API'); }
        $this->records[$body['id']] = $body;
        return $body;
    }
    protected function patch($path, $body) {
        if ($path !== 'ecommerce/stores/' . $body['id']) { throw new RuntimeException('Unexpected PATCH: ' . $path); }
        $this->writes[] = array('PATCH', $path, $body);
        if ($this->fail_write) { throw new MailChimp_WooCommerce_Error('Simulated unavailable API'); }
        $this->records[$body['id']] = $body;
        return $body;
    }
    protected function url($extra = '', $params = null) { throw new RuntimeException('Network disabled'); }
}
class Store_URL_Local_Admin extends MailChimp_WooCommerce_Admin {
    public function __construct($api) { $this->api = $api; }
}

$checks = 0;
$check = function($condition, $message) use (&$checks) { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$original = array();
foreach (array('store_id', 'store_created_at', 'store_updated_at', 'errors.store_info') as $key) {
    $original[$key] = get_option('mailchimp-woocommerce-' . $key);
}
$verified = get_transient('mailchimp_woocommerce_store_id_verified');
$home = 'https://example.test'; $site = 'https://example.test/wp';
$home_filter = function() use (&$home) { return $home; };
$site_filter = function() use (&$site) { return $site; };
$no_http = function() { return new WP_Error('local_qa', 'External HTTP disabled'); };
$no_mail = function() { return false; };
add_filter('pre_http_request', $no_http); add_filter('pre_wp_mail', $no_mail);
add_filter('pre_option_home', $home_filter); add_filter('pre_option_siteurl', $site_filter);
$api = Store_URL_Local_Api::install();
$admin = new Store_URL_Local_Admin($api);
$data = array('mailchimp_list' => 'local-audience', 'store_name' => 'URL regression store', 'admin_email' => 'qa@example.test');
wp_cache_set('mailchimp-woocommerce-options', array('mailchimp_api_key' => 'local-fixture', 'mailchimp_list' => 'local-audience'), 'mailchimp-woocommerce');
$record = function($id, $domain, $platform = 'woocommerce', $list = 'local-audience') {
    return array('id' => $id, 'name' => 'Existing store', 'domain' => $domain, 'platform' => $platform,
        'list_id' => $list, 'currency_code' => 'USD');
};
$reset_id = function($id, $cached = false) {
    mailchimp_set_data('store_id', $id);
    delete_transient('mailchimp_woocommerce_store_id_verified');
    if ($cached) { set_transient('mailchimp_woocommerce_store_id_verified', 1, 600); }
};
try {
    foreach (array(
        array('https://example.test', 'https://example.test/wp', 'example.test'),
        array('https://example.test', 'https://example.test', 'example.test'),
        array('https://shop.example.test', 'https://core.example.test/wp', 'shop.example.test'),
        array('http://example.test:8080/shop', 'http://example.test:8080/shop/wp', 'example.test'),
    ) as $case) {
        list($home, $site, $expected) = $case;
        $reset_id('existing-store');
        $api->records = array('existing-store' => $record('existing-store', $site)); $api->writes = array();
        $check($admin->syncStore($data) === true, 'Existing store synchronizes');
        $write = $api->writes[0];
        $check($write[2]['domain'] === $expected, 'Registration must send the public host, never the WP directory: ' . $site);
        $check($write[0] === 'PATCH' && $write[1] === 'ecommerce/stores/existing-store' && count($api->records) === 1,
            'Correct the existing store in place without creating a duplicate');
        $check($write[2]['list_id'] === 'local-audience' && mailchimp_get_store_id() === 'existing-store', 'Preserve audience and store ID');
        $check(strpos(admin_url(), $site . '/wp-admin/') === 0, 'Admin stays under the WordPress installation directory');
        $check(strpos(wp_login_url(), $site . '/wp-login.php') === 0, 'Login stays under the WordPress installation directory');
        $check(home_url() === $home && site_url() === $site, 'Registration does not rewrite global WordPress URLs');

        $reset_id(false); $api->records = array(); $api->writes = array();
        $check($admin->syncStore($data) === true, 'New store registration succeeds');
        $write = $api->writes[0];
        $check(count($api->writes) === 1 && $write[0] === 'POST' && $write[2]['domain'] === $expected, 'Create uses the same public host');
        $check($write[2]['id'] === mailchimp_get_store_id() && count($api->records) === 1, 'Created ID is retained locally');
    }

    $home = 'https://example.test'; $site = 'https://example.test/wp';
    foreach (array('example.test', 'https://example.test/', 'http://example.test', 'https://example.test/wp', 'HTTPS://EXAMPLE.TEST/') as $legacy) {
        $reset_id(false);
        $api->records = array('recovered' => $record('recovered', $legacy),
            'other-platform' => $record('other-platform', 'example.test', 'shopify'),
            'other-path' => $record('other-path', 'https://example.test/another-shop'),
            'other-host' => $record('other-host', 'www.example.test'));
        $api->writes = array();
        $check(mailchimp_get_store_id() === 'recovered', 'Recover canonical and exact legacy domains without adopting another store');
        $check($admin->syncStore($data) && $api->writes[0][0] === 'PATCH' && $api->writes[0][2]['id'] === 'recovered', 'Recovery updates rather than duplicates');
    }

    // A stale verification cache skips lookup: exercise syncStore's conflict recovery too.
    $reset_id('stale-id', true); $api->conflict = true;
    $api->records = array('other-platform' => $record('other-platform', 'example.test', 'shopify'),
        'recovered' => $record('recovered', 'https://example.test/wp', 'woocommerce', 'original-audience'));
    $api->writes = array();
    $check($admin->syncStore($data) === true, 'Conflict recovery succeeds with a legacy store domain');
    $check(count($api->writes) === 2 && $api->writes[1][0] === 'PATCH' &&
        $api->writes[1][2]['id'] === 'recovered' && $api->writes[1][2]['list_id'] === 'original-audience',
        'Conflict recovery selects the WooCommerce store and preserves its original audience');
    $check(mailchimp_get_store_id() === 'recovered', 'Conflict recovery persists the recovered ID');

    $reset_id('stale-id', true); unset($api->records['recovered']); $api->writes = array();
    $thrown = false;
    try { $admin->syncStore($data); } catch (MailChimp_WooCommerce_Error $e) { $thrown = true; }
    $check($thrown && count($api->writes) === 1 && mailchimp_get_store_id() === 'stale-id', 'Never adopt a different platform after a conflict');
    $api->conflict = false;

    $filtered_home = function() { return 'https://mapped.example.test/language'; };
    add_filter('home_url', $filtered_home);
    try {
        $reset_id('existing-store', true); $api->records = array('existing-store' => $record('existing-store', $site)); $api->writes = array();
        $check($admin->syncStore($data) && $api->writes[0][2]['domain'] === 'mapped.example.test', 'Honor the public home_url filter');
    } finally { remove_filter('home_url', $filtered_home); }

    $api->fail_write = true; $api->writes = array(); $thrown = false;
    try { $admin->syncStore($data); } catch (MailChimp_WooCommerce_Error $e) { $thrown = true; }
    $check($thrown && mailchimp_get_store_id() === 'existing-store', 'API failure propagates without replacing store identity');
    $api->fail_write = false;

    $home = '/no-host'; $api->writes = array(); $thrown = false;
    try { $admin->syncStore($data); } catch (MailChimp_WooCommerce_Error $e) { $thrown = true; }
    $check($thrown && !$api->writes, 'Invalid public address cannot silently register the installation URL');
    echo json_encode(array('checks' => $checks, 'wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION,
        'php' => PHP_VERSION, 'result' => 'passed')) . "\n";
} finally {
    foreach ($original as $key => $value) {
        if ($value === false) { Mailchimp_Woocommerce_DB_Helpers::delete_option('mailchimp-woocommerce-' . $key); }
        else { mailchimp_set_data($key, $value); }
    }
    delete_transient('mailchimp_woocommerce_store_id_verified');
    if ($verified !== false) { set_transient('mailchimp_woocommerce_store_id_verified', $verified, 600); }
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce');
    Store_URL_Local_Api::reset();
    remove_filter('pre_option_home', $home_filter); remove_filter('pre_option_siteurl', $site_filter);
    remove_filter('pre_http_request', $no_http); remove_filter('pre_wp_mail', $no_mail);
}

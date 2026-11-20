<?php
/** Run with: php tests/regression/server-ip-lookup-tls.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// The enhanced logger looks up the server's outbound IP from public IP services for the
// connection log. That lookup used to pass sslverify => false, which accepts any
// certificate. It must now leave verification at WordPress's default (on, against the
// bundled CA file) while still accepting a valid IP reply, rejecting junk, and falling
// through the service list to SERVER_ADDR.
error_reporting(E_ALL & ~E_DEPRECATED);

class WP_Error {
    public $message;
    public function __construct($message = '') { $this->message = $message; }
}

// Each call pops the next queued reply and records the URL and args it was given.
function wp_remote_get($url, $args = array()) {
    $GLOBALS['requests'][] = array('url' => $url, 'args' => $args);
    return array_shift($GLOBALS['replies']);
}
function is_wp_error($thing) { return $thing instanceof WP_Error; }
function wp_remote_retrieve_body($response) {
    return is_array($response) && isset($response['body']) ? $response['body'] : '';
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

require dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-enhanced-logger.php';

function server_ip(array $replies) {
    $GLOBALS['requests'] = array();
    $GLOBALS['replies'] = $replies;
    $method = new ReflectionMethod('MailChimp_WooCommerce_Enhanced_Logger', 'get_server_ip');
    $method->setAccessible(true);
    return $method->invoke(null);
}
function requests() { return $GLOBALS['requests']; }

unset($_SERVER['SERVER_ADDR']);

// a valid reply from the first service is accepted, whitespace and all
check(server_ip(array(array('body' => "203.0.113.7\n"))) === '203.0.113.7', 'a valid IPv4 reply is returned trimmed');
check(count(requests()) === 1, 'the lookup stops at the first valid reply');
check(server_ip(array(array('body' => '2001:db8::1'))) === '2001:db8::1', 'a valid IPv6 reply is returned');

// every request leaves certificate verification at the WordPress default
foreach (requests() as $request) {
    check(strpos($request['url'], 'https://') === 0, 'the IP service is called over https: ' . $request['url']);
    check(!array_key_exists('sslverify', $request['args']), 'no sslverify override is sent to ' . $request['url']);
    check(isset($request['args']['timeout']) && $request['args']['timeout'] === 2, 'the 2 second timeout is kept for ' . $request['url']);
}

// junk and errors fall through the service list in order
$junk = array('body' => '<html><body>503 Service Unavailable</body></html>');
check(server_ip(array($junk, new WP_Error('cURL error 60: SSL certificate problem'), array('body' => '198.51.100.9'))) === '198.51.100.9', 'junk, then an error, then a valid reply yields the valid reply');
$urls = array_map(function ($r) { return $r['url']; }, requests());
check($urls === array('https://api.ipify.org', 'https://icanhazip.com', 'https://ipecho.net/plain'), 'the three services are tried in order');
foreach (requests() as $request) {
    check(!array_key_exists('sslverify', $request['args']), 'no sslverify override on the fallback request to ' . $request['url']);
}

// an unverifiable certificate now comes back as a WP_Error and is never accepted as an IP
$tls_error = new WP_Error('cURL error 60: SSL certificate problem: unable to get local issuer certificate');
check(server_ip(array($tls_error, $tls_error, $tls_error)) === 'unknown', 'with every service failing and no SERVER_ADDR the result is unknown');
check(count(requests()) === 3, 'every service was tried before giving up');

$_SERVER['SERVER_ADDR'] = '10.0.0.5';
check(server_ip(array($junk, $junk, $junk)) === '10.0.0.5', 'with every reply invalid the lookup falls back to SERVER_ADDR');
check(server_ip(array(array('body' => '999.1.1.1'), $junk, $junk)) === '10.0.0.5', 'an out-of-range address is not an IP');
check(server_ip(array(array('body' => ''), $junk, $junk)) === '10.0.0.5', 'an empty body is not an IP');

// and the shipped file no longer disables verification anywhere
$source = file_get_contents(dirname(__DIR__, 2) . '/includes/class-mailchimp-woocommerce-enhanced-logger.php');
check(!preg_match('/[\'"]sslverify[\'"]\s*=>\s*false/', $source), 'the enhanced logger never passes sslverify => false');

echo "server-ip-lookup-tls: all checks passed\n";

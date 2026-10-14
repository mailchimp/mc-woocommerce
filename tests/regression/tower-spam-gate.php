<?php
/** Run with: php tests/regression/tower-spam-gate.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// Load only the transient helpers and the API class, avoiding WordPress bootstrap side effects.
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
foreach (array('function mailchimp_get_transient(', 'function mailchimp_set_transient(', 'function mailchimp_get_transient_value(') as $needle) {
    $start = strpos($bootstrap, $needle);
    eval(substr($bootstrap, $start, strpos($bootstrap, "\n/**", $start) - $start));
}
error_reporting(E_ALL & ~E_DEPRECATED);
require dirname(__DIR__, 2) . '/includes/api/class-mailchimp-api.php';

$GLOBALS['cache'] = array();
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; return true; }
class WP_Error {}
function is_wp_error($thing) { return $thing instanceof WP_Error; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function wp_remote_get($url) {
    ++$GLOBALS['tower_calls'];
    return $GLOBALS['tower'];
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function allowed() {
    $method = new ReflectionMethod('MailChimp_WooCommerce_MailChimpApi', 'allowedToSubmitSpam');
    $method->setAccessible(true);
    return $method->invoke(new MailChimp_WooCommerce_MailChimpApi());
}
function reset_tower($response) {
    $GLOBALS['cache'] = array();
    $GLOBALS['tower_calls'] = 0;
    $GLOBALS['tower'] = $response;
}

// green stays allowed once cached (the bug: the cached wrapper never equalled 'green')
reset_tower(array('body' => json_encode(array('status' => 'green'))));
check(allowed() === true, 'green from Tower is allowed');
check(allowed() === true, 'cached green is still allowed');
check($GLOBALS['tower_calls'] === 1, 'cached status does not call Tower again');

// red is blocked and cached
reset_tower(array('body' => json_encode(array('status' => 'red'))));
check(allowed() === false && allowed() === false && $GLOBALS['tower_calls'] === 1, 'red is blocked and cached');

// a failed request is treated as red and cached instead of throwing
reset_tower(new WP_Error());
check(allowed() === false && allowed() === false && $GLOBALS['tower_calls'] === 1, 'WP_Error is red and cached');

// an unusable body is treated as red
reset_tower(array('body' => 'not json'));
check(allowed() === false, 'bad body is red');

echo "Tower spam gate regression checks passed.\n";

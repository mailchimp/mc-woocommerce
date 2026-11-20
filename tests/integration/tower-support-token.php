<?php
/**
 * Remote support token lifecycle. Tower is simulated; all external HTTP is blocked.
 * MC_WOO_TOWER_LOCAL_QA=1 MC_WOO_WP_LOAD=/path/to/wp-load.php php tests/integration/tower-support-token.php
 * Run only on an unconfigured disposable local site (localhost or *.test). Options are restored afterward.
 */
if (PHP_SAPI !== 'cli') { exit; }
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require getenv('MC_WOO_WP_LOAD');
}
$host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
if (getenv('MC_WOO_TOWER_LOCAL_QA') !== '1' || mailchimp_is_configured() ||
    !(in_array($host, array('localhost', '127.0.0.1'), true) || substr($host, -5) === '.test')) {
    throw new RuntimeException('Requires MC_WOO_TOWER_LOCAL_QA=1 and an unconfigured disposable local site.');
}
$mode = isset($argv[1]) ? $argv[1] : 'run';
if ($mode === 'probe') {
    $request = new WP_REST_Request('POST', '/mailchimp-for-woocommerce/v1/tower/' . $argv[2]);
    $request->set_header('Authorization', 'Bearer ' . base64_encode($argv[3]));
    $request->set_header('Content-Type', 'application/json'); $request->set_body('{"test":true}');
    $response = rest_do_request($request);
    echo 'HTTP ' . $response->get_status();
    exit;
}
function probe($route, $token) {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' probe ' . escapeshellarg($route) . ' ' . escapeshellarg((string) $token) . ' 2>/dev/null');
    return strpos((string) $out, 'HTTP 200') !== false ? 'ok' : (strpos((string) $out, 'unauthorized') !== false ? 'denied' : 'other: ' . substr((string) $out, 0, 120));
}
$checks = 0;
function check($c, $m) { global $checks; ++$checks; echo ($c ? 'ok   ' : 'FAIL ') . $m . "\n"; if (!$c) { $GLOBALS['failed'] = true; } }
$orig = array();
foreach (array('tower.opt', 'tower.token') as $k) { $orig[$k] = get_option('mailchimp-woocommerce-' . $k, null); }
$tower_mode = 'ok'; $sent = null; $during = array();
add_filter('pre_http_request', function ($pre, $args, $url) use (&$tower_mode, &$sent, &$during) {
    if (strpos($url, 'https://tower.vextras.com/admin-api/woocommerce/') !== 0) { return new WP_Error('local_qa', 'External HTTP disabled'); }
    $body = json_decode($args['body'], true); $sent = $body['support_token'];
    if (strpos($url, '/enable/') !== false) { $during = array(probe('verify', $sent), probe('logs', $sent)); }
    if ($tower_mode === 'down') { return new WP_Error('local_qa', 'Tower unreachable'); }
    return array('response' => array('code' => 200), 'body' => $tower_mode === 'ok' ? '{"success":true}' : '{"success":false,"message":"nope"}', 'headers' => array());
}, 10, 3);
$tower = new MailChimp_WooCommerce_Tower(mailchimp_get_store_id());
$token = function () { return mailchimp_get_data('tower.token'); };
$opt = function () { return (bool) mailchimp_get_data('tower.opt'); };
try {
    // 1. A store that switched support off on an older version still has its token saved.
    mailchimp_set_data('tower.opt', 0); mailchimp_set_data('tower.token', 'stale-token-01');
    check(probe('verify', 'stale-token-01') === 'denied' && probe('logs', 'stale-token-01') === 'denied', 'Leftover token from an earlier disable is refused');

    // 2. Enable, Tower confirms.
    $result = $tower->toggle(true); $first = $sent;
    check($during === array('ok', 'denied'), 'During enable the new token passes verify only: ' . json_encode($during));
    check(!empty($result->success) && $token() === $first && $opt() && $first !== 'stale-token-01', 'Confirmed enable saves the new token and switches support on');
    check(get_transient('mailchimp-woocommerce-tower.pending_token') === false, 'Pending token is cleared');
    check(probe('verify', $first) === 'ok' && probe('logs', $first) !== 'denied', 'Enabled token is accepted');
    check(probe('verify', 'stale-token-01') === 'denied', 'Replaced token is refused');

    // 3. Re-enable while on, Tower refuses.
    $tower_mode = 'refuse'; $tower->toggle(true); $second = $sent;
    check($token() === $first && $opt() && probe('verify', $first) === 'ok', 'Failed re-enable keeps the working token');
    check($second !== $first && probe('verify', $second) === 'denied', 'Token from the failed enable is not valid');

    // 4. Disable while Tower is unreachable.
    $tower_mode = 'down'; $result = $tower->toggle(false);
    check($sent === $first, 'Disable sends Tower the current token');
    check($result === null && get_option('mailchimp-woocommerce-tower.token', null) === null && !$opt(), 'Disable removes the token and switches support off without Tower');
    check(probe('verify', $first) === 'denied' && probe('logs', $first) === 'denied', 'Token is refused after disable');

    // 5. Enable from off, Tower unreachable.
    $tower->toggle(true); $third = $sent;
    check(get_option('mailchimp-woocommerce-tower.token', null) === null && !$opt() && probe('verify', $third) === 'denied', 'Failed enable leaves no token behind');

    // 6. Enable then disable, Tower confirms both.
    $tower_mode = 'ok'; $tower->toggle(true); $fourth = $sent; $tower->toggle(false);
    check(probe('verify', $fourth) === 'denied' && !$opt(), 'Normal disable revokes the token');
} finally {
    foreach ($orig as $k => $v) { if ($v === null) { delete_option('mailchimp-woocommerce-' . $k); } else { update_option('mailchimp-woocommerce-' . $k, $v); } }
    delete_transient('mailchimp-woocommerce-tower.pending_token');
    wp_cache_flush();
}
if (!empty($GLOBALS['failed'])) { echo "tower-support-token: FAILED\n"; exit(1); }
echo "tower-support-token: ok ({$checks} checks)\n";

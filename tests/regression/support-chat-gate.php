<?php
/** Run with: php tests/regression/support-chat-gate.php */
// Load only the gate and transient helpers, avoiding WordPress bootstrap side effects.
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
foreach (array('function mailchimp_support_chat_enabled(', 'function mailchimp_get_transient(', 'function mailchimp_set_transient(', 'function mailchimp_get_transient_value(') as $needle) {
    $start = strpos($bootstrap, $needle);
    eval(substr($bootstrap, $start, strpos($bootstrap, "\n/**", $start) - $start));
}
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
$GLOBALS['cache'] = array();
function get_transient($key) {
    $entry = $GLOBALS['cache'][$key] ?? null;
    return $entry && $entry[1] > $GLOBALS['now'] ? $entry[0] : false;
}
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = array($value, $GLOBALS['now'] + $ttl); return true; }
function mailchimp_debug(...$args) {}
function mailchimp_request_support_chat_status_from_tower() {
    ++$GLOBALS['tower_calls'];
    if ($GLOBALS['tower'] instanceof Exception) { throw $GLOBALS['tower']; }
    return $GLOBALS['tower'];
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function reset_gate($tower) {
    $GLOBALS['cache'] = array();
    $GLOBALS['now'] = 0;
    $GLOBALS['tower_calls'] = 0;
    $GLOBALS['tower'] = $tower;
}

// enabled: one Tower call per hour
reset_gate(true);
check(mailchimp_support_chat_enabled() === true, 'enabled from Tower');
$GLOBALS['now'] = 3599;
check(mailchimp_support_chat_enabled() === true && $GLOBALS['tower_calls'] === 1, 'cached for the hour');
$GLOBALS['now'] = 3601;
mailchimp_support_chat_enabled();
check($GLOBALS['tower_calls'] === 2, 'rechecks after an hour');

// disabled is cached too - false must not look like a cache miss
reset_gate(false);
mailchimp_support_chat_enabled();
check(mailchimp_support_chat_enabled() === false && $GLOBALS['tower_calls'] === 1, 'disabled is cached');

// a failed check disables the chat and retries after 5 minutes
reset_gate(new Exception('tower down'));
check(mailchimp_support_chat_enabled() === false, 'failure disables chat');
$GLOBALS['tower'] = true;
$GLOBALS['now'] = 299;
check(mailchimp_support_chat_enabled() === false && $GLOBALS['tower_calls'] === 1, 'failure cached briefly');
$GLOBALS['now'] = 301;
check(mailchimp_support_chat_enabled() === true && $GLOBALS['tower_calls'] === 2, 'retries after 5 minutes');

echo "Support chat gate regression checks passed.\n";

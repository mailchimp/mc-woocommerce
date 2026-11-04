<?php
/** Run with: php tests/regression/log-delete-allowlist.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
error_reporting(E_ALL & ~E_DEPRECATED);

// Minimal slug stub. Only its internal consistency matters here: the test proves
// the allow-list logic, not WordPress's exact sanitize_title().
function sanitize_title($s) {
    $s = strtolower((string) $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

// Pull mailchimp_string_contains() and the matcher straight out of bootstrap.php by
// brace-matching, so the test exercises the shipped code and not a copy of it.
function extract_function($src, $needle) {
    $start = strpos($src, $needle);
    if ($start === false) { throw new RuntimeException("not found: $needle"); }
    $depth = 0;
    for ($i = strpos($src, '{', $start); $i < strlen($src); $i++) {
        if ($src[$i] === '{') { $depth++; }
        elseif ($src[$i] === '}') { $depth--; if ($depth === 0) { return substr($src, $start, $i - $start + 1); } }
    }
    throw new RuntimeException("unbalanced braces: $needle");
}
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
eval(extract_function($bootstrap, 'function mailchimp_string_contains('));
eval(extract_function($bootstrap, 'function mailchimp_woocommerce_match_log_file('));

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

// A realistic WC_LOG_DIR listing: two of the plugin's own logs plus unrelated files.
$real  = 'mailchimp_woocommerce-2026-11-03-aa11bb22cc33dd44ee55ff6600112233.log';
$other = 'mailchimp_woocommerce-2026-10-30-99887766554433221100aabbccddeeff.log';
$files = array('.', '..', $real, $other, 'fatal-errors.log', 'woocommerce-2026-11-03.log', 'mailchimp_woocommerce-notes.txt');

// The viewer keys files by sanitize_title, so that handle is what comes back in.
check(mailchimp_woocommerce_match_log_file(sanitize_title($real), $files) === $real, 'a real plugin log resolves to its file name');
check(mailchimp_woocommerce_match_log_file(sanitize_title($other), $files) === $other, 'the other real plugin log resolves');

// Whatever the request is, the result is only ever a file from the directory listing.
foreach (array(
    '../../../../etc/passwd'                 => 'path traversal is refused',
    '../wp-config.php'                        => 'parent-dir escape is refused',
    '/var/log/syslog'                        => 'an absolute path is refused',
    'fatal-errors.log'                       => 'a non-plugin log is refused',
    'woocommerce-2026-11-03.log'             => 'a woocommerce core log is refused',
    'mailchimp_woocommerce-notes.txt'        => 'a non-.log plugin file is refused',
    'mailchimp_woocommerce-does-not-exist'   => 'a name not in the directory is refused',
    ''                                       => 'empty input is refused',
) as $input => $message) {
    check(mailchimp_woocommerce_match_log_file($input, $files) === null, $message);
    check(mailchimp_woocommerce_match_log_file(sanitize_title($input), $files) === null, $message . ' (slugged)');
}

echo "log-delete-allowlist: all checks passed\n";

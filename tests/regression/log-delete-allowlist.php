<?php
/** Run with: php tests/regression/log-delete-allowlist.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
error_reporting(E_ALL & ~E_DEPRECATED);

// Slug stub following the ASCII rules of WordPress's sanitize_title_with_dashes()
// (wp-includes/formatting.php): lowercase, '.' to '-', keep only [%a-z0-9 _-],
// whitespace to '-', collapse dashes, trim. Underscores stay, so a real plugin log
// name slugs the way the log viewer's <select> emits it. The test proves the
// allow-list logic, not WordPress's exact function.
function sanitize_title($s) {
    $s = strtolower(strip_tags((string) $s));
    $s = preg_replace('/&.+?;/', '', $s);
    $s = str_replace('.', '-', $s);
    $s = preg_replace('/[^%a-z0-9 _-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('|-+|', '-', $s);
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
check(sanitize_title($real) === 'mailchimp_woocommerce-2026-11-03-aa11bb22cc33dd44ee55ff6600112233-log', 'the stub slugs a log name the way the viewer does');
check(mailchimp_woocommerce_match_log_file(sanitize_title($real), $files) === $real, 'a real plugin log resolves to its file name');
check(mailchimp_woocommerce_match_log_file(sanitize_title($other), $files) === $other, 'the other real plugin log resolves');
check(mailchimp_woocommerce_match_log_file($real, $files) === $real, 'the raw file name resolves too');
check(mailchimp_woocommerce_match_log_file($real, array()) === null, 'an empty directory listing matches nothing');
check(mailchimp_woocommerce_match_log_file($real, false) === null, 'a failed scandir() matches nothing');

// Whatever the request is, the result is only ever a file from the directory listing.
foreach (array(
    '../../../../etc/passwd'                 => 'path traversal is refused',
    '../wp-config.php'                        => 'parent-dir escape is refused',
    '..%2f..%2fwp-config.php'                => 'a percent-encoded traversal is refused',
    '..\\..\\wp-config.php'                  => 'a backslash traversal is refused',
    '/var/log/syslog'                        => 'an absolute path is refused',
    'C:\\Windows\\win.ini'                   => 'a Windows path is refused',
    "fatal-errors.log\0"                     => 'a null byte does not unlock another file',
    'fatal-errors.log'                       => 'a non-plugin log is refused',
    'woocommerce-2026-11-03.log'             => 'a woocommerce core log is refused',
    'mailchimp_woocommerce-notes.txt'        => 'a non-.log plugin file is refused',
    'mailchimp_woocommerce-does-not-exist'   => 'a name not in the directory is refused',
    ''                                       => 'empty input is refused',
) as $input => $message) {
    check(mailchimp_woocommerce_match_log_file($input, $files) === null, $message);
    check(mailchimp_woocommerce_match_log_file(sanitize_title($input), $files) === null, $message . ' (slugged)');
}

// A directory prefix or trailing junk around a real name is dropped by the slug, and
// what comes back is still the listed file name, never the path that was sent.
foreach (array('../../' . $real, '/' . $real, $real . "\0", "..\\" . $real) as $input) {
    check(mailchimp_woocommerce_match_log_file($input, $files) === $real, 'only the listed file name comes back for ' . addcslashes($input, "\0"));
}

echo "log-delete-allowlist: all checks passed\n";

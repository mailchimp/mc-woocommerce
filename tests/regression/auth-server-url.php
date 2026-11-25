<?php
/** Run with: php tests/regression/auth-server-url.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// Each case runs in its own process because a constant can only be defined once.
if (!isset($argv[1])) {
    $default = 'https://woocommerce.mailchimpapp.com';
    $cases = array(
        // not defined, or not a plain http(s) origin: production host
        array(null, $default, false),
        array('', $default, false),
        array('javascript:alert(1)', $default, false),
        array('ftp://auth.example.test', $default, false),
        array('https://auth.example.test/some/path', $default, false),
        array('https://user:pass@auth.example.test', $default, false),
        array('https://auth.example.test?x=1', $default, false),
        // a plain origin replaces the host and nothing else
        array('https://auth.example.test', 'https://auth.example.test', true),
        array('https://auth.example.test/', 'https://auth.example.test', true),
        array(' http://localhost:8080 ', 'http://localhost:8080', true),
    );
    foreach ($cases as $case) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg(json_encode($case[0]));
        $out = json_decode(shell_exec($cmd), true);
        $label = var_export($case[0], true);
        if ($out !== array($case[1], $case[1] . '/api/start', $case[1] . '/api/usernames/available/', $case[2])) {
            fwrite(STDERR, "FAIL for {$label}: " . json_encode($out) . "\n");
            exit(1);
        }
    }
    echo "auth-server-url: ok\n";
    exit(0);
}

$value = json_decode($argv[1]);
if ($value !== null) { define('MAILCHIMP_AUTH_SERVER_URL', $value); }
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
foreach (array('function mailchimp_get_auth_server_url(', 'function mailchimp_auth_server_is_overridden(') as $needle) {
    $start = strpos($bootstrap, $needle);
    eval(substr($bootstrap, $start, strpos($bootstrap, "\n/**", $start) - $start));
}
echo json_encode(array(
    mailchimp_get_auth_server_url(),
    mailchimp_get_auth_server_url('/api/start'),
    mailchimp_get_auth_server_url('api/usernames/available/'),
    mailchimp_auth_server_is_overridden(),
));

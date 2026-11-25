<?php
/** Run with: php tests/regression/e2e-environment.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// Each case runs in its own process because a constant can only be defined once.
if (!isset($argv[1])) {
    $production = 'https://login.mailchimp.com';
    $e2e = 'https://e2e-login.mailchimp.com';
    $cases = array(
        // not defined, or not an "on" value: production login host
        array(null, false, $production),
        array(false, false, $production),
        array(0, false, $production),
        array('', false, $production),
        array('false', false, $production),
        array('e2e', false, $production),
        // switched on
        array(true, true, $e2e),
        array(1, true, $e2e),
        array('true', true, $e2e),
    );
    foreach ($cases as $case) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg(json_encode($case[0]));
        $out = json_decode(shell_exec($cmd), true);
        $label = var_export($case[0], true);
        if ($out !== array($case[1], $case[2], $case[2] . '/oauth2/metadata')) {
            fwrite(STDERR, "FAIL for {$label}: " . json_encode($out) . "\n");
            exit(1);
        }
    }

    // the plugin must use the helper, never a hardcoded login host.
    $root = dirname(__DIR__, 2);
    foreach (array('includes/api/class-mailchimp-api.php', 'admin/class-mailchimp-woocommerce-admin.php') as $file) {
        if (strpos(file_get_contents("{$root}/{$file}"), 'login.mailchimp.com') !== false) {
            fwrite(STDERR, "FAIL: hardcoded login host in {$file}\n");
            exit(1);
        }
    }
    echo "e2e-environment: ok\n";
    exit(0);
}

$value = json_decode($argv[1]);
if ($value !== null) { define('MAILCHIMP_E2E', $value); }
$bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap.php');
foreach (array('function mailchimp_is_e2e_environment(', 'function mailchimp_get_login_url(') as $needle) {
    $start = strpos($bootstrap, $needle);
    eval(substr($bootstrap, $start, strpos($bootstrap, "\n/**", $start) - $start));
}
echo json_encode(array(
    mailchimp_is_e2e_environment(),
    mailchimp_get_login_url(),
    mailchimp_get_login_url('/oauth2/metadata'),
));

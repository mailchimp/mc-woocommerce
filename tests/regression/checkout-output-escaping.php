<?php
/** Run with php tests/regression/checkout-output-escaping.php. No network or WordPress. (sc-53128) */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL & ~E_DEPRECATED);
$checks = 0;
function check($condition, $message) { global $checks; ++$checks; if (!$condition) { throw new RuntimeException($message); } }

$GLOBALS['kses_calls'] = array();
function __($text, $domain = null) { return $text; }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8', false); }
function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8', false); }
function esc_html__($text, $domain = null) { return esc_html($text); }
// Stand-in for wp_kses: records the allowlist it was handed and keeps only those tags.
function wp_kses($text, $allowed) {
    $GLOBALS['kses_calls'][] = $allowed;
    $text = preg_replace('#<script\b.*?</script>#is', '', $text);
    $text = preg_replace('/\son\w+="[^"]*"/i', '', $text);
    return strip_tags($text, '<' . implode('><', array_keys($allowed)) . '>');
}
function is_admin() { return false; }
function is_checkout() { return true; }
function is_user_logged_in() { return false; }
function apply_filters($hook, $value) { return $value; }
function mailchimp_get_list_id() { return 'audience-a'; }
function mailchimp_get_admin_options() { return $GLOBALS['options']; }
function mailchimp_get_api() { return new class { public function getCachedGDPRFields($list) { return $GLOBALS['gdpr']; } }; }

$root = dirname(__DIR__, 2);
$bootstrap = file_get_contents($root . '/bootstrap.php');
$start = strpos($bootstrap, 'function mailchimp_newsletter_label_allowed_html(');
check($start !== false, 'Shared label allowlist helper exists');
eval(substr($bootstrap, $start, strpos($bootstrap, "\n}\n", $start) + 2 - $start));
require $root . '/includes/class-mailchimp-woocommerce-options.php';
require $root . '/includes/class-mailchimp-woocommerce-newsletter.php';

function render() {
    $GLOBALS['kses_calls'] = array();
    ob_start();
    (new MailChimp_Newsletter())->applyNewsletterField(null);
    return ob_get_clean();
}

$allowed = mailchimp_newsletter_label_allowed_html();
check(array_keys($allowed) === array('a', 'br'), 'Label allows only a link and a line break');
check(array_keys($allowed['a']) === array('href', 'title', 'target'), 'Link attributes are href, title and target');

// The merchant label keeps its link and line break, and loses everything else.
$GLOBALS['options'] = array(
    'mailchimp_checkbox_defaults' => 'check',
    'newsletter_label' => 'Join <b>us</b><script>alert(1)</script><br>Read the <a href="https://example.com/privacy" target="_blank" onclick="x()">privacy policy</a>',
);
$GLOBALS['gdpr'] = array(array('marketing_permission_id' => "id'\"><i>", 'text' => 'Email <img src=x onerror=alert(1)> & post', 'enabled' => false));
$html = render();
check($GLOBALS['kses_calls'] === array($allowed), 'Label is filtered once with the same allowlist used on save');
check(strpos($html, '<a href="https://example.com/privacy" target="_blank">privacy policy</a>') !== false, 'Privacy link survives');
check(strpos($html, '<br>') !== false, 'Line break survives');
check(strpos($html, '<script') === false && strpos($html, 'onclick') === false && strpos($html, '<b>') === false, 'Other label markup is removed');
check(strpos($html, 'id="mailchimp_woocommerce_newsletter" type="checkbox" name="mailchimp_woocommerce_newsletter" value="1" checked="checked"') !== false, 'Newsletter checkbox is unchanged');

// Marketing permission text and ids are provider text, never markup.
check(strpos($html, '<span>Email &lt;img src=x onerror=alert(1)&gt; &amp; post</span>') !== false, 'Permission text is escaped as text');
check(strpos($html, '<img') === false && strpos($html, '<i>') === false, 'Permission values add no elements');
check(substr_count($html, "mailchimp_woocommerce_gdpr[id&#039;&quot;&gt;&lt;i&gt;]") === 4, 'Permission id is attribute-escaped in name, for and id');

// Ordinary values render exactly as before.
$GLOBALS['options'] = array('mailchimp_checkbox_defaults' => 'uncheck', 'newsletter_label' => '');
$GLOBALS['gdpr'] = array(array('marketing_permission_id' => 'abc123', 'text' => 'Email', 'enabled' => true));
$html = render();
check(strpos($html, '<span>Subscribe to our newsletter</span>') !== false, 'Default label is used when none is saved');
check(strpos($html, 'checked="checked"> <span>') === false, 'Unchecked default stays unchecked');
check(strpos($html, "<input type='hidden' value='0' name='mailchimp_woocommerce_gdpr[abc123]'>") !== false, 'Permission hidden input is unchanged');
check(strpos($html, "id='mailchimp_woocommerce_gdpr[abc123]' type='checkbox' name='mailchimp_woocommerce_gdpr[abc123]' value='1' checked=\"checked\">") !== false, 'Permission checkbox is unchanged');
check(strpos($html, '<span>Email</span>') !== false, 'Permission text is unchanged');

// Hidden setting still renders nothing.
$GLOBALS['options'] = array('mailchimp_checkbox_defaults' => 'hide');
check(render() === '', 'Hidden newsletter field renders nothing');

echo "checkout-output-escaping: ok ({$checks} checks)\n";

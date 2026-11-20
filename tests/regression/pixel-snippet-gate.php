<?php
/** Run with: php tests/regression/pixel-snippet-gate.php */
if (PHP_SAPI !== 'cli') { exit; }

// Exercise the real output methods; only WordPress/WooCommerce services are stand-ins.
class MailChimp_WooCommerce_Options {}
class Mailchimp_Woocommerce_DB_Helpers {
    public static function get_option($key, $default = false) {
        return array_key_exists($key, $GLOBALS['options']) ? $GLOBALS['options'][$key] : $default;
    }
}
function apply_filters($name, $value) {
    return $name === 'mailchimp_add_inline_footer_script' ? $GLOBALS['filter_value'] : $value;
}
function mailchimp_get_connected_site_script_fragment() { return $GLOBALS['fragment']; }
function plugin_dir_url($path) { return 'https://local.test/plugins/mailchimp/'; }
function mailchimp_environment_variables() { return (object) array('version' => '6.4-test'); }
function rest_url($path) { return 'https://local.test/wp-json/' . $path; }
function esc_url_raw($url) { return $url; }
function esc_js($value) { return $value; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function wp_enqueue_script($handle, $src, $deps, $version, $footer) {
    $GLOBALS['scripts'][$handle] = compact('src', 'deps', 'version', 'footer');
}
function wp_localize_script($handle, $name, $data) { $GLOBALS['localized'][$handle][$name] = $data; }
function WC() {
    return (object) array('cart' => new class {
        public function get_cart() { return array(array('variation_id' => 202, 'product_id' => 100)); }
    }, 'session' => new class {
        public function get_customer_id() { return 'fixture-cart'; }
    });
}
require dirname(__DIR__, 2) . '/includes/tracking/class-mailchimp-woocommerce-pixel-tracking.php';
require dirname(__DIR__, 2) . '/public/class-mailchimp-woocommerce-public.php';

$checks = 0;
function check($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) { throw new RuntimeException($message); }
}
function capture($callback) {
    ob_start();
    try { $callback(); return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$pixel = (new ReflectionClass('MailChimp_WooCommerce_Pixel_Tracking'))->newInstanceWithoutConstructor();
$public = new MailChimp_WooCommerce_Public();
$pixel->append_script_data('events', 'CART_VIEWED');
$snippet = '<script>window.localSnippetFixture = true;</script>';
$cases = array(
    'enabled' => array(true, true, $snippet, true),
    'option off' => array(false, true, $snippet, false),
    'missing fragment' => array(true, true, false, false),
    'empty fragment' => array(true, true, '', false),
    'filter false' => array(true, false, $snippet, false),
    'filter zero' => array(true, 0, $snippet, false),
    'filter empty' => array(true, '', $snippet, false),
    'default option' => array(null, true, $snippet, true),
    'enabled again' => array(true, true, $snippet, true),
);
foreach ($cases as $name => $case) {
    list($option, $GLOBALS['filter_value'], $GLOBALS['fragment'], $enabled) = $case;
    $GLOBALS['options'] = $option === null ? array() : array('mailchimp-woocommerce-code-snippet' => $option);
    $GLOBALS['scripts'] = $GLOBALS['localized'] = array();
    $footer = capture(array($public, 'add_inline_footer_script'));
    $pixel->enqueue_tracking_script();
    $pixel->enqueue_block_tracking_script();
    $inline = capture(array($pixel, 'inline_script_data'));
    check(($footer !== '') === $enabled, "$name: snippet output");
    check(isset($GLOBALS['scripts']['mailchimp-woocommerce-pixel-tracking']) === $enabled, "$name: classic script");
    check(isset($GLOBALS['scripts']['mailchimp-woocommerce-pixel-tracking-blocks']) === $enabled, "$name: block script");
    check(isset($GLOBALS['localized']['mailchimp-woocommerce-pixel-tracking']['mcPixelConfig']) === $enabled, "$name: localized config");
    check((strpos($inline, 'window.mcPixel') !== false) === $enabled, "$name: inline pixel data");
    if ($enabled) {
        check($footer === $snippet, "$name: fragment is unchanged");
        check(strpos($inline, "window.mcPixel.cartId = 'fixture-cart'") !== false, "$name: cart identity preserved");
        check(strpos($inline, '{"202":"100"}') !== false, "$name: variation identity preserved");
        check(strpos($inline, 'CART_VIEWED') !== false, "$name: events preserved");
        check($GLOBALS['scripts']['mailchimp-woocommerce-pixel-tracking']['version'] === '6.4-test', "$name: cache version preserved");
        $asset = require dirname(__DIR__, 2) . '/blocks/build/pixel-tracking.asset.php';
        check($GLOBALS['scripts']['mailchimp-woocommerce-pixel-tracking-blocks']['deps'] === $asset['dependencies'], "$name: block dependencies preserved");
    } else {
        check($inline === '', "$name: no inline output");
    }
}
echo "Pixel snippet gate regression checks passed ($checks checks).\n";
